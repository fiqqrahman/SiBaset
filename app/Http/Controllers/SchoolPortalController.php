<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolCoordinateChangeRequest;
use App\Models\Asset;
use App\Services\PalangkaRayaBoundaryService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class SchoolPortalController extends Controller
{
    public function dashboard(): View
    {
        $school = $this->currentSchool();
        $assets = Asset::query()->where('school_id', $school?->id ?? 0);

        $summary = [
            ['label' => 'Total aset sekolah', 'value' => (clone $assets)->count(), 'meta' => 'Unit aset tercatat', 'icon' => 'box', 'tone' => 'zinc'],
            ['label' => 'Menunggu verifikasi', 'value' => (clone $assets)->where('status', Asset::STATUS_PENDING)->count(), 'meta' => 'Sedang ditinjau dinas', 'icon' => 'clock', 'tone' => 'amber'],
            ['label' => 'Disetujui', 'value' => (clone $assets)->where('status', Asset::STATUS_APPROVED)->count(), 'meta' => 'Hasil pemeriksaan dinas', 'icon' => 'check', 'tone' => 'emerald'],
            ['label' => 'Perlu perbaikan', 'value' => (clone $assets)->where('status', Asset::STATUS_NEEDS_REVISION)->count(), 'meta' => 'Perlu dilengkapi', 'icon' => 'alert', 'tone' => 'rose'],
        ];

        $activities = Asset::query()
            ->where('school_id', $school?->id ?? 0)
            ->with(['type.category', 'acquisition'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function (Asset $asset): array {
                $status = match ($asset->status) {
                    Asset::STATUS_APPROVED => [
                        'label' => match ($asset->acquisition->ownership_status) {
                            'verified_bmd' => 'Terverifikasi sebagai BMD',
                            'verified_non_bmd' => 'Terverifikasi non-BMD',
                            default => 'Disetujui',
                        },
                        'tone' => 'emerald',
                    ],
                    Asset::STATUS_NEEDS_REVISION => ['label' => 'Perlu perbaikan', 'tone' => 'rose'],
                    Asset::STATUS_REJECTED => ['label' => 'Ditolak', 'tone' => 'rose'],
                    default => ['label' => 'Menunggu verifikasi', 'tone' => 'amber'],
                };

                return [
                    'code' => $asset->asset_code,
                    'title' => $asset->name,
                    'type' => $asset->type->category->scheme === 'KIBAR' ? 'KIBAR' : 'KIB '.$asset->type->category->code,
                    'date' => $asset->created_at?->translatedFormat('d M Y, H.i') ?? '—',
                    'status' => $status['label'],
                    'tone' => $status['tone'],
                ];
            })
            ->all();

        return view('pages.school.dashboard', [
            'school' => $school,
            'summary' => $summary,
            'activities' => $activities,
            'pendingRequest' => $school?->coordinateChangeRequests()->where('status', 'pending')->latest()->first(),
        ]);
    }

    public function assets(): View
    {
        $this->currentSchoolOrFail();

        return view('pages.school.assets.index');
    }

    public function profile(PalangkaRayaBoundaryService $boundary): View
    {
        $school = $this->currentSchoolOrFail();
        $cityBoundary = null;

        try {
            $cityBoundary = $boundary->geometry();
        } catch (Throwable) {
            // Page remains usable for viewing; server refuses submissions if boundary cannot be checked.
        }

        return view('pages.school.profile.edit', [
            'school' => $school,
            'pendingRequest' => $school->coordinateChangeRequests()->where('status', 'pending')->latest()->first(),
            'cityBoundary' => $cityBoundary,
        ]);
    }

    private function currentSchool(): ?School
    {
        return $this->currentSchoolQuery()->first();
    }

    private function currentSchoolOrFail(): School
    {
        return $this->currentSchoolQuery()->firstOrFail();
    }

    private function currentSchoolQuery()
    {
        abort_unless(auth()->user()?->role === 'operator_sekolah' && auth()->user()?->school_id, 403);

        return School::query()->whereKey(auth()->user()->school_id);
    }
}
