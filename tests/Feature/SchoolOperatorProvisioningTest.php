<?php

use App\Models\School;
use App\Models\User;
use App\Services\SchoolOperatorProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

function createSchoolForOperatorProvisioning(string $npsn, string $name): School
{
    return School::query()->create([
        'npsn' => $npsn,
        'nama' => $name,
        'bentuk_pendidikan' => 'SD',
        'status_sekolah' => 'Negeri',
        'coordinate_status' => School::COORDINATE_MISSING,
    ]);
}

test('dry-run does not create or modify user accounts', function () {
    createSchoolForOperatorProvisioning('12345678', 'SD Negeri Contoh');

    $this->artisan('sibaset:make-operators', ['--dry-run' => true])
        ->expectsOutputToContain('Dry-run selesai: 1 akun akan dibuat')
        ->assertSuccessful();

    expect(User::query()->count())->toBe(0)
        ->and(School::query()->count())->toBe(1);
});

test('provisioning creates a linked operator with hashed password and reminder flag', function () {
    $school = createSchoolForOperatorProvisioning('12345678', 'SD Negeri Contoh');

    $result = app(SchoolOperatorProvisioningService::class)->provision('password1234');
    $operator = User::query()->sole();

    expect($result['created'])->toBe(1)
        ->and($operator->role)->toBe('operator_sekolah')
        ->and($operator->school_id)->toBe($school->id)
        ->and($operator->email)->toBe('operator.sd-negeri-contoh.12345678@sibaset.invalid')
        ->and(Validator::make(['email' => $operator->email], ['email' => ['required', 'email', 'max:255']])->passes())->toBeTrue()
        ->and(Hash::check('password1234', $operator->password))->toBeTrue()
        ->and($operator->password_change_recommended)->toBeTrue();

    $originalPasswordHash = $operator->password;
    $secondRun = app(SchoolOperatorProvisioningService::class)->provision('differentpassword123');

    expect($secondRun['created'])->toBe(0)
        ->and($secondRun['skipped'])->toBe(1)
        ->and($operator->refresh()->password)->toBe($originalPasswordHash)
        ->and(User::query()->count())->toBe(1);
});

test('provisioning skips existing operators and protects conflicting admin emails', function () {
    $operatorSchool = createSchoolForOperatorProvisioning('12345678', 'SD Sudah Ada Operator');
    createSchoolForOperatorProvisioning('87654321', 'SD Konflik Email');

    User::factory()->create([
        'role' => 'operator_sekolah',
        'school_id' => $operatorSchool->id,
        'email' => 'operator-lama@example.test',
        'password' => 'operator-password-123',
    ]);

    User::factory()->create([
        'role' => 'admin',
        'school_id' => null,
        'email' => 'operator.sd-konflik-email.87654321@sibaset.invalid',
        'password' => 'admin-password-123',
    ]);

    $result = app(SchoolOperatorProvisioningService::class)->provision('password1234');

    expect($result['created'])->toBe(0)
        ->and($result['skipped'])->toBe(1)
        ->and($result['conflicts'])->toBe(1)
        ->and(User::query()->where('role', 'admin')->count())->toBe(1)
        ->and(User::query()->count())->toBe(2)
        ->and(User::query()->where('email', 'operator.sd-konflik-email.87654321@sibaset.invalid')->value('role'))->toBe('admin');
});

test('single operator provisioning marks the account for a password change', function () {
    $school = createSchoolForOperatorProvisioning('12345678', 'SD Operator Lama');
    $operator = User::factory()->create([
        'role' => 'operator_sekolah',
        'school_id' => $school->id,
        'email' => 'operator-lama@example.test',
        'password' => 'operator-password-123',
        'password_change_recommended' => false,
    ]);

    $this->artisan('sibaset:make-operator', ['npsn' => $school->npsn, 'email' => $operator->email])
        ->expectsConfirmation('Akun sudah ada. Hubungkan akun ini ke sekolah yang dipilih sebagai operator?', 'yes')
        ->expectsQuestion('Kata sandi baru operator', 'new-password-1234')
        ->assertSuccessful();

    expect($operator->refresh()->password_change_recommended)->toBeTrue()
        ->and(Hash::check('new-password-1234', $operator->password))->toBeTrue();
});
