# BodyM Fase 1: Akuisisi dan Audit Dataset

## Tujuan

Fase 1 memastikan dataset BodyM yang akan dipakai pada eksperimen ZRINTTAILOR
berasal dari sumber resmi, dapat direproduksi, utuh, dan dipahami strukturnya.
Fase ini belum melatih model dan belum mengubah alur pengukuran produksi.

## Sumber dan lisensi

- Tanggal akses: 4 Agustus 2026
- Registry resmi: <https://registry.opendata.aws/bodym/>
- Dokumentasi/publikasi: <https://adversarialbodysim.github.io/>
- Bucket publik: `s3://amazon-bodym`
- Region: `us-west-2`
- Materi yang tersedia: siluet hitam-putih, tinggi, berat, gender, dan 14 ukuran
  tubuh dalam sentimeter. Foto RGB asli tidak tersedia.

Registry menulis nama lisensi sebagai **Creative Commons
Attribution-NonCommercial 4.0 International**, tetapi tautan legal yang tampil
pada registry mengarah ke halaman CC BY 4.0 tanpa unsur NonCommercial. Karena
terdapat ketidakkonsistenan ini, proyek memakai interpretasi konservatif:
**CC BY-NC 4.0**. Dataset boleh dipakai untuk penelitian/tugas akhir dengan
atribusi, tetapi tidak boleh diasumsikan aman untuk produk komersial tanpa
konfirmasi tertulis dari pemilik dataset.

## Lokasi lokal dan kebijakan Git

Dataset mentah disimpan di luar repository:

```text
C:\laragon\www\Jasa_jahit\datasets\bodym\
|-- inventory.json
|-- audit.json
`-- raw\
    |-- train\
    |-- testA\
    `-- testB\
```

File mentah, inventaris lengkap, dan laporan audit yang memuat ID pseudonim
tidak dimasukkan ke Git. Repository hanya menyimpan alat reproduksi dan
ringkasan tanpa identitas: `tools/bodym_dataset.py` dan
`docs/bodym/bodym-audit-summary-v1.json`.

## Hasil inventaris dan integritas

Seluruh `17.965` objek berhasil dicocokkan dengan ukuran serta ETag dari bucket.
Tidak ada objek hilang atau checksum yang tidak cocok.

| Jenis | Jumlah |
| --- | ---: |
| Total objek | 17.965 |
| PNG siluet | 17.956 |
| CSV | 9 |
| Ukuran total | 91.438.277 byte (87,2 MiB) |

Setiap PNG mempunyai dimensi `720x960`. Pemeriksaan header tidak menemukan PNG
rusak.

## Struktur dataset

Setiap split memiliki tiga CSV dan dua direktori gambar:

- `hwg_metadata.csv`: `subject_id`, `gender`, `height_cm`, `weight_kg`.
- `measurements.csv`: `subject_id` dan 14 target ukuran.
- `subject_to_photo_map.csv`: relasi satu subjek ke satu atau lebih `photo_id`.
- `mask/`: siluet tampak depan.
- `mask_left/`: siluet tampak samping kiri.

Satu `photo_id` selalu memiliki satu siluet depan dan satu siluet samping.
Karena itu, `8.978 photo_id` menghasilkan `17.956 PNG`, bukan 17.956 subjek.

| Split | Baris subjek | Photo ID | Mask depan | Mask samping |
| --- | ---: | ---: | ---: | ---: |
| Train | 2.018 | 6.134 | 6.134 | 6.134 |
| Test-A | 87 | 1.684 | 1.684 | 1.684 |
| Test-B | 400 | 1.160 | 1.160 | 1.160 |
| Total | 2.505 | 8.978 | 8.978 | 8.978 |

Semua subjek pada setiap split terdapat pada metadata, measurements, dan photo
map. Tidak ditemukan nilai kosong, duplikasi `subject_id` di dalam satu CSV,
duplikasi pasangan subjek-foto, foto tanpa pemetaan, atau pasangan view yang
hilang.

## Label dan unit

Semua 14 target pada `measurements.csv` berupa sentimeter. Pemetaan ke kontrak
`bodym.v1` adalah:

| BodyM | Kontrak ZRINTTAILOR | Jenis |
| --- | --- | --- |
| `ankle` | `ankle_girth` | Lingkar |
| `arm-length` | `arm_length` | Panjang |
| `bicep` | `bicep_girth` | Lingkar |
| `calf` | `calf_girth` | Lingkar |
| `chest` | `chest_girth` | Lingkar |
| `forearm` | `forearm_girth` | Lingkar |
| `height` | `height` | Tinggi |
| `hip` | `hip_girth` | Lingkar |
| `leg-length` | `leg_length` | Panjang |
| `shoulder-breadth` | `shoulder_breadth` | Lebar |
| `shoulder-to-crotch` | `shoulder_to_crotch` | Panjang |
| `thigh` | `thigh_girth` | Lingkar |
| `waist` | `waist_girth` | Lingkar |
| `wrist` | `wrist_girth` | Lingkar |

Neck, knee, inseam, outseam, rise, dan shirt length tidak mempunyai ground truth
BodyM. Field tersebut tidak boleh diberi label sebagai keluaran model BodyM.

## Temuan split

Tidak ada `subject_id` train yang muncul di Test-A atau Test-B. Dengan demikian,
split train aman dari kebocoran identitas terhadap kedua set uji.

Terdapat satu `subject_id` yang muncul di Test-A dan Test-B. Metadata dan ukuran
untuk ID tersebut tidak identik, serta foto yang dipakai berbeda. Total 2.505
baris split karena itu mewakili 2.504 ID global unik. Ini tidak mencemari
pelatihan, tetapi dapat membuat satu identitas berkontribusi dua kali jika hasil
Test-A dan Test-B digabung menjadi satu skor.

Aturan untuk fase training/evaluasi:

1. Pertahankan split resmi dan jangan pindahkan data test ke train.
2. Laporkan metrik Test-A dan Test-B secara terpisah.
3. Jangan menghitung skor gabungan lintas kedua test tanpa deduplikasi subjek.
4. Semua augmentasi dan normalisasi statistik hanya boleh dipelajari dari train.
5. Pemilihan beberapa foto harus dilakukan per `subject_id`, bukan acak per PNG.

## Reproduksi

Jalankan dari root project dengan Python 3:

```powershell
python tools/bodym_dataset.py inventory `
  --output C:\laragon\www\Jasa_jahit\datasets\bodym\inventory.json

python tools/bodym_dataset.py download `
  --inventory C:\laragon\www\Jasa_jahit\datasets\bodym\inventory.json `
  --root C:\laragon\www\Jasa_jahit\datasets\bodym\raw

python tools/bodym_dataset.py verify `
  --inventory C:\laragon\www\Jasa_jahit\datasets\bodym\inventory.json `
  --root C:\laragon\www\Jasa_jahit\datasets\bodym\raw

python tools/bodym_dataset.py audit `
  --root C:\laragon\www\Jasa_jahit\datasets\bodym\raw `
  --output C:\laragon\www\Jasa_jahit\datasets\bodym\audit.json
```

## Keputusan Fase 1

Dataset layak dilanjutkan ke persiapan data dan baseline model dengan batasan:

- penggunaan dibatasi untuk konteks nonkomersial/TA sampai lisensi diklarifikasi;
- keluaran model dibatasi pada 14 label BodyM;
- evaluasi Test-A dan Test-B dipisah;
- tinggi dan berat adalah input/metadata penting BodyM, sehingga strategi untuk
  kondisi aplikasi tanpa input tinggi harus diputuskan sebelum training final.
