<?php

use App\Models\School;
use App\Models\SchoolCoordinateChangeRequest;
use App\Models\User;
use App\Services\PalangkaRayaBoundaryService;
use App\Services\SchoolImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

function testPalangkaBoundary(): array
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

function createSchoolForCoordinateTest(array $overrides = []): School
{
    return School::query()->create(array_merge([
        'npsn' => '12345678',
        'nama' => 'Sekolah Uji',
        'bentuk_pendidikan' => 'SD',
        'status_sekolah' => 'Negeri',
        'coordinate_status' => School::COORDINATE_MISSING,
    ], $overrides));
}

function createRoleUser(string $role, ?int $schoolId = null): User
{
    return User::factory()->create([
        'role' => $role,
        'school_id' => $schoolId,
    ]);
}

test('boundary checker accepts an interior point and rejects a point outside polygon', function () {
    $service = app(PalangkaRayaBoundaryService::class);
    $polygon = testPalangkaBoundary();

    expect($service->containsGeometry($polygon, -2.5, 113.5))->toBeTrue()
        ->and($service->containsGeometry($polygon, -2.5, 115.0))->toBeFalse();
});

test('operator cannot submit coordinates for another school', function () {
    $school = createSchoolForCoordinateTest();
    $otherSchool = createSchoolForCoordinateTest(['npsn' => '87654321', 'nama' => 'Sekolah Lain']);
    $operator = createRoleUser('operator_sekolah', $school->id);

    $boundary = Mockery::mock(PalangkaRayaBoundaryService::class);
    $boundary->shouldReceive('contains')->andReturn(true);
    app()->instance(PalangkaRayaBoundaryService::class, $boundary);

    $this->actingAs($operator)
        ->post(route('school.coordinates.store'), [
            'latitude' => -2.5,
            'longitude' => 113.5,
        ])
        ->assertRedirect();

    expect(SchoolCoordinateChangeRequest::query()->count())->toBe(1)
        ->and(SchoolCoordinateChangeRequest::query()->first()->school_id)->toBe($school->id)
        ->and(SchoolCoordinateChangeRequest::query()->first()->school_id)->not->toBe($otherSchool->id);
});

test('out of boundary coordinates are rejected by operator submission', function () {
    $school = createSchoolForCoordinateTest();
    $operator = createRoleUser('operator_sekolah', $school->id);

    $boundary = Mockery::mock(PalangkaRayaBoundaryService::class);
    $boundary->shouldReceive('contains')->with(-2.5, 115.0)->andReturn(false);
    app()->instance(PalangkaRayaBoundaryService::class, $boundary);

    $this->actingAs($operator)
        ->from(route('school.profile.edit'))
        ->post(route('school.coordinates.store'), [
            'latitude' => -2.5,
            'longitude' => 115.0,
        ])
        ->assertRedirect(route('school.profile.edit'))
        ->assertSessionHasErrors('latitude');

    expect(SchoolCoordinateChangeRequest::query()->count())->toBe(0);
});

test('approving a coordinate request updates official coordinates and verifier audit fields', function () {
    $school = createSchoolForCoordinateTest([
        'lintang' => '-2.6000000',
        'bujur' => '113.7000000',
        'coordinate_status' => School::COORDINATE_PENDING,
    ]);
    $operator = createRoleUser('operator_sekolah', $school->id);
    $admin = createRoleUser('admin');

    $submission = SchoolCoordinateChangeRequest::query()->create([
        'school_id' => $school->id,
        'submitted_by' => $operator->id,
        'previous_latitude' => $school->lintang,
        'previous_longitude' => $school->bujur,
        'latitude' => -2.5,
        'longitude' => 113.5,
        'status' => 'pending',
    ]);

    $boundary = Mockery::mock(PalangkaRayaBoundaryService::class);
    $boundary->shouldReceive('contains')->with(-2.5, 113.5)->andReturn(true);
    app()->instance(PalangkaRayaBoundaryService::class, $boundary);

    $this->actingAs($admin)
        ->post(route('admin.schools.coordinate-requests.approve', $submission), ['review_note' => 'Diperiksa'])
        ->assertRedirect();

    $school->refresh();
    $submission->refresh();

    expect($school->coordinate_status)->toBe(School::COORDINATE_VERIFIED)
        ->and((float) $school->lintang)->toBe(-2.5)
        ->and((float) $school->bujur)->toBe(113.5)
        ->and($school->coordinate_verified_by)->toBe($admin->id)
        ->and($school->coordinate_verified_at)->not->toBeNull()
        ->and($submission->status)->toBe('approved');
});

test('operator role cannot open admin import pages', function () {
    $school = createSchoolForCoordinateTest();
    $operator = createRoleUser('operator_sekolah', $school->id);

    $this->actingAs($operator)
        ->get(route('admin.schools.import.index'))
        ->assertForbidden();
});

test('admin login ignores a previous operator URL and is redirected to the admin dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin', 'school_id' => null, 'password' => 'Strong-password-123']);

    // Reproduce a stale intended URL by visiting an operator page before logging in.
    $this->get(route('school.dashboard'))->assertRedirect(route('home'));

    $this->post(route('login'), [
        'email' => $admin->email,
        'password' => 'Strong-password-123',
    ])->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($admin);
});

test('operator login is redirected to its own dashboard even after visiting an admin URL', function () {
    $school = createSchoolForCoordinateTest();
    $operator = User::factory()->create([
        'role' => 'operator_sekolah',
        'school_id' => $school->id,
        'password' => 'Strong-password-123',
    ]);

    $this->get(route('admin.dashboard'))->assertRedirect(route('home'));

    $this->post(route('login'), [
        'email' => $operator->email,
        'password' => 'Strong-password-123',
    ])->assertRedirect(route('school.dashboard'));

    $this->assertAuthenticatedAs($operator);
});

test('legacy dashboard URL redirects administrators to the new URL', function () {
    $admin = User::factory()->create(['role' => 'admin', 'school_id' => null]);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertRedirect(route('admin.dashboard'));
});


test('JSON import upserts by NPSN and never overwrites already verified coordinates', function () {
    $admin = createRoleUser('admin');
    $school = createSchoolForCoordinateTest([
        'sekolah_id' => 'external-001',
        'lintang' => '-2.2500000',
        'bujur' => '113.8500000',
        'coordinate_status' => School::COORDINATE_VERIFIED,
        'coordinate_verified_at' => now(),
        'coordinate_verified_by' => $admin->id,
    ]);

    $boundary = Mockery::mock(PalangkaRayaBoundaryService::class);
    $boundary->shouldReceive('geometry')->andReturn(testPalangkaBoundary());
    $boundary->shouldReceive('containsGeometry')->andReturn(true);

    $importer = new SchoolImportService($boundary);
    $preview = $importer->preview([
        'data' => [[
            'sekolah_id' => 'external-updated',
            'npsn' => $school->npsn,
            'nama' => 'Nama Sekolah Diperbarui',
            'bentuk_pendidikan' => 'SD',
            'status_sekolah' => 'NEGERI',
            'alamat_jalan' => 'Jalan Baru',
            'nama_dusun' => 'Dusun Contoh',
            'kecamatan' => 'Jekan Raya',
            'kabupaten' => 'Kota Palangka Raya',
            'provinsi' => 'Prov. Kalimantan Tengah',
            'nomor_telepon' => null,
            'akreditasi' => 'A',
            'lintang' => '-2.5000000',
            'bujur' => '113.5000000',
        ]],
    ]);

    expect($preview['errors'])->toBe([])
        ->and($preview['summary']['valid_rows'])->toBe(1);

    $result = $importer->import($preview['rows'], $admin, 'schools.json');
    $school->refresh();

    expect($result['created'])->toBe(0)
        ->and($result['updated'])->toBe(1)
        ->and($school->nama)->toBe('Nama Sekolah Diperbarui')
        ->and($school->sekolah_id)->toBe('external-updated')
        ->and((float) $school->lintang)->toBe(-2.25)
        ->and((float) $school->bujur)->toBe(113.85)
        ->and($school->coordinate_status)->toBe(School::COORDINATE_VERIFIED)
        ->and($school->coordinate_verified_by)->toBe($admin->id)
        ->and($school->coordinate_verified_at)->not->toBeNull();
});

test('JSON import rejects duplicate NPSNs inside one file before saving', function () {
    $boundary = Mockery::mock(PalangkaRayaBoundaryService::class);
    $boundary->shouldReceive('geometry')->andReturn(testPalangkaBoundary());
    $boundary->shouldReceive('containsGeometry')->andReturn(true);

    $row = [
        'npsn' => '12345678',
        'nama' => 'Sekolah Uji',
        'bentuk_pendidikan' => 'SD',
        'status_sekolah' => 'Negeri',
    ];

    $preview = (new SchoolImportService($boundary))->preview([
        'data' => [$row, $row],
    ]);

    expect($preview['errors'])->toHaveCount(1)
        ->and($preview['rows'])->toHaveCount(1)
        ->and(str_contains($preview['errors'][0], 'duplikat'))->toBeTrue()
        ->and(School::query()->count())->toBe(0);
});
