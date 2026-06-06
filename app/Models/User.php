<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// PERBAIKAN: Tambahkan 'balance' dan 'transaction_pin' ke dalam daftar Fillable
#[Fillable(['name', 'email', 'password', 'balance', 'transaction_pin'])]
// PERBAIKAN: Sembunyikan 'transaction_pin' agar tidak bocor ke frontend saat data user dipanggil
#[Hidden(['password', 'remember_token', 'transaction_pin'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:2', // Opsional: Memastikan nominal balance terbaca sebagai angka desimal/float yang presisi
        ];
    }
}
