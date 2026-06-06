<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function updatePin(Request $request)
    {
        $user = $request->user();
        $hasOldPin = !empty($user->transaction_pin);

        // Dynamic Validation Rules
        $rules = [
            'pin' => 'required|string|size:6|confirmed',
        ];

        $messages = [
            'pin.required' => 'PIN baru wajib diisi.',
            'pin.size' => 'PIN baru harus tepat 6 digit angka.',
            'pin.confirmed' => 'Konfirmasi PIN baru tidak cocok.',
        ];

        // Jika di database sudah ada PIN, aktifkan aturan verifikasi PIN lama
        if ($hasOldPin) {
            $rules['current_pin'] = 'required|string|size:6';
            $rules['pin'] .= '|different:current_pin';

            $messages['current_pin.required'] = 'PIN lama wajib diisi untuk verifikasi.';
            $messages['current_pin.size'] = 'PIN lama harus 6 digit.';
            $messages['pin.different'] = 'PIN baru tidak boleh sama dengan PIN lama Anda.';
        }

        $request->validate($rules, $messages);

        // Eksekusi verifikasi kecocokan hash PIN lama
        if ($hasOldPin && !Hash::check($request->current_pin, $user->transaction_pin)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'current_pin' => 'PIN lama yang Anda masukkan salah.',
            ]);
        }

        // Amankan PIN baru ke MySQL database
        $user->update([
            'transaction_pin' => Hash::make($request->pin),
        ]);

        return redirect()->back()->with('success', 'PIN Keamanan Transaksi berhasil diperbarui!');
    }
}
