<?php

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function createSchoolForPasswordReminder(): School
{
    return School::query()->create([
        'npsn' => '12345678',
        'nama' => 'SD Operator Uji',
        'bentuk_pendidikan' => 'SD',
        'status_sekolah' => 'Negeri',
        'coordinate_status' => School::COORDINATE_MISSING,
    ]);
}

function createOperatorForPasswordReminder(School $school, array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'operator_sekolah',
        'school_id' => $school->id,
        'password' => 'current-password-123',
        'password_change_recommended' => true,
    ], $overrides));
}

test('password reminder does not block dashboard and can be dismissed for the session', function () {
    $school = createSchoolForPasswordReminder();
    $operator = createOperatorForPasswordReminder($school);

    $this->actingAs($operator)
        ->get(route('school.dashboard'))
        ->assertOk()
        ->assertSee('Kata sandi akun perlu diperbarui');

    $this->from(route('school.dashboard'))
        ->post(route('school.password.reminder.dismiss'))
        ->assertRedirect(route('school.dashboard'));

    $this->get(route('school.dashboard'))
        ->assertOk()
        ->assertDontSee('Kata sandi akun perlu diperbarui');

    $this->get(route('school.assets.index'))->assertOk();

    expect($operator->refresh()->password_change_recommended)->toBeTrue();
});

test('operator can change the password and clear the reminder flag', function () {
    $school = createSchoolForPasswordReminder();
    $operator = createOperatorForPasswordReminder($school);

    $this->actingAs($operator)
        ->put(route('school.password.update'), [
            'current_password' => 'current-password-123',
            'password' => 'replacement-password-456',
            'password_confirmation' => 'replacement-password-456',
        ])
        ->assertRedirect(route('school.profile.edit').'#ubah-kata-sandi')
        ->assertSessionHas('status', 'Kata sandi berhasil diperbarui.');

    $operator->refresh();

    expect(Hash::check('replacement-password-456', $operator->password))->toBeTrue()
        ->and(Hash::check('current-password-123', $operator->password))->toBeFalse()
        ->and($operator->password_change_recommended)->toBeFalse();
});

test('wrong current password is rejected without clearing the reminder flag', function () {
    $school = createSchoolForPasswordReminder();
    $operator = createOperatorForPasswordReminder($school);

    $this->actingAs($operator)
        ->from(route('school.dashboard'))
        ->put(route('school.password.update'), [
            'current_password' => 'wrong-current-password',
            'password' => 'replacement-password-456',
            'password_confirmation' => 'replacement-password-456',
        ])
        ->assertRedirect(route('school.dashboard'))
        ->assertSessionHasErrors('current_password');

    expect($operator->refresh()->password_change_recommended)->toBeTrue()
        ->and(Hash::check('current-password-123', $operator->password))->toBeTrue();
});
