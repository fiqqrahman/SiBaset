<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MakeSibasetOperator extends Command
{
    protected $signature = 'sibaset:make-operator {npsn : NPSN sekolah} {email : Email operator}';

    protected $description = 'Buat akun operator yang terikat pada satu sekolah.';

    public function handle(): int
    {
        $school = School::query()->where('npsn', $this->argument('npsn'))->first();

        if (! $school) {
            $this->error('Sekolah dengan NPSN tersebut tidak ditemukan. Impor data sekolah terlebih dahulu.');

            return self::FAILURE;
        }

        $email = strtolower(trim((string) $this->argument('email')));

        if (Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:255']])->fails()) {
            $this->error('Email tidak valid.');

            return self::FAILURE;
        }

        $existing = User::query()->where('email', $email)->first();

        if ($existing?->role === 'admin') {
            $this->error('Akun administrator tidak dapat diubah menjadi operator melalui perintah ini.');

            return self::FAILURE;
        }

        if ($existing && ! $this->confirm('Akun sudah ada. Hubungkan akun ini ke sekolah yang dipilih sebagai operator?', false)) {
            $this->warn('Tidak ada perubahan.');

            return self::SUCCESS;
        }

        $name = $existing?->name ?? trim((string) $this->ask('Nama operator'));
        $password = $this->secret($existing ? 'Kata sandi baru operator' : 'Kata sandi operator');

        if (strlen((string) $password) < 12) {
            $this->error('Kata sandi harus minimal 12 karakter.');

            return self::FAILURE;
        }

        $user = $existing ?? new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->role = 'operator_sekolah';
        $user->school_id = $school->id;
        $user->save();

        $this->info("Akun operator siap untuk {$school->nama} (NPSN {$school->npsn}).");

        return self::SUCCESS;
    }
}
