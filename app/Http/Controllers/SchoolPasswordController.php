<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class SchoolPasswordController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'confirmed', 'different:current_password', Password::min(12)],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak sama.',
            'password.different' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.',
        ]);

        $user = $request->user();
        $user->password = $validated['password'];
        $user->password_change_recommended = false;
        $user->save();

        $request->session()->forget('school_password_reminder_dismissed');
        $request->session()->regenerate();

        return redirect()
            ->to(route('school.profile.edit').'#ubah-kata-sandi')
            ->with('status', 'Kata sandi berhasil diperbarui.');
    }

    public function dismissReminder(Request $request): RedirectResponse
    {
        $request->session()->put('school_password_reminder_dismissed', true);

        return back();
    }
}
