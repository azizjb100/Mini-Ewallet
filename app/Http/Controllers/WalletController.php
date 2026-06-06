<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use App\Services\TransferService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WalletController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $usersList = User::where('id', '!=', $user->id)->get(['id', 'name']);

        // Tangkap parameter filter dari frontend
        $typeFilter = $request->get('type'); // 'all', 'transfer_in', 'transfer_out'
        $startDate = $request->get('start_date'); // format: YYYY-MM-DD
        $endDate = $request->get('end_date'); // format: YYYY-MM-DD

        // Base Query Riwayat Transaksi milik pengguna
        $query = Transaction::where(function ($q) use ($user) {
            $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id);
        })
            ->where(function ($q) use ($user) {
                $q->where(fn($sub) => $sub->where('sender_id', $user->id)->where('type', 'transfer_out'))
                    ->orWhere(fn($sub) => $sub->where('receiver_id', $user->id)->where('type', 'transfer_in'));
            });

        // 1. Jalankan Filter Tipe Mutasi jika dipilih (in / out)
        if ($typeFilter && $typeFilter !== 'all') {
            $query->where('type', $typeFilter);
        }

        // 2. PERBAIKAN TOTAL: Gunakan Rentang Waktu Carbon yang Auto-Settle dengan Timezone
        if ($startDate && $endDate) {
            // Jika user memilih rentang dari tanggal 4 s/d 4, Carbon akan mengunci dari:
            // 2026-06-04 00:00:00 sampai 2026-06-04 23:59:59 secara presisi
            $start = \Illuminate\Support\Carbon::parse($startDate)->startOfDay();
            $end = \Illuminate\Support\Carbon::parse($endDate)->endOfDay();

            $query->whereBetween('created_at', [$start, $end]);
        } elseif ($startDate) {
            $query->where('created_at', '>=', \Illuminate\Support\Carbon::parse($startDate)->startOfDay());
        } elseif ($endDate) {
            $query->where('created_at', '<=', \Illuminate\Support\Carbon::parse($endDate)->endOfDay());
        }

        // Eksekusi query dengan sorting terbaru & pagination
        $transactions = $query->orderBy('created_at', 'desc')->paginate(5);

        // Kirim kembali parameter filter ke frontend agar form tidak ter-reset saat berpindah halaman
        return Inertia::render('Dashboard', [
            'usersList' => $usersList,
            'transactions' => $transactions,
            'filters' => [
                'type' => $typeFilter ?? 'all',
                'start_date' => $startDate ?? '',
                'end_date' => $endDate ?? '',
            ]
        ]);
    }

    public function transfer(Request $request, TransferService $transferService)
    {
        // Validasi input nomor e-wallet (harus 12 digit, berupa angka, dan ada di tabel users)
        $request->validate([
            'ewallet_number' => 'required|digits:12|exists:users,ewallet_number',
            'amount' => 'required|numeric|min:1',
            'pin' => 'required|string|size:6',
        ], [
            'ewallet_number.required' => 'Nomor e-wallet tujuan wajib diisi.',
            'ewallet_number.digits' => 'Nomor e-wallet harus tepat 12 digit angka.',
            'ewallet_number.exists' => 'Nomor e-wallet tidak terdaftar di sistem kami.',
            'amount.required' => 'Nominal transfer tidak boleh kosong.',
            'pin.required' => 'PIN Validasi wajib diisi.',
        ]);

        $sender = $request->user();

        // 1. Ambil data penerima berdasarkan nomor e-wallet yang diinput
        $receiver = User::where('ewallet_number', $request->ewallet_number)->first();

        // 2. Validasi pencegahan: Tidak boleh mentransfer ke nomor e-wallet milik sendiri
        if ($sender->id === $receiver->id) {
            return redirect()->back()->withErrors(['ewallet_number' => 'Anda tidak dapat mentransfer dana ke nomor e-wallet Anda sendiri.']);
        }

        // 3. Validasi PIN Keamanan Transaksi
        if (!$sender->transaction_pin) {
            return redirect()->back()->withErrors(['pin' => 'Anda belum mengatur PIN transaksi. Silahkan set PIN terlebih dahulu di profil.']);
        }

        if (!Hash::check($request->pin, $sender->transaction_pin)) {
            return redirect()->back()->withErrors(['pin' => 'PIN Keamanan yang Anda masukkan salah!']);
        }

        // 4. Eksekusi transfer menggunakan TransferService
        try {
            // Karena service Anda membutuhkan objek model atau ID penerima, kita oper variabel $receiver yang baru dicari
            $transferService->execute($sender, $receiver, $request->amount);

            return redirect()->back()->with('success', 'Transfer dana berhasil dikirim!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    public function topup(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000', // Minimal top up 10 ribu
            'payment_method' => 'required|in:va_bank,qris', // Validasi pilihan metode
        ], [
            'amount.required' => 'Nominal top up wajib diisi.',
            'amount.min' => 'Minimal top up adalah Rp10.000.',
            'payment_method.required' => 'Silakan pilih metode pembayaran terlebih dahulu.',
        ]);

        try {
            $user = $request->user();
            $amount = (float) $request->amount;
            $method = $request->payment_method;

            // Tentukan teks deskripsi berdasarkan pilihan user
            $description = $method === 'va_bank'
                ? "Top Up Saldo via Virtual Account Bank"
                : "Top Up Saldo via QRIS Instant";

            \Illuminate\Support\Facades\DB::transaction(function () use ($user, $amount, $description) {
                // Lock user untuk mencegah race condition (Keamanan finansial)
                $currentUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
                $currentUser->increment('balance', $amount);

                // Catat transaksi masuk (Simulasi langsung disetujui / dana masuk)
                \App\Models\Transaction::create([
                    'reference_number' => 'TOP-' . strtoupper(\Illuminate\Support\Str::random(12)),
                    'sender_id' => null, // null menandakan dana masuk dari luar sistem e-wallet
                    'receiver_id' => $currentUser->id,
                    'type' => 'transfer_in',
                    'amount' => $amount,
                    'description' => $description
                ]);
            });

            return redirect()->back()->with('success', 'Top Up berhasil! Saldo Anda telah bertambah.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses top up saldo.');
        }
    }

    public function checkWallet(Request $request)
    {
        $request->validate([
            'wallet_code' => 'required|digits:12' // Validasi input harus pas 12 digit angka
        ], [
            'wallet_code.required' => 'Masukkan nomor e-wallet tujuan.',
            'wallet_code.digits' => 'Nomor e-wallet harus berupa 12 digit angka.'
        ]);

        // PERBAIKAN: Cari berdasarkan ewallet_number, bukan email lagi
        $targetUser = \App\Models\User::where('ewallet_number', $request->wallet_code)
            ->where('id', '!=', $request->user()->id)
            ->first();

        if ($targetUser) {
            return response()->json([
                'success' => true,
                'user_id' => $targetUser->id,
                'name' => $targetUser->name
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Nomor E-Wallet tidak ditemukan.'
        ]);
    }
}
