<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

// 1. Halaman Utama langsung di-redirect ke Login
Route::get('/', function () {
    return redirect()->route('login');
});

// 2. Route yang membutuhkan Autentikasi (Fitur E-Wallet)
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard ditangani oleh WalletController agar data Saldo & Transaksi terkirim ke Vue
    Route::get('/dashboard', [WalletController::class, 'dashboard'])->name('dashboard');
    Route::post('/transfer', [WalletController::class, 'transfer'])->name('transfer');
    Route::post('/wallet/topup', [WalletController::class, 'topup'])->name('wallet.topup');
    Route::post('/wallet/check', [WalletController::class, 'checkWallet'])->name('wallet.check');
    Route::put('/profile/pin', [ProfileController::class, 'updatePin'])->name('profile.pin.update');
});

// 3. Route Manajemen Profil (Bawaan Laravel Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// 4. Memuat sistem Login, Register, Logout dari Breeze
require __DIR__ . '/auth.php';
