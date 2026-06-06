<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class TransferService
{
    public function execute(User $sender, int $receiverId, float $amount)
    {
        if ($sender->id == $receiverId) {
            throw new Exception("Anda tidak dapat mengirim uang ke akun sendiri.");
        }

        if ($amount <= 0) {
            throw new Exception("Nominal transfer harus lebih besar dari nol.");
        }

        return DB::transaction(function () use ($sender, $receiverId, $amount) {
            // Pessimistic Locking untuk mencegah double spending
            $senderUser = User::where('id', $sender->id)->lockForUpdate()->first();
            $receiverUser = User::where('id', $receiverId)->lockForUpdate()->first();

            if (!$receiverUser) {
                throw new Exception("Pengguna penerima tidak ditemukan.");
            }

            if ($senderUser->balance < $amount) {
                throw new Exception("Saldo tidak mencukupi.");
            }

            $senderUser->decrement('balance', $amount);
            $receiverUser->increment('balance', $amount);

            // Base string acak unik
            $baseRef = strtoupper(Str::random(12));

            // Catat transaksi Pengirim (Tambahkan akhiran -OUT)
            Transaction::create([
                'reference_number' => 'TRX-' . $baseRef . '-OUT', // <-- Perubahan di sini
                'sender_id' => $senderUser->id,
                'receiver_id' => $receiverUser->id,
                'type' => 'transfer_out',
                'amount' => $amount,
                'description' => "Transfer ke " . $receiverUser->name
            ]);

            // Catat transaksi Penerima (Tambahkan akhiran -IN)
            Transaction::create([
                'reference_number' => 'TRX-' . $baseRef . '-IN',  // <-- Perubahan di sini
                'sender_id' => $senderUser->id,
                'receiver_id' => $receiverUser->id,
                'type' => 'transfer_in',
                'amount' => $amount,
                'description' => "Terima transfer dari " . $senderUser->name
            ]);

            return 'TRX-' . $baseRef;
        });
    }
}
