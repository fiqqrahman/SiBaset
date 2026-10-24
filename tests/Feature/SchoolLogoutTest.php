<?php

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createSchoolForLogoutTest(): School
{
    return School::query()->create([
        'npsn' => '12345678',
        'nama' => 'SD Operator Logout',
        'bentuk_pendidikan' => 'SD',
        'status_sekolah' => 'Negeri',
        'coordinate_status' => School::COORDINATE_MISSING,
    ]);
}

function createOperatorForLogoutTest(School $school): User
{
    return User::factory()->create([
        'role' => 'operator_sekolah',
        'school_id' => $school->id,
    ]);
}

test('operator profile modal exposes a CSRF-protected logout form', function () {
    $school = createSchoolForLogoutTest();
    $operator = createOperatorForLogoutTest($school);

    $this->actingAs($operator)
        ->get(route('school.dashboard'))
        ->assertOk()
        ->assertSee('Keluar dari akun')
        ->assertSee('action="'.route('logout').'"', false)
        ->assertSee('method="POST"', false)
        ->assertSee('name="_token"', false);
});

test('operator logout ends the session and prevents access to the portal', function () {
    $school = createSchoolForLogoutTest();
    $operator = createOperatorForLogoutTest($school);

    $this->actingAs($operator)
        ->post(route('logout'))
        ->assertRedirect(route('home'))
        ->assertSessionHas('status', 'Anda berhasil keluar.');

    $this->assertGuest();

    $this->get(route('school.dashboard'))
        ->assertRedirect(route('home'));
});
