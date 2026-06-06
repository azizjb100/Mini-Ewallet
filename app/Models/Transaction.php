<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    // Daftarkan semua kolom yang diizinkan untuk diisi secara massal
    protected $fillable = [
        'reference_number',
        'sender_id',
        'receiver_id',
        'type',
        'amount',
        'description'
    ];

    // Opsional: Tambahkan relasi jika dibutuhkan di masa depan
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
