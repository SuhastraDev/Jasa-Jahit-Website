# BodyM Fase 4: Model Final, Kalibrasi, dan Guardrail

## Tujuan

Fase 4 membekukan model BodyM v1 berdasarkan kestabilan antar seed, MAE,
latency, dan ukuran serialisasi. Confidence dihitung dari residual validation,
bukan konstanta UI. Prediksi yang berada di luar distribusi atau tidak masuk
akal ditolak dengan diagnosis, tanpa mengubah angka mentah secara diam-diam.

## Pemilihan model

Tiga MLP dengan konfigurasi identik dilatih ulang pada seed berbeda. Seluruh
seed memenuhi batas p95 latency 50 ms dan ukuran estimator 1 MB.

| Seed | Validation macro MAE | p95 model-only | Estimator |
| ---: | ---: | ---: | ---: |
| 20260803 | 1,218 cm | 3,820 ms | 471.991 byte |
| 20260804 | 1,250 cm | 2,382 ms | 472.103 byte |
| 20260805 | **1,177 cm** | **1,229 ms** | 472.039 byte |

Rata-rata MAE adalah 1,215 cm, deviasi standar 0,030 cm, dan rentang 0,072
cm. Seed `20260805` dibekukan sebagai BodyM v1. TestB tidak dipakai dalam
pemilihan seed.

## Kalibrasi

Interval memakai split-conformal absolute residual pada 87 subject validation.
Empirical validation coverage yang diperoleh adalah 81,61%, 91,95%, dan
96,55% untuk coverage nominal 80%, 90%, dan 95%.

Pada final testB, macro coverage interval 90% adalah 82,56%, di bawah nominal
90%. Artinya confidence hanya boleh dijelaskan sebagai coverage empiris BodyM,
bukan peluang bahwa foto pengguna pasti tepat.

## Guardrail

- OOD: StandardScaler, PCA 32 komponen, dan jarak 8-nearest-neighbor.
- Di atas kuantil validation 95% diberi status `review`.
- Di atas kuantil validation 99% diberi status `rejected`.
- Plausibility memakai rentang target training ditambah margin error 95%.
- Tidak ada silent clipping; prediksi mentah tetap disimpan bersama kode error.

Pada 1.150 baris testB, 990 diterima, 96 perlu review, dan 64 ditolak. Guarded
inference memiliki p95 6,362 ms pada pengujian lokal.

## Artifact

Artifact lokal berada di:

```text
C:\laragon\www\Jasa_jahit\datasets\bodym\models\phase-4\
|-- bodym-v1.joblib
|-- bodym-v1.metadata.json
|-- MODEL_CARD_BODYM_V1.md
`-- phase-4-report.json
```

Model bundle berukuran 1.988.210 byte dengan SHA-256
`bbc12f57766076ed12fb914b168f1f9df63de96506fbb82390ff4c74b4f5f247`.

## Reproduksi

```powershell
python tools/bodym_finalize.py finalize `
  --matrix C:\laragon\www\Jasa_jahit\datasets\bodym\processed\bodym-features-v1.csv `
  --manifest C:\laragon\www\Jasa_jahit\datasets\bodym\processed\bodym-features-v1.manifest.json `
  --phase3-report C:\laragon\www\Jasa_jahit\datasets\bodym\models\phase-3\phase-3-report.json `
  --output-dir C:\laragon\www\Jasa_jahit\datasets\bodym\models\phase-4

python tools/bodym_finalize.py verify `
  --report C:\laragon\www\Jasa_jahit\datasets\bodym\models\phase-4\phase-4-report.json
```

## Batasan

Metrik berasal dari siluet BodyM terkontrol. Akurasi foto nyata masih bergantung
pada segmentasi, pose, perspektif, dan skala A4/KTP. Integrasi tersebut menjadi
ruang lingkup Fase 5 dan tetap membutuhkan evaluasi ground truth foto lokal.
