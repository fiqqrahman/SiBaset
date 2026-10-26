<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAcquisition;
use App\Models\AssetChangeRequest;
use App\Models\AssetEvent;
use App\Models\School;
use App\Models\SchoolCoordinateChangeRequest;
use App\Models\SchoolImportLog;
use App\Services\PalangkaRayaBoundaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

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
        $assetStatusCountsBySchool = Asset::query()
            ->select('school_id', 'status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('school_id', 'status')
            ->get()
            ->groupBy('school_id');
        $acquisitionCountsBySchool = AssetAcquisition::query()
            ->select('school_id', 'ownership_status', DB::raw('COUNT(*) as aggregate'))
            ->whereIn('ownership_status', [AssetAcquisition::STATUS_PENDING, AssetAcquisition::STATUS_NEEDS_REVISION])
            ->groupBy('school_id', 'ownership_status')
            ->get()
            ->groupBy('school_id');
        $changeCountsBySchool = AssetChangeRequest::query()
            ->join('assets', 'assets.id', '=', 'asset_change_requests.asset_id')
            ->where('asset_change_requests.status', 'pending')
            ->select('assets.school_id', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('assets.school_id')
            ->get()
            ->keyBy('school_id');

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
                    $insideBoundary = $boundary->containsGeometry($boundaryGeometry, (float) $school->lintang, (float) $school->bujur);
                } catch (Throwable) {
                    $insideBoundary = false;
                }
            }

            $status = $school->coordinate_status;
            if (! $validPair) {
                $status = School::COORDINATE_MISSING;
            } elseif (! $insideBoundary) {
                $status = $boundaryGeometry === null ? School::COORDINATE_NEEDS_REVIEW : School::COORDINATE_OUTSIDE_BOUNDARY;
            } elseif ($school->coordinate_status !== School::COORDINATE_VERIFIED) {
                $status = School::COORDINATE_PENDING;
            }

            $isVisibleOnMap = $status === School::COORDINATE_VERIFIED && $school->coordinate_status === School::COORDINATE_VERIFIED;
            $canPlotOnMap = $validPair && $insideBoundary && in_array($status, [School::COORDINATE_VERIFIED, School::COORDINATE_PENDING], true);
            if ($isVisibleOnMap) $verifiedCount++;

            $assetCounts = $assetStatusCountsBySchool->get($school->id, collect())
                ->keyBy('status')->map(fn ($row): int => (int) $row->aggregate);
            $acquisitionCounts = $acquisitionCountsBySchool->get($school->id, collect())
                ->keyBy('ownership_status')->map(fn ($row): int => (int) $row->aggregate);
            $pendingAcquisitions = (int) $acquisitionCounts->get(AssetAcquisition::STATUS_PENDING, 0);
            $revisionAcquisitions = (int) $acquisitionCounts->get(AssetAcquisition::STATUS_NEEDS_REVISION, 0);

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
                'asset_stats' => [
                    'total' => (int) $assetCounts->sum(),
                    'pending' => (int) $assetCounts->get(Asset::STATUS_PENDING, 0),
                    'approved' => (int) $assetCounts->get(Asset::STATUS_APPROVED, 0),
                    'needs_revision' => (int) $assetCounts->get(Asset::STATUS_NEEDS_REVISION, 0),
                    'rejected' => (int) $assetCounts->get(Asset::STATUS_REJECTED, 0),
                    'pending_acquisitions' => $pendingAcquisitions,
                    'needs_revision_acquisitions' => $revisionAcquisitions,
                    'open_acquisitions' => $pendingAcquisitions + $revisionAcquisitions,
                    'pending_changes' => (int) ($changeCountsBySchool->get($school->id)?->aggregate ?? 0),
                ],
            ];
        }

        $total = $allSchools->count();
        $pendingCoordinateRequests = SchoolCoordinateChangeRequest::query()->where('status', 'pending')->count();
        $missingCoordinates = count(array_filter($schools, static fn (array $school) => $school['location_status'] === School::COORDINATE_MISSING));
        $needsReview = $total - $verifiedCount - $missingCoordinates;

        $assetStatusCounts = Asset::query()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->get()->keyBy('status')->map(fn ($row): int => (int) $row->aggregate);
        $totalAssets = (int) $assetStatusCounts->sum();
        $approvedAssets = (int) $assetStatusCounts->get(Asset::STATUS_APPROVED, 0);
        $pendingAssets = (int) $assetStatusCounts->get(Asset::STATUS_PENDING, 0);
        $revisionAssets = (int) $assetStatusCounts->get(Asset::STATUS_NEEDS_REVISION, 0);
        $rejectedAssets = (int) $assetStatusCounts->get(Asset::STATUS_REJECTED, 0);
        $percentage = static fn (int $count): float => $totalAssets > 0 ? round(($count / $totalAssets) * 100, 1) : 0.0;
        $approvedEnd = $percentage($approvedAssets);
        $pendingEnd = round($approvedEnd + $percentage($pendingAssets), 1);
        $revisionEnd = round($pendingEnd + $percentage($revisionAssets), 1);

        $pendingAcquisitions = AssetAcquisition::query()->where('ownership_status', AssetAcquisition::STATUS_PENDING)->count();
        $revisionAcquisitions = AssetAcquisition::query()->where('ownership_status', AssetAcquisition::STATUS_NEEDS_REVISION)->count();
        $pendingChanges = AssetChangeRequest::query()->where('status', 'pending')->count();

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
                'pending_requests' => $pendingCoordinateRequests,
                'latest_import' => SchoolImportLog::query()->latest()->first(),
            ],
            'assetStats' => [
                'total' => $totalAssets,
                'new_7_days' => Asset::query()->where('created_at', '>=', now()->subDays(7))->count(),
                'pending' => $pendingAssets,
                'approved' => $approvedAssets,
                'needs_revision' => $revisionAssets,
                'rejected' => $rejectedAssets,
                'pending_acquisitions' => $pendingAcquisitions,
                'pending_changes' => $pendingChanges,
                'approval_percentage' => $percentage($approvedAssets),
                'percentages' => [
                    'approved_end' => $approvedEnd,
                    'pending_end' => $pendingEnd,
                    'revision_end' => $revisionEnd,
                ],
            ],
            'notificationPendingCount' => $pendingAcquisitions + $revisionAcquisitions + $pendingChanges + $pendingCoordinateRequests,
        ]);
    }

    public function notifications(): JsonResponse
    {
        $eventTitles = [
            'asset_submitted' => 'Aset baru diajukan',
            'document_uploaded' => 'Dokumen pengadaan diunggah',
            'acquisition_submitted' => 'Pengadaan baru masuk',
            'asset_resubmitted' => 'Data aset dikirim ulang',
            'acquisition_resubmitted' => 'Pengadaan dikirim ulang',
            'change_requested' => 'Perubahan aset diajukan',
            'acquisition_reviewed' => 'Pengadaan selesai diperiksa',
            'asset_reviewed' => 'Status aset diperbarui',
            'change_approved' => 'Perubahan aset disetujui',
            'change_needs_revision' => 'Perubahan aset perlu diperbaiki',
            'change_rejected' => 'Perubahan aset ditolak',
        ];
        $since = now()->subDays(14);
        $assetEvents = AssetEvent::query()
            ->with(['actor', 'acquisition.school', 'asset.school'])
            ->whereHas('actor', fn ($query) => $query->where('role', 'operator_sekolah'))
            ->where('created_at', '>=', $since)
            ->latest('created_at')
            ->limit(40)
            ->get()
            ->map(function (AssetEvent $event) use ($eventTitles): array {
                $school = $event->acquisition?->school ?? $event->asset?->school;
                $details = $event->details ?? [];
                $code = data_get($details, 'asset_code') ?? data_get($details, 'current.asset_code');
                $description = $code ? 'Kode aset: '.$code : 'Aktivitas dicatat dalam riwayat inventaris.';
                if (isset($details['asset_count'])) $description = (int) $details['asset_count'].' unit aset dalam pengajuan.';

                return [
                    'id' => 'asset-event-'.$event->id,
                    'title' => $eventTitles[$event->event_type] ?? 'Aktivitas inventaris',
                    'description' => $description,
                    'school_id' => $school?->id,
                    'school_name' => $school?->nama ?? 'Sekolah tidak tersedia',
                    'actor_name' => $event->actor?->name ?? 'Pengguna tidak tersedia',
                    'occurred_at' => $event->created_at?->toIso8601String(),
                    'target' => str_starts_with($event->event_type, 'change_') || $event->event_type === 'change_requested' ? 'change' : 'asset',
                    'event_type' => $event->event_type,
                    'status' => data_get($details, 'decision') ?? data_get($details, 'status'),
                ];
            });

        $coordinateEvents = SchoolCoordinateChangeRequest::query()
            ->with(['school', 'submittedBy'])
            ->where('status', 'pending')
            ->where('created_at', '>=', $since)
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(function (SchoolCoordinateChangeRequest $coordinateRequest): array {
                return [
                    'id' => 'coordinate-request-'.$coordinateRequest->id,
                    'title' => 'Operator mengajukan koordinat sekolah',
                    'description' => $coordinateRequest->request_note ?: ($coordinateRequest->review_note ?: 'Pembaruan lokasi sekolah.'),
                    'school_id' => $coordinateRequest->school?->id,
                    'school_name' => $coordinateRequest->school?->nama ?? 'Sekolah tidak tersedia',
                    'actor_name' => $coordinateRequest->submittedBy?->name ?? 'Operator sekolah',
                    'occurred_at' => $coordinateRequest->created_at?->toIso8601String(),
                    'target' => 'coordinate',
                    'event_type' => 'coordinate_pending',
                    'status' => $coordinateRequest->status,
                ];
            });

        $feed = $assetEvents->concat($coordinateEvents)
            ->sortByDesc(static fn (array $item): int => strtotime($item['occurred_at'] ?? '') ?: 0)
            ->take(25)->values();
        $pendingCount = AssetAcquisition::query()->whereIn('ownership_status', [AssetAcquisition::STATUS_PENDING, AssetAcquisition::STATUS_NEEDS_REVISION])->count()
            + AssetChangeRequest::query()->where('status', 'pending')->count()
            + SchoolCoordinateChangeRequest::query()->where('status', 'pending')->count();

        return response()->json([
            'data' => $feed,
            'meta' => ['pending_count' => $pendingCount, 'period_days' => 14],
        ]);
    }
}