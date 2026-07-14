# Panduan Pengukuran Multi-view ZRINTTAILOR

Dokumen ini menjelaskan cara kerja fitur ukur badan terbaru untuk kebutuhan tugas akhir dan produksi jahit custom.

## Tujuan

Fitur ukur badan tidak lagi memakai satu foto dan rasio sederhana. Sistem sekarang memakai tiga foto, marker kalibrasi, pose landmark, siluet tubuh, dan evaluasi error agar estimasi ukuran lebih mendekati ukuran manual.

## Protokol Foto

User wajib menyediakan:

- Foto depan.
- Foto samping.
- Foto belakang.
- Marker kalibrasi yang berdiri sendiri.

Marker yang direkomendasikan:

- Marker ArUco ukuran A4.
- Checkerboard ukuran A4.
- Kertas A4 polos sebagai alternatif dengan akurasi lebih rendah.

Marker tidak boleh dipegang oleh user. Marker harus ditempel pada dinding, papan tegak, tripod kecil, hanger, atau calibration pole. Marker harus terlihat penuh pada setiap foto, tegak, tidak menutupi tubuh, dan berada pada bidang jarak yang sama dengan tubuh.

Pose user:

- Berdiri tegak.
- Kepala lurus.
- Tangan rileks sedikit menjauh dari badan.
- Kaki normal.
- Pakaian fit/body-fit.
- Tubuh terlihat penuh dari kepala sampai kaki.

## Alur Sistem

1. User memilih jenis marker.
2. User mengunggah foto depan, samping, dan belakang.
3. Laravel memvalidasi file dan orientasi foto.
4. Laravel mengirim tiga foto ke service FastAPI.
5. FastAPI mendeteksi marker pada setiap foto.
6. Jika marker gagal terdeteksi, proses ditolak.
7. FastAPI mendeteksi pose tubuh dengan MediaPipe.
8. FastAPI mengambil siluet tubuh dengan OpenCV.
9. Sistem menghitung skala pixel ke cm dari marker.
10. Sistem mengambil lebar tubuh dari foto depan/belakang dan kedalaman tubuh dari foto samping.
11. Lingkar tubuh dihitung dengan pendekatan elips.
12. Hasil ditampilkan dengan confidence score dan bisa diedit manual.

## Ukuran Yang Dihitung

Ukuran baju:

- Leher.
- Dada.
- Pinggang.
- Pinggul.
- Lebar bahu.
- Panjang baju.
- Panjang lengan.
- Lengan atas.
- Pergelangan tangan.
- Tinggi badan.

Ukuran celana:

- Pinggang celana.
- Pinggul celana.
- Paha.
- Lutut.
- Betis.
- Bukaan bawah.
- Inseam.
- Outseam.
- Rise/pesak.

## Evaluasi Error

Dataset evaluasi memakai CSV dengan pasangan kolom:

```csv
predicted_chest,actual_chest,predicted_waist,actual_waist
92.4,91.0,78.2,80.0
```

Semua field didukung dengan pola:

```text
predicted_{nama_ukuran}
actual_{nama_ukuran}
```

Jalankan evaluasi:

```bash
php artisan measurement:evaluate storage/app/measurement-evaluation.csv
```

Output menampilkan:

- Jumlah baris dataset.
- Sample per ukuran.
- MAE dalam cm.
- Error maksimum dalam cm.

Target awal:

- Tinggi, bahu, panjang lengan, panjang celana: 1-2.5 cm.
- Dada, pinggang, pinggul: 2-4 cm.
- Paha, betis, ankle, rise: 2-5 cm.

## Pengujian Sintetis Lokal

Jika belum tersedia sampel orang asli, pengujian awal dapat dilakukan dengan dataset sintetis lokal:

```bash
python tools/generate_synthetic_measurement_dataset.py
php artisan measurement:evaluate storage/app/synthetic_measurements/synthetic_measurement_eval.csv
```

Script tersebut membuat:

- CSV ground truth sintetis di `storage/app/synthetic_measurements/synthetic_measurement_eval.csv`.
- Contoh visual SVG depan, samping, dan belakang di `storage/app/synthetic_measurements/samples`.
- README dataset di `storage/app/synthetic_measurements/README.md`.

Pengujian sintetis ini berguna untuk memvalidasi pipeline evaluasi, format CSV, dan perhitungan MAE. Hasilnya tidak boleh diklaim sebagai akurasi tubuh manusia nyata karena bukan berasal dari pengukuran orang asli.

## Catatan Akademik

Hasil sistem adalah estimasi terkalibrasi, bukan pengganti mutlak meteran tailor. Untuk produksi jahit, hasil tetap perlu ditinjau oleh user/admin. Setiap perubahan manual disimpan sebagai `edited_fields_json` agar dapat dianalisis sebagai data koreksi.
