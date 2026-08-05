# BodyM Fase 2: Preprocessing Siluet dan Ekstraksi Fitur

## Tujuan

Fase 2 membangun satu pipeline deterministik untuk mengubah pasangan siluet
depan dan samping menjadi feature matrix. Fungsi yang sama dirancang untuk
dipakai saat training dan inference, sedangkan sumber skalanya dapat berbeda.

Fase ini belum melatih model dan belum mengaktifkan BodyM pada aplikasi.

## Artifact

Kode dan kontrak yang masuk repository:

- `python-cv/bodym_preprocessing.py`: normalisasi dan ekstraksi fitur.
- `python-cv/bodym_feature_pipeline.py`: builder dan verifier feature matrix.
- `python-cv/bodym_visual_qa.py`: visual QA depan/samping.
- `tools/bodym_features.py`: CLI build, verify, dan QA.
- `docs/bodym/feature-spec-v1.json`: urutan 224 fitur yang dibekukan.
- `docs/bodym/phase-2-summary-v1.json`: ringkasan hasil pada BodyM asli.

Artifact besar disimpan di luar Git:

```text
C:\laragon\www\Jasa_jahit\datasets\bodym\
|-- processed\
|   |-- bodym-features-v1.csv
|   `-- bodym-features-v1.manifest.json
`-- qa\phase-2\
    |-- index.html
    |-- summary.json
    `-- 9 gambar QA
```

## Pipeline preprocessing

Untuk setiap view, pipeline menjalankan langkah berikut:

1. Konversi gambar grayscale/RGB menjadi mask biner dengan threshold tetap.
2. Cari bounding body menggunakan baris dan kolom dengan sedikitnya dua pixel
   foreground sehingga noise satu pixel tidak mengubah koordinat tubuh.
3. Validasi ukuran body, skala, dan kontinuitas siluet dari kepala ke kaki.
4. Crop bounding body dan resize dengan nearest-neighbor agar mask tetap biner.
5. Tempatkan tubuh di tengah canvas `192x256`, target tinggi 240 pixel, dengan
   lebar maksimum 176 pixel.
6. Ekstrak profil lebar/depth penuh dan komponen yang paling dekat centerline.
7. Konversi fitur fisik ke sentimeter memakai skala view masing-masing.

Versi preprocessing dibekukan sebagai `bodym-preprocess.v1`.

## Kesetaraan training dan inference

Kedua jalur memanggil fungsi publik yang sama:

```text
preprocess_silhouette(mask, view, cm_per_pixel)
extract_pair_features(front, side)
```

Perbedaannya hanya sumber `cm_per_pixel`:

- Training BodyM: `height_cm / tinggi bounding siluet dalam pixel` untuk setiap
  view.
- Inference aplikasi: skala hasil kalibrasi A4/KTP untuk setiap view.

Pengguna produksi tidak perlu mengetik tinggi badan. A4/KTP tetap menjadi
sumber skala fisik, sedangkan bentuk tubuh berasal dari siluet. Integrasi skala
A4/KTP ke fungsi ini dikerjakan pada Fase 5.

## Spesifikasi 224 fitur

Urutan fitur lengkap tersedia di `feature-spec-v1.json` dan diuji terhadap
runtime agar perubahan urutan menyebabkan test gagal.

| Kelompok | Jumlah | Isi |
| --- | ---: | --- |
| Fitur global | 8 | Tinggi per view, selisih tinggi, area, dan rasio bounding |
| Profil siluet | 192 | 32 titik x 6 profil depan/samping, normalized dan cm |
| Anchor anatomi | 24 | 8 area x lebar depan, depth samping, dan elips |
| Total | 224 | Urutan tetap |

Delapan anchor proporsional adalah neck, shoulder, chest, waist, hip, thigh,
calf, dan ankle. Nilai elips adalah fitur geometri untuk model, bukan hasil ukur
final dan bukan pengganti ground truth.

Metadata `gender`, `height_cm`, dan `weight_kg` tetap dibawa pada matrix untuk
audit. Ketiganya tidak dimasukkan ke daftar 224 fitur model. `height_cm` hanya
dipakai untuk membentuk skala training yang setara dengan skala A4/KTP.

## Hasil build pada BodyM asli

| Metrik | Hasil |
| --- | ---: |
| Pasangan input | 8.978 |
| Baris valid | 8.973 |
| Pasangan invalid | 5 (0,056%) |
| Train valid | 6.132 |
| Test-A valid | 1.684 |
| Test-B valid | 1.157 |
| Feature | 224 |
| Target | 14 |
| Ukuran matrix | 25.472.118 byte |
| SHA-256 | `fb7a552cb09dfcb996bed0afad6f44255693596504281ad1ed4a398858675e2c` |

Verifier membaca ulang seluruh matrix dan memastikan:

- hash cocok dengan manifest;
- header dan urutan fitur cocok kontrak;
- jumlah baris dan split cocok;
- tidak ada pasangan split-subject-photo duplikat;
- seluruh nilai fitur dan target berupa angka finite;
- tidak ada NaN atau infinity.

## Mask invalid

Lima pasangan ditolak dengan kode `incomplete_silhouette`: dua dari train dan
tiga dari Test-B. Mask tersebut valid sebagai file PNG/checksum, tetapi bentuk
tubuh terputus atau terfragmentasi. Ambang minimum kontinuitas baris adalah 85%.

Pair invalid tidak diisi nol, tidak diperbaiki secara diam-diam, dan tidak masuk
matrix. Manifest lokal menyimpan split, `photo_id`, kode, dan nilai
`row_coverage` agar keputusan eksklusi dapat diaudit.

## Visual QA

Galeri QA berisi tiga sampel deterministik dari masing-masing train, Test-A,
dan Test-B. Setiap gambar memperlihatkan:

- siluet depan dan samping pada canvas normalisasi;
- kontur berwarna cyan;
- delapan anchor anatomi dan garis lebar/depth;
- tinggi fisik kedua view;
- contoh lebar/depth chest;
- jumlah fitur.

Satu sampel dari setiap split telah diperiksa secara visual. Canvas tidak
terpotong, pasangan view tampil, dan overlay berada pada area proporsional yang
dimaksud.

## Perintah reproduksi

```powershell
python tools/bodym_features.py build `
  --dataset-root C:\laragon\www\Jasa_jahit\datasets\bodym\raw `
  --output-csv C:\laragon\www\Jasa_jahit\datasets\bodym\processed\bodym-features-v1.csv `
  --output-manifest C:\laragon\www\Jasa_jahit\datasets\bodym\processed\bodym-features-v1.manifest.json `
  --allow-invalid

python tools/bodym_features.py verify `
  --matrix C:\laragon\www\Jasa_jahit\datasets\bodym\processed\bodym-features-v1.csv `
  --manifest C:\laragon\www\Jasa_jahit\datasets\bodym\processed\bodym-features-v1.manifest.json

python tools/bodym_features.py qa `
  --dataset-root C:\laragon\www\Jasa_jahit\datasets\bodym\raw `
  --output-dir C:\laragon\www\Jasa_jahit\datasets\bodym\qa\phase-2 `
  --samples-per-split 3
```

## Batasan

- Anchor anatomi merupakan area proporsional pada tinggi siluet, bukan landmark
  anatomi hasil pose detector.
- Akurasi 14 ukuran belum dapat disimpulkan sebelum model dan baseline Fase 3
  dilatih serta dihitung MAE-nya.
- BodyM berupa siluet terkontrol. Foto pengguna tetap memerlukan segmentasi dan
  pemeriksaan domain gap.
- Skala inference A4/KTP belum terhubung pada Fase 2.

## Keputusan Fase 2

Feature matrix terversi dapat dipakai pada Fase 3. Training wajib menggunakan
split resmi, mengecualikan lima pasangan invalid berdasarkan manifest, dan
menjaga seluruh foto dari satu `subject_id` pada kelompok yang sama.
