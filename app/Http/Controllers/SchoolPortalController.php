<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolCoordinateChangeRequest;
use App\Services\PalangkaRayaBoundaryService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class SchoolPortalController extends Controller
{
    public function dashboard(): View
    {
        $school = $this->currentSchool();

        return view('pages.school.dashboard', [
            'school' => $school,
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
