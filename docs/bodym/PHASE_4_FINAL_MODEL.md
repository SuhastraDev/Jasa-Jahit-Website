# BodyM Fase 4: Model Final, Kalibrasi, dan Guardrail

## Tujuan

Fase 4 membekukan model BodyM v1 berdasarkan kestabilan antar seed, MAE,
latency, dan ukuran serialisasi. Model dasar terpilih dipertahankan tanpa
retraining, lalu dibungkus pencarian centroid siluet top-K dan koreksi residual
yang dikalibrasi pada validation. Confidence dihitung dari residual validation,
bukan konstanta UI. Prediksi yang berada di luar distribusi atau tidak masuk
akal ditolak dengan diagnosis, tanpa mengubah angka mentah secara diam-diam.

## Pemilihan model

Tiga seed MLP diuji dengan konfigurasi retrieval identik. Seed `20260805`
memakai artifact Fase 3 yang sudah terverifikasi agar model dasar terbaik tidak
berubah akibat retraining. Seluruh seed memenuhi batas p95 latency 50 ms dan
ukuran estimator 3 MB.

| Seed | Validation macro MAE | p95 model-only | Estimator |
| ---: | ---: | ---: | ---: |
| 20260803 | 1,216 cm | 9,257 ms | 1.964.398 byte |
| 20260804 | 1,231 cm | 3,173 ms | 1.964.238 byte |
| 20260805 | **1,165 cm** | **3,820 ms** | 1.508.382 byte |

Rata-rata MAE adalah 1,204 cm, deviasi standar 0,028 cm, dan rentang 0,065
cm. Seed `20260805` dibekukan sebagai BodyM v1. TestB tidak dipakai dalam
pemilihan seed.

## Retrieval siluet

Setiap prediksi dibandingkan dengan 2.018 centroid subject training pada ruang
PCA 32 komponen. Dua belas tetangga terdekat membentuk estimasi lokal dan
residual berbobot jarak. Koreksi hanya aktif jika menurunkan MAE validation.

- Betis: residual lokal kekuatan 0,50.
- Tinggi: residual lokal kekuatan 0,75.
- Panjang kaki: residual lokal kekuatan 0,50.
- Pinggang: residual lokal kekuatan 1,00.
- Sepuluh indikator lain tetap memakai prediksi dasar.

Validation macro MAE turun dari 1,177 cm menjadi 1,165 cm. Pada testB yang
terpisah, macro MAE turun dari 1,637 cm menjadi 1,633 cm. Artifact hanya lolos
verifikasi jika hasil hybrid tidak lebih buruk pada kedua evaluasi tersebut.

## Kalibrasi

Interval memakai split-conformal absolute residual pada 87 subject validation.
Empirical validation coverage yang diperoleh adalah 81,61%, 91,95%, dan
96,55% untuk coverage nominal 80%, 90%, dan 95%.

Pada final testB, macro coverage interval 90% adalah 82,21%, di bawah nominal
90%. Artinya confidence hanya boleh dijelaskan sebagai coverage empiris BodyM,
bukan peluang bahwa foto pengguna pasti tepat.

## Guardrail

- OOD: StandardScaler, PCA 32 komponen, dan jarak 8-nearest-neighbor.
- Di atas kuantil validation 95% diberi status `review`.
- Di atas kuantil validation 99% diberi status `rejected`.
- Plausibility memakai rentang target training ditambah margin error 95%.
- Tidak ada silent clipping; prediksi mentah tetap disimpan bersama kode error.

Pada 1.150 baris testB, 990 diterima, 96 perlu review, dan 64 ditolak. Guarded
inference memiliki p95 5,710 ms pada pengujian lokal.

## Artifact

Artifact lokal berada di:

```text
C:\laragon\www\Jasa_jahit\datasets\bodym\models\phase-4-hybrid-stable\
|-- bodym-v1.joblib
|-- bodym-v1.metadata.json
|-- MODEL_CARD_BODYM_V1.md
`-- phase-4-report.json
```

Model bundle berukuran 2.883.194 byte dengan SHA-256
`4acbebdada78c25dc1987002e3320302265df42215884831b4c0189a7c428d91`.

## Reproduksi

```powershell
python tools/bodym_finalize.py finalize `
  --matrix C:\laragon\www\Jasa_jahit\datasets\bodym\processed\bodym-features-v1.csv `
  --manifest C:\laragon\www\Jasa_jahit\datasets\bodym\processed\bodym-features-v1.manifest.json `
  --phase3-report C:\laragon\www\Jasa_jahit\datasets\bodym\models\phase-3\phase-3-report.json `
  --output-dir C:\laragon\www\Jasa_jahit\datasets\bodym\models\phase-4-hybrid-stable

python tools/bodym_finalize.py verify `
  --report C:\laragon\www\Jasa_jahit\datasets\bodym\models\phase-4-hybrid-stable\phase-4-report.json
```

## Batasan

Metrik berasal dari siluet BodyM terkontrol. Akurasi foto nyata masih bergantung
pada segmentasi, pose, perspektif, dan skala A4/KTP. Integrasi tersebut menjadi
ruang lingkup Fase 5 dan tetap membutuhkan evaluasi ground truth foto lokal.
