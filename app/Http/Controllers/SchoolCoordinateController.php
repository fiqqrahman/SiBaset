<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolCoordinateChangeRequest;
use App\Services\PalangkaRayaBoundaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class SchoolCoordinateController extends Controller
{
    public function store(Request $request, PalangkaRayaBoundaryService $boundary): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'operator_sekolah' && $user->school_id, 403);

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'request_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];

        if ($latitude === 0.0 && $longitude === 0.0) {
            throw ValidationException::withMessages([
                'latitude' => 'Koordinat 0,0 tidak valid sebagai lokasi sekolah.',
            ]);
        }

        try {
            if (! $boundary->contains($latitude, $longitude)) {
                throw ValidationException::withMessages([
                    'latitude' => 'Titik berada di luar batas administratif Kota Palangka Raya. Pilih lokasi di dalam poligon kota.',
                ]);
            }
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'latitude' => 'Batas wilayah belum dapat diverifikasi. Pengajuan tidak disimpan demi keamanan koordinat.',
            ]);
        }

        $result = DB::transaction(function () use ($user, $data, $latitude, $longitude): string {
            $school = School::query()->whereKey($user->school_id)->lockForUpdate()->firstOrFail();

            if ($school->coordinateChangeRequests()->where('status', 'pending')->exists()) {
                return 'pending';
            }

            SchoolCoordinateChangeRequest::query()->create([
                'school_id' => $school->id,
                'submitted_by' => $user->id,
                'previous_latitude' => $school->lintang,
                'previous_longitude' => $school->bujur,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'status' => 'pending',
                'request_note' => $data['request_note'] ?? null,
            ]);

            return 'created';
        });

        if ($result === 'pending') {
            return back()->withErrors(['latitude' => 'Sekolah ini masih memiliki pengajuan koordinat yang menunggu pemeriksaan admin.']);
        }

        return back()->with('status', 'Pengajuan koordinat berhasil dikirim dan menunggu verifikasi admin.');
    }

    public function adminIndex(PalangkaRayaBoundaryService $boundary): View
    {
        $boundaryGeometry = null;

        try {
            $boundaryGeometry = $boundary->geometry();
        } catch (Throwable) {
            // Fail closed: coordinates cannot be considered verified without a known boundary.
        }

        $needsReview = School::query()->orderBy('nama')->get()->filter(
            static function (School $school) use ($boundary, $boundaryGeometry): bool {
                $latitude = $school->lintang === null ? null : (float) $school->lintang;
                $longitude = $school->bujur === null ? null : (float) $school->bujur;

                if ($latitude === null || $longitude === null) {
                    $school->setAttribute('review_coordinate_status', School::COORDINATE_MISSING);

                    return true;
                }

                if (
                    $latitude < -90 || $latitude > 90
                    || $longitude < -180 || $longitude > 180
                    || ($latitude === 0.0 && $longitude === 0.0)
                ) {
                    $school->setAttribute('review_coordinate_status', School::COORDINATE_NEEDS_REVIEW);

                    return true;
                }

                if ($boundaryGeometry === null) {
                    $school->setAttribute('review_coordinate_status', School::COORDINATE_NEEDS_REVIEW);

                    return true;
                }

                try {
                    $insideBoundary = $boundary->containsGeometry($boundaryGeometry, $latitude, $longitude);
                } catch (Throwable) {
                    $school->setAttribute('review_coordinate_status', School::COORDINATE_NEEDS_REVIEW);

                    return true;
                }

                if (! $insideBoundary) {
                    $school->setAttribute('review_coordinate_status', School::COORDINATE_OUTSIDE_BOUNDARY);

                    return true;
                }

                if ($school->coordinate_status === School::COORDINATE_VERIFIED) {
                    return false;
                }

                $school->setAttribute('review_coordinate_status', $school->coordinate_status);

                return true;
            }
        )->values();

        $perPage = 12;
        $page = max(1, request()->integer('schools_page', 1));
        $schools = new LengthAwarePaginator(
            $needsReview->forPage($page, $perPage)->values(),
            $needsReview->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'schools_page', 'query' => request()->query()],
        );

        return view('pages.admin.schools.coordinates', [
            'pendingRequests' => SchoolCoordinateChangeRequest::query()
                ->with(['school', 'submittedBy'])
                ->where('status', 'pending')
                ->latest()
                ->paginate(8, ['*'], 'requests_page'),
            'schools' => $schools,
            'boundaryAvailable' => $boundaryGeometry !== null,
        ]);
    }

    public function approve(Request $request, SchoolCoordinateChangeRequest $coordinateRequest, PalangkaRayaBoundaryService $boundary): RedirectResponse
    {
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:2000']]);

        try {
            $inside = $boundary->contains((float) $coordinateRequest->latitude, (float) $coordinateRequest->longitude);
        } catch (Throwable) {
            return back()->withErrors(['review_note' => 'Batas wilayah tidak dapat diperiksa. Pengajuan belum dapat disetujui.']);
        }

        if (! $inside) {
            return back()->withErrors(['review_note' => 'Pengajuan di luar batas kota tidak dapat disetujui. Minta operator memilih koordinat yang benar.']);
        }

        DB::transaction(function () use ($request, $coordinateRequest, $data): void {
            $submission = SchoolCoordinateChangeRequest::query()->whereKey($coordinateRequest->id)->lockForUpdate()->firstOrFail();

            abort_unless($submission->status === 'pending', 409, 'Pengajuan ini sudah diproses.');

            $school = School::query()->whereKey($submission->school_id)->lockForUpdate()->firstOrFail();
            $school->lintang = $submission->latitude;
            $school->bujur = $submission->longitude;
            $school->coordinate_status = School::COORDINATE_VERIFIED;
            $school->coordinate_verified_at = now();
            $school->coordinate_verified_by = $request->user()->id;
            $school->save();

            $submission->status = 'approved';
            $submission->reviewed_by = $request->user()->id;
            $submission->reviewed_at = now();
            $submission->review_note = $data['review_note'] ?? null;
            $submission->save();
        });

        return back()->with('status', 'Koordinat usulan disetujui dan menjadi lokasi resmi sekolah.');
    }

    public function reject(Request $request, SchoolCoordinateChangeRequest $coordinateRequest): RedirectResponse
    {
        $data = $request->validate([
            'review_note' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $coordinateRequest, $data): void {
            $submission = SchoolCoordinateChangeRequest::query()->whereKey($coordinateRequest->id)->lockForUpdate()->firstOrFail();
            abort_unless($submission->status === 'pending', 409, 'Pengajuan ini sudah diproses.');

            $submission->status = 'rejected';
            $submission->reviewed_by = $request->user()->id;
            $submission->reviewed_at = now();
            $submission->review_note = $data['review_note'];
            $submission->save();
        });

        return back()->with('status', 'Pengajuan ditolak. Koordinat resmi sebelumnya tidak diubah.');
    }

    public function verifySchool(Request $request, School $school, PalangkaRayaBoundaryService $boundary): RedirectResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'review_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];

        if ($latitude === 0.0 && $longitude === 0.0) {
            throw ValidationException::withMessages(['latitude' => 'Koordinat 0,0 tidak dapat diverifikasi.']);
        }

        try {
            if (! $boundary->contains($latitude, $longitude)) {
                throw ValidationException::withMessages(['latitude' => 'Koordinat berada di luar poligon Kota Palangka Raya.']);
            }
        } catch (RuntimeException) {
            throw ValidationException::withMessages(['latitude' => 'Batas wilayah tidak tersedia. Koordinat belum dapat diverifikasi.']);
        }

        DB::transaction(function () use ($request, $school, $data, $latitude, $longitude): void {
            $lockedSchool = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            $lockedSchool->lintang = $latitude;
            $lockedSchool->bujur = $longitude;
            $lockedSchool->coordinate_status = School::COORDINATE_VERIFIED;
            $lockedSchool->coordinate_verified_at = now();
            $lockedSchool->coordinate_verified_by = $request->user()->id;
            $lockedSchool->save();

            SchoolCoordinateChangeRequest::query()
                ->where('school_id', $lockedSchool->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                    'review_note' => $data['review_note'] ?? 'Ditutup karena admin memverifikasi koordinat sekolah langsung.',
                    'updated_at' => now(),
                ]);
        });

        return back()->with('status', 'Koordinat sekolah berhasil diperiksa dan diverifikasi.');
    }
}
