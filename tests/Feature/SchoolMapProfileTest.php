<?php

use App\Http\Controllers\DashboardController;
use App\Models\School;
use App\Models\User;
use App\Services\PalangkaRayaBoundaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function mapProfileTestBoundary(): array
{
    return [
        'type' => 'Polygon',
        'coordinates' => [[
            [113.0, -3.0],
            [114.0, -3.0],
            [114.0, -2.0],
            [113.0, -2.0],
            [113.0, -3.0],
        ]],
    ];
}

function createSchoolForMapProfileTest(array $overrides = []): School
{
    return School::query()->create(array_merge([
        'npsn' => '30201234',
        'nama' => 'SMP Negeri Pemetaan',
        'bentuk_pendidikan' => 'SMP',
        'status_sekolah' => 'Negeri',
        'alamat_jalan' => 'Jl. Contoh No. 12',
        'nama_dusun' => 'Marang',
        'kecamatan' => 'Bukit Batu',
        'kabupaten' => 'Kota Palangka Raya',
        'provinsi' => 'Kalimantan Tengah',
        'nomor_telepon' => '0536123456',
        'akreditasi' => 'A',
        'lintang' => '-2.5000000',
        'bujur' => '113.5000000',
        'coordinate_status' => School::COORDINATE_VERIFIED,
    ], $overrides));
}

function bindMapProfileBoundary(): PalangkaRayaBoundaryService
{
    $boundary = Mockery::mock(PalangkaRayaBoundaryService::class);
    $boundary->shouldReceive('geometry')->andReturn(mapProfileTestBoundary());
    $boundary->shouldReceive('containsGeometry')->andReturn(true);
    app()->instance(PalangkaRayaBoundaryService::class, $boundary);

    return $boundary;
}

test('dashboard payload maps full school profile fields from the selected school record', function () {
    $school = createSchoolForMapProfileTest();
    $boundary = bindMapProfileBoundary();

    $view = app(DashboardController::class)->__invoke($boundary);
    $mappedSchool = collect($view->getData()['schools'])->firstWhere('id', $school->id);

    expect($mappedSchool)->toBeArray()
        ->and($mappedSchool)->toMatchArray([
            'id' => $school->id,
            'name' => 'SMP Negeri Pemetaan',
            'npsn' => '30201234',
            'education_level' => 'SMP',
            'school_status' => 'Negeri',
            'address' => 'Jl. Contoh No. 12',
            'hamlet' => 'Marang',
            'district' => 'Bukit Batu',
            'regency' => 'Kota Palangka Raya',
            'province' => 'Kalimantan Tengah',
            'phone' => '0536123456',
            'accreditation' => 'A',
            'location_status' => School::COORDINATE_VERIFIED,
            'latitude' => -2.5,
            'longitude' => 113.5,
        ]);
});

test('admin dashboard renders a separate school modal from the account profile modal', function () {
    createSchoolForMapProfileTest();
    bindMapProfileBoundary();

    $admin = User::factory()->create([
        'name' => 'Admin Pemetaan',
        'role' => 'admin',
        'school_id' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('id="profile-modal"', false)
        ->assertSee('id="school-details-modal"', false)
        ->assertSee('id="school-details-content"', false)
        ->assertSee("const modal = $('#school-details-modal');", false)
        ->assertDontSee("const modal = $('#profile-modal');", false)
        ->assertSee("['Dusun', school.hamlet]", false)
        ->assertSee("['Kabupaten/Kota', school.regency]", false)
        ->assertSee("['Provinsi', school.province]", false)
        ->assertSee('function displayValue(value)', false);
});
