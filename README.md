# ZRINTTAILOR

Platform manajemen jasa jahit online berbasis Laravel 13. Pelanggan dapat memesan pakaian custom, memilih ukuran standar atau mengisi ukuran manual, melacak status pesanan secara real-time, dan berkomunikasi langsung dengan admin via chat terintegrasi.

---

## Fitur Utama

### Untuk Pelanggan
- **Pemesanan pakaian custom** — pilih layanan, jenis pakaian, warna, bahan, dan desain
- **Pilihan ukuran standar (S–XXXL)** — auto-isi nilai cm, bisa diedit manual
- **Pengukuran via AI (Computer Vision)** — upload foto dengan objek referensi untuk estimasi ukuran otomatis
- **Tracking pesanan real-time** — timeline status dari pending hingga selesai
- **Konfirmasi penerimaan barang** — pelanggan bisa konfirmasi terima atau lapor masalah
- **Laporan masalah pengiriman** — otomatis terkirim ke chat admin
- **Pembayaran online** — upload bukti transfer, admin verifikasi langsung
- **Chat dengan admin** — notifikasi real-time via polling, tanda baca (✓/✓✓), status online/offline, hapus pesan satu per satu atau multi-pilih
- **Login/Daftar dengan Google** — via OAuth 2.0 (Laravel Socialite)
- **Lupa password** — reset via email

### Untuk Admin
- **Dashboard** — ringkasan pesanan, pembayaran, dan pendapatan
- **Manajemen pesanan** — guided status buttons, badge "BARU" untuk pesanan < 24 jam
- **Verifikasi pembayaran inline** — langsung di halaman detail pesanan
- **Input data pengiriman** — ekspedisi, no. resi, estimasi tiba
- **Manajemen layanan & katalog** — harga, deskripsi, gambar
- **Manajemen portofolio & testimoni**
- **Chat dengan semua pelanggan** — sidebar badge unread, status online/offline user, tanda baca ✓✓, hapus pesan
- **Notifikasi WhatsApp** — via Fonnte API (setiap update status pesanan)

---

## Tech Stack

| Layer | Teknologi |
|---|---|
| Backend | Laravel 13.4, PHP 8.3 |
| Frontend | Blade, Alpine.js, Tailwind CSS |
| Realtime | HTTP Polling (3 detik) |
| Auth | Laravel Breeze + Laravel Socialite (Google) |
| Database | MySQL |
| Storage | Laravel Storage (public disk) |
| WhatsApp | Fonnte API |
| AI/CV | Google Gemini 2.0 Flash Vision (validasi foto) + Python CV service (pengukuran) |

---

## Persyaratan

- PHP >= 8.3
- Composer
- Node.js >= 18 & NPM
- MySQL 8+
- Laragon / XAMPP / server lokal lainnya

---

## Instalasi

### 1. Clone repo

```bash
git clone https://github.com/SuhastraDev/zrinttailor.git
cd zrinttailor
```

### 2. Install dependensi

```bash
composer install
npm install
```

### 3. Konfigurasi environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit file `.env` sesuai kebutuhan:

```env
APP_NAME=ZRINTTAILOR
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zrinttailor
DB_USERNAME=root
DB_PASSWORD=

# Google OAuth (buat di Google Cloud Console)
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback

# WhatsApp via Fonnte
FONNTE_TOKEN=your_fonnte_token

# Pembayaran DANA
DANA_NUMBER=08xxxxxxxxxx
DANA_NAME=Nama Pemilik

# AI Validasi Foto (Google Gemini — gratis)
GEMINI_API_KEY=your_gemini_api_key

# CV/AI Service (pengukuran badan)
CV_SERVICE_URL=http://127.0.0.1:8000

# Mail (untuk reset password)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=no-reply@zrinttailor.com
MAIL_FROM_NAME=ZRINTTAILOR
```

### 4. Migrasi database

```bash
php artisan migrate --seed
```

### 5. Build assets

```bash
npm run build
# atau untuk development:
npm run dev
```

### 6. Storage link

```bash
php artisan storage:link
```

### 7. Jalankan aplikasi

```bash
php artisan serve
```

Buka `http://localhost:8000`

---

## Akun Default (Seeder)

| Role | Email | Password |
|---|---|---|
| Admin | admin@zrinttailor.com | password |
| Pelanggan | user@zrinttailor.com | password |

> Pastikan seeder dijalankan: `php artisan db:seed`

---

## Struktur Direktori Penting

```
app/
├── Http/Controllers/
│   ├── Admin/          # Dashboard, Order, Payment, Shipment, dll
│   └── User/           # Order, Payment, Chat, Measurement, Tracking
├── Models/             # User, Order, Payment, Chat, Message, dll
resources/
├── views/
│   ├── admin/          # Halaman panel admin
│   ├── user/           # Halaman panel pelanggan
│   ├── auth/           # Login, Register, Forgot Password
│   └── layouts/        # admin.blade.php, user.blade.php
database/
└── migrations/         # Semua migrasi tabel
```

---

## Alur Status Pesanan

```
pending → confirmed → processing → done → shipped → completed
                                                   ↘ [laporan masalah via chat]
                          ↘ cancelled (kapan saja sebelum done)
```

---

## Google OAuth Setup

1. Buka [Google Cloud Console](https://console.cloud.google.com/)
2. Buat project baru → **APIs & Services** → **Credentials**
3. Buat **OAuth 2.0 Client ID** (tipe: Web Application)
4. Tambahkan Authorized Redirect URI:
   - Development: `http://localhost:8000/auth/google/callback`
   - Production: `https://yourdomain.com/auth/google/callback`
5. Salin Client ID dan Client Secret ke `.env`

---

## Notifikasi WhatsApp (Fonnte)

1. Daftar di [fonnte.com](https://fonnte.com)
2. Hubungkan nomor WhatsApp
3. Salin token ke `.env` sebagai `FONNTE_TOKEN`

Notifikasi otomatis dikirim ke pelanggan pada setiap perubahan status pesanan.

---

## .env.example

Pastikan file `.env.example` sudah diperbarui dan **tidak** menyertakan nilai sensitif sebelum push ke GitHub. File `.env` asli sudah tercantum di `.gitignore` secara default oleh Laravel.

---

## License

MIT License — bebas digunakan untuk keperluan pribadi maupun komersial.

---

## Developer

Dibuat oleh **[SuhastraDev](https://github.com/SuhastraDev)**
