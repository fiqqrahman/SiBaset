<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolCoordinateChangeRequest;
use App\Models\SchoolImportLog;
use App\Services\PalangkaRayaBoundaryService;
use Throwable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(PalangkaRayaBoundaryService $boundary): View
    {
        $boundaryGeometry = null;

        try {
            $boundaryGeometry = $boundary->geometry();
        } catch (Throwable) {
            // Fail closed: the map does not claim any coordinate is verified without the boundary.
        }

        $allSchools = School::query()->orderBy('nama')->get();
        $schools = [];
        $verifiedCount = 0;

        foreach ($allSchools as $school) {
            $validPair = $school->lintang !== null
                && $school->bujur !== null
                && is_numeric($school->lintang)
                && is_numeric($school->bujur)
                && (float) $school->lintang >= -90
                && (float) $school->lintang <= 90
                && (float) $school->bujur >= -180
                && (float) $school->bujur <= 180
                && ! ((float) $school->lintang === 0.0 && (float) $school->bujur === 0.0);

            $insideBoundary = false;

            if ($validPair && $boundaryGeometry !== null) {
                try {
                    $insideBoundary = $boundary->containsGeometry(
                        $boundaryGeometry,
                        (float) $school->lintang,
                        (float) $school->bujur,
                    );
                } catch (Throwable) {
                    $insideBoundary = false;
                }
            }

            $status = $school->coordinate_status;

            if (! $validPair) {
                $status = School::COORDINATE_MISSING;
            } elseif (! $insideBoundary) {
                $status = $boundaryGeometry === null
                    ? School::COORDINATE_NEEDS_REVIEW
                    : School::COORDINATE_OUTSIDE_BOUNDARY;
            } elseif ($school->coordinate_status !== School::COORDINATE_VERIFIED) {
                $status = School::COORDINATE_PENDING;
            }

            $isVisibleOnMap = $status === School::COORDINATE_VERIFIED
                && $school->coordinate_status === School::COORDINATE_VERIFIED;
            $canPlotOnMap = $validPair
                && $insideBoundary
                && in_array($status, [School::COORDINATE_VERIFIED, School::COORDINATE_PENDING], true);

            if ($isVisibleOnMap) {
                $verifiedCount++;
            }

            $schools[] = [
                'id' => $school->id,
                'name' => $school->nama,
                'npsn' => $school->npsn,
                'district' => $school->kecamatan,
                'hamlet' => $school->nama_dusun,
                'address' => $school->alamat_jalan,
                'regency' => $school->kabupaten,
                'province' => $school->provinsi,
                'phone' => $school->nomor_telepon,
                'accreditation' => $school->akreditasi,
                'latitude' => $canPlotOnMap ? (float) $school->lintang : null,
                'longitude' => $canPlotOnMap ? (float) $school->bujur : null,
                'location_status' => $status,
                'education_level' => $school->bentuk_pendidikan,
                'school_status' => $school->status_sekolah,
                'assets' => [],
            ];
        }

        $total = $allSchools->count();
        $pendingSubmissions = SchoolCoordinateChangeRequest::query()->where('status', 'pending')->count();
        $missingCoordinates = count(array_filter($schools, static fn (array $school) => $school['location_status'] === School::COORDINATE_MISSING));
        $needsReview = $total - $verifiedCount - $missingCoordinates;

        return view('pages.dashboard', [
            'schools' => $schools,
            'cityBoundary' => $boundaryGeometry
                ? ['type' => 'Feature', 'properties' => ['name' => 'Kota Palangka Raya'], 'geometry' => $boundaryGeometry]
                : null,
            'boundaryAvailable' => $boundaryGeometry !== null,
            'schoolStats' => [
                'total' => $total,
                'verified' => $verifiedCount,
                'missing' => $missingCoordinates,
                'needs_review' => max(0, $needsReview),
                'pending_requests' => $pendingSubmissions,
                'latest_import' => SchoolImportLog::query()->latest()->first(),
            ],
        ]);
    }
}
