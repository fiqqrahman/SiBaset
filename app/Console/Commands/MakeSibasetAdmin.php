<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MakeSibasetAdmin extends Command
{
    protected $signature = 'sibaset:make-admin {email? : Email akun admin}';

    protected $description = 'Buat akun administrator SiBaset secara interaktif atau promosikan akun yang sudah ada.';

    public function handle(): int
    {
        $email = strtolower(trim((string) ($this->argument('email') ?: $this->ask('Email administrator'))));

        $validator = Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:255']]);

        if ($validator->fails()) {
            $this->error('Email tidak valid.');

            return self::FAILURE;
        }

        $existing = User::query()->where('email', $email)->first();

        if ($existing && ! $this->confirm('Akun sudah ada. Promosikan akun ini menjadi administrator?', false)) {
            $this->warn('Tidak ada perubahan.');

            return self::SUCCESS;
        }

        $name = $existing?->name ?? trim((string) $this->ask('Nama administrator'));
        $password = $existing ? null : $this->secret('Kata sandi administrator');

        if (! $existing && (strlen((string) $password) < 12)) {
            $this->error('Kata sandi awal harus minimal 12 karakter.');

            return self::FAILURE;
        }

        $user = $existing ?? new User();
        $user->name = $name;
        $user->email = $email;
        $user->role = 'admin';
        $user->school_id = null;

        if ($password !== null) {
            $user->password = Hash::make($password);
        }

        $user->save();

        $this->info("Akun administrator SiBaset siap: {$email}");

        return self::SUCCESS;
    }
}
