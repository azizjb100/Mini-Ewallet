# 💳 Mini E-Wallet Application

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="350" alt="Laravel Logo">
</p>

<p align="center">
  <strong>Aplikasi Dompet Digital Modern Menggunakan Laravel 11, Vue 3, Inertia.js, Pinia, dan Tailwind CSS</strong>
</p>

<p align="center">
  Mini E-Wallet merupakan aplikasi simulasi dompet digital yang dirancang dengan konsep modern seperti OVO, DANA, dan GoPay, dilengkapi fitur transfer saldo, top up, keamanan PIN transaksi, serta pengalaman pengguna berbasis Single Page Application (SPA).
</p>

---

## 📖 Tentang Aplikasi

Mini E-Wallet adalah aplikasi dompet digital berbasis web yang dibangun untuk mensimulasikan proses transaksi keuangan elektronik secara aman dan responsif.

Aplikasi ini dikembangkan menggunakan kombinasi teknologi:

- Laravel 11 sebagai Backend Framework
- Vue 3 sebagai Frontend Framework
- Inertia.js sebagai penghubung Backend dan Frontend
- Pinia untuk State Management
- Tailwind CSS untuk antarmuka modern
- MySQL sebagai Database Management System

Dengan arsitektur tersebut, aplikasi mampu memberikan pengalaman pengguna yang cepat tanpa melakukan _full page reload_ seperti aplikasi web tradisional.

---

## ✨ Fitur Utama

### 👤 Manajemen Pengguna

- Registrasi Akun
- Login & Logout
- Edit Profil Pengguna
- Ubah Password
- Pengaturan PIN Transaksi

### 💳 Manajemen Dompet Digital

- Pembuatan Nomor E-Wallet Otomatis
- Dashboard Informasi Saldo
- Informasi Nomor Akun E-Wallet
- Monitoring Saldo Pengguna

### 💸 Transfer Saldo

- Transfer Antar Pengguna
- Validasi Nomor E-Wallet Tujuan
- Verifikasi Nama Penerima
- Konfirmasi Nominal Transfer
- Validasi PIN Sebelum Transaksi

### 🏦 Top Up Saldo

- Simulasi Top Up Virtual Account
- Simulasi Pembayaran QRIS
- Penambahan Saldo Instan

### 🔐 Keamanan Sistem

- PIN Transaksi 6 Digit
- Hashing PIN Menggunakan Laravel Hash
- Validasi Server Side
- Proteksi Saldo dari Double Spending
- Database Transaction & Row Locking

### 🎨 Antarmuka Modern

- Responsive Design
- Mobile Friendly
- SPA (Single Page Application)
- Dashboard Interaktif
- User Experience Modern

---

## 🛠️ Teknologi yang Digunakan

### Backend

| Teknologi | Versi  |
| --------- | ------ |
| PHP       | 8.2+   |
| Laravel   | 11     |
| MySQL     | 8+     |
| Composer  | Latest |

### Frontend

| Teknologi    | Versi  |
| ------------ | ------ |
| Vue.js       | 3      |
| Inertia.js   | Latest |
| Pinia        | Latest |
| Tailwind CSS | Latest |
| Vite         | Latest |

---

## 📂 Struktur Direktori

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   └── RegisteredUserController.php
│   │   ├── ProfileController.php
│   │   └── WalletController.php
│   └── Middleware/
│       └── HandleInertiaRequests.php
│
├── Models/
│   └── User.php
│
└── Services/
    └── TransferService.php
```

---

## 🚀 Instalasi Aplikasi

### 1. Clone Repository

```bash
git clone https://github.com/username/mini-ewallet.git

cd mini-ewallet
```

### 2. Install Dependensi Backend

```bash
composer install
```

### 3. Install Dependensi Frontend

```bash
npm install
```

### 4. Konfigurasi Environment

Salin file environment:

```bash
cp .env.example .env
```

Sesuaikan konfigurasi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mini_ewallet
DB_USERNAME=root
DB_PASSWORD=
```

Generate application key:

```bash
php artisan key:generate
```

### 5. Migrasi Database

```bash
php artisan migrate
```

---

## ▶️ Menjalankan Aplikasi

### Terminal 1 - Menjalankan Laravel

```bash
php artisan serve
```

Akses aplikasi melalui:

```text
http://127.0.0.1:8000
```

### Terminal 2 - Menjalankan Vite

```bash
npm run dev
```

Pastikan kedua terminal berjalan secara bersamaan.

---

## 🔄 Alur Transfer Saldo

```text
Input Nomor E-Wallet
          │
          ▼
Input Nominal Transfer
          │
          ▼
Validasi Akun Tujuan
          │
          ▼
Tampilkan Data Penerima
          │
          ▼
Konfirmasi Transfer
          │
          ▼
Input PIN Transaksi
          │
          ▼
Proses Transfer
          │
          ▼
Transfer Berhasil
```

---

## 🔐 Implementasi Keamanan

### Keamanan PIN

PIN transaksi disimpan menggunakan hashing sehingga tidak dapat dibaca kembali dari database.

```php
Hash::make($request->pin);

Hash::check($request->pin, $user->transaction_pin);
```

### Proteksi Transaksi Keuangan

Sistem menggunakan Database Transaction dan Row Locking untuk mencegah race condition dan double spending.

```php
DB::transaction(function () use ($sender, $receiver, $amount) {

    $sender = User::lockForUpdate()->find($sender->id);

    $receiver = User::lockForUpdate()->find($receiver->id);

    $sender->balance -= $amount;

    $receiver->balance += $amount;

    $sender->save();

    $receiver->save();
});
```

Keuntungan pendekatan ini:

- Mencegah Double Spending
- Mencegah Race Condition
- Menjaga Konsistensi Saldo
- Aman untuk Transaksi Bersamaan

---

## 🎯 Keputusan Desain Sistem

### Nomor E-Wallet

Setiap pengguna mendapatkan nomor akun otomatis:

- Panjang 12 Digit
- Format Numerik
- Prefix Vendor: 88
- Bersifat Unik

Contoh:

```text
881234567890
```

### Penyimpanan Saldo

Saldo pengguna disimpan pada kolom:

```text
users.balance
```

Pendekatan ini dipilih agar proses pembacaan saldo pada dashboard lebih cepat dan sederhana.

---

## 📸 Screenshot Aplikasi

Tambahkan screenshot pada folder:

```text
screenshots/
├── login.png
├── register.png
├── dashboard.png
├── transfer.png
└── topup.png
```

Lalu tampilkan pada README:

```markdown
## Dashboard

![Dashboard](screenshots/dashboard.png)

## Transfer

![Transfer](screenshots/transfer.png)
```

---

## 👨‍💻 Tujuan Pengembangan

Proyek ini dibuat untuk mempelajari dan mengimplementasikan:

- Laravel Service Pattern
- Vue 3 Composition API
- Inertia.js SPA Architecture
- State Management Menggunakan Pinia
- Database Transaction Handling
- Sistem Transaksi Finansial
- Pengembangan Aplikasi Web Modern

---

## 📄 Lisensi

Proyek ini menggunakan lisensi MIT dan dapat digunakan untuk kebutuhan pembelajaran maupun pengembangan lebih lanjut.

---

## 👨‍💻 Pengembang

**Aziz JB**

Keahlian:

- Laravel Development
- Backend Development
- Database Design
- SQL Server
- PostgreSQL
- MySQL
- Query Optimization
- Database Administration (DBA)
- Data Engineering
