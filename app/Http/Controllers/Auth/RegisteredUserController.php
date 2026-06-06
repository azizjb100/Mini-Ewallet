<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'pin' => 'required|string|size:6', // Tambahkan validasi PIN wajib 6 karakter
        ]);

        // ==================== GENERATE NOMOR E-WALLET 12 DIGIT ====================
        // Membuat 12 digit string angka berawalan '88' + 10 digit acak
        $ewalletNumber = '88' . mt_rand(1000000000, 9999999999);

        // Validasi double-check: Jika nomor e-wallet sudah ada yang punya, generate ulang sampai ketemu yang unik
        while (User::where('ewallet_number', $ewalletNumber)->exists()) {
            $ewalletNumber = '88' . mt_rand(1000000000, 9999999999);
        }
        // ==========================================================================

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'ewallet_number' => $ewalletNumber,
            'transaction_pin' => Hash::make($request->pin), // Enkripsi dan simpan PIN pendaftaran
            'balance' => 0.00,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
