# Laporan Pengujian Kesesuaian Proposal ZRINTTAILOR

Tanggal pengujian: 12 Juli 2026  
Lingkungan: lokal, Laravel `http://127.0.0.1:8001`, Vite `http://127.0.0.1:5174`, MySQL `zrinttailor`

## Tujuan

Dokumen ini mencatat hasil pengujian fitur yang disesuaikan dengan proposal tugas akhir:

> Pengembangan Intelligent Tailoring Service dengan Sistem Rekomendasi Ukuran menggunakan Computer Vision pada Proses Pemesanan di ZRINTTAILOR.

Fokus pengujian adalah memastikan alur ukur badan dan pemesanan tidak lagi memakai ukuran standar S/M/L/XL sebagai dasar custom tailoring, serta pilihan jenis pakaian mengikuti ruang lingkup proposal.

## Ruang Lingkup

Yang diuji:

- Login pelanggan demo.
- Akses halaman ukur badan berbasis Computer Vision.
- Form upload foto full body dan benda referensi.
- Pengiriman form ukur badan ke endpoint backend analisis.
- Halaman buat pesanan.
- Pilihan jenis pakaian sesuai proposal.
- Penghapusan opsi denim/jeans dan logika preset ukuran standar.

Yang belum diuji otomatis:

- Akurasi hasil ukur terhadap data ground truth.
- Perhitungan MAE atau metrik evaluasi Computer Vision.
- Integrasi penuh service CV/Gemini sampai menghasilkan ukuran dari foto asli.
- Submit pesanan end-to-end sampai tersimpan, karena membutuhkan data alamat dan pilihan layanan lengkap.

## Data Uji

Akun pelanggan demo:

- Email: `demo@zrinttailor.com`
- Password: `password123`

Seeder yang digunakan:

```bash
php artisan db:seed --class=AdminUserSeeder
```

Seeder tersebut memakai `firstOrCreate`, sehingga aman dijalankan ulang untuk memastikan akun demo tersedia.

## Skenario Pengujian

| ID | Skenario | Ekspektasi | Hasil |
| --- | --- | --- | --- |
| TC-01 | Login sebagai pelanggan demo | User berhasil masuk dan diarahkan ke halaman user, bukan tetap di login | PASS |
| TC-02 | Buka halaman `ukur-badan` | Halaman dapat diakses setelah login | PASS |
| TC-03 | Cek form ukur badan | Form mengarah ke `ukur-badan/analisis`, metode `POST`, dan `multipart/form-data` | PASS |
| TC-04 | Cek input ukur badan | Ada input `body_photo`, `ref_object`, `ref_width_cm`, dan `ref_height_cm` | PASS |
| TC-05 | Cek implementasi lama di ukur badan | Tidak ada `@mediapipe`, `runPose()`, `selectedStdSize`, `stdSizes`, `applyStdSize`, atau kartu `Estimasi Ukuran Baju` | PASS |
| TC-06 | Buka halaman `pesan` | Halaman dapat diakses setelah login | PASS |
| TC-07 | Cek pilihan jenis pakaian | Tersedia `Kemeja`, `Baju Dinas`, `Baju Sekolah`, `Baju Koko`, `Kebaya`, `Gamis`, `Celana Kain`, dan `Rok Kain` | PASS |
| TC-08 | Cek opsi di luar proposal | `Denim` tidak tersedia pada HTML halaman order | PASS |
| TC-09 | Cek preset ukuran standar | Logika preset ukuran standar `selectedStdSize`, `stdSizes`, dan `applyStdSize` tidak ada | PASS |

## Bukti Teknis

Route ukur badan tersedia:

```bash
php artisan route:list --name=user.measurement
```

Route yang relevan:

- `GET ukur-badan` -> `user.measurement.index`
- `POST ukur-badan/analisis` -> `user.measurement.analyze`
- `POST ukur-badan/simpan` -> `user.measurement.store`
- `DELETE ukur-badan/{measurement}` -> `user.measurement.destroy`

Route buat pesanan tersedia:

```bash
php artisan route:list --name=user.orders.create
```

Route yang relevan:

- `GET pesan` -> `user.orders.create`

Hasil browser automation:

```json
[
  {
    "name": "Authenticated demo user session",
    "status": "PASS"
  },
  {
    "name": "Measurement analyze form aligns with proposal",
    "status": "PASS"
  },
  {
    "name": "Order form proposal clothing options and materials",
    "status": "PASS"
  },
  {
    "name": "Protected pages remain accessible after login",
    "status": "PASS"
  }
]
```

## Catatan Hasil

Fitur ukur badan sudah lebih sesuai proposal karena:

- Pengukuran dimulai dari upload satu foto full body.
- Form memakai benda referensi sebagai dasar konversi skala.
- Analisis diarahkan ke backend melalui endpoint `user.measurement.analyze`.
- UI tidak lagi menghitung ukuran dengan MediaPipe langsung di browser.
- UI tidak lagi menampilkan estimasi ukuran baju S/M/L/XL sebagai hasil utama.

Fitur pemesanan sudah lebih sesuai proposal karena:

- Pilihan pakaian mengikuti ruang lingkup proposal.
- Celana dan rok dibatasi sebagai kain.
- Denim/jeans tidak ditawarkan sebagai opsi cepat.
- Preset ukuran standar dihapus dari form manual.

## Gap Untuk Tugas Akhir

Masih perlu ditambahkan modul atau minimal halaman/laporan evaluasi akurasi jika ingin sangat kuat untuk sidang:

1. Simpan ukuran manual penjahit sebagai ground truth.
2. Bandingkan hasil CV dengan ground truth per atribut ukuran.
3. Hitung selisih absolut per ukuran.
4. Hitung MAE untuk dada, pinggang, pinggul, bahu, panjang lengan, dan tinggi.
5. Tampilkan tabel hasil evaluasi atau ekspor CSV/PDF.

Contoh kolom evaluasi:

| Foto | Ukuran | Hasil CV | Ground Truth | Error Absolut |
| --- | --- | ---: | ---: | ---: |
| sample-001 | Dada | 92.0 | 91.0 | 1.0 |
| sample-001 | Pinggang | 78.0 | 80.0 | 2.0 |

Formula:

```text
MAE = jumlah seluruh error absolut / jumlah data uji
```

## Kesimpulan

Berdasarkan pengujian lokal, fitur ukur badan dan pemesanan sudah selaras dengan ruang lingkup utama proposal dari sisi UI dan alur aplikasi. Kekurangan yang masih perlu dipertimbangkan untuk tugas akhir adalah fitur dokumentasi evaluasi akurasi Computer Vision terhadap data ground truth.
