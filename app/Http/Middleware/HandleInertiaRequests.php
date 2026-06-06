<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                // PERBAIKAN: Jika ada user login, pecah datanya secara spesifik dan tambahkan penanda has_pin
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'balance' => $user->balance,
                    'ewallet_number' => $user->ewallet_number,
                    'has_pin' => !empty($user->transaction_pin), // Mengirim status boolean (true/false) ke Vue
                ] : null,
            ],
            // PENTING: Pastikan bagian flash ini mendaftarkan 'receipt'
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'receipt' => fn() => $request->session()->get('receipt'),
            ],
        ]);
    }
}
