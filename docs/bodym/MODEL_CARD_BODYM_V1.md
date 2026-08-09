# Model Card BodyM v1

## Ringkasan

BodyM v1 memakai regresi MLP ringan, pencarian centroid siluet terdekat, dan
koreksi residual tervalidasi untuk memprediksi 14 indikator ukuran tubuh dari
224 fitur siluet terstruktur. Model dasar memakai seed
`20260805` dan koreksi dipilih hanya dari validation split BodyM.

Model ini belum merupakan bukti akurasi foto pengguna ZRINTTAILOR. Angka di
bawah berlaku untuk dataset BodyM terkontrol dan pipeline fitur Fase 2.

## Data dan split

- Training: 2018 subject.
- Validation/kalibrasi: 87 subject.
- Final test: 399 subject.
- Pemilihan seed menggunakan validation; testB tidak dipakai untuk seleksi.
- Subject lintas split setelah kebijakan eksklusi: 0.

## Pemilihan model

- Kandidat: MLP dengan arsitektur yang sama pada 3 seed.
- Validation macro MAE terpilih: 1.165 cm.
- Validation macro MAE model dasar: 1.177 cm.
- Rata-rata antar seed: 1.204 cm.
- Deviasi standar antar seed: 0.028 cm.
- Model-only latency p95: 3.820 ms.
- Ukuran estimator tanpa kompresi: 1508382 byte.

## Retrieval dan koreksi residual

- Referensi retrieval: 2018 centroid subject training.
- Neighbor per prediksi: 12.
- Target dengan koreksi aktif: calf_girth, height, leg_length, waist_girth.
- Target lain tetap memakai prediksi model dasar karena koreksi tidak memperbaiki MAE validation.

## Hasil final test

- Subject-level macro MAE: 1.633 cm.
- Subject-level macro RMSE: 2.314 cm.
- Subject-level macro absolute bias: 0.346 cm.

## Confidence dan interval

Confidence bukan angka buatan dan bukan probabilitas bahwa satu foto pengguna
pasti benar. Nilainya adalah empirical coverage pada validation BodyM. Interval
prediksi memakai split-conformal absolute residual untuk coverage nominal
80%, 90%, dan 95%. Jumlah subject kalibrasi: 87.

Pada testB, macro coverage aktual untuk interval 90% adalah
82.21%.
Nilai ini berada di bawah nominal 90%, sehingga interval wajib ditampilkan
sebagai estimasi error BodyM, bukan jaminan ketepatan foto pengguna.

## Guardrail

- OOD memakai StandardScaler, PCA, dan jarak k-nearest-neighbor terhadap data training.
- Jarak di atas kuantil validation 95% diberi status review.
- Jarak di atas kuantil validation 99% ditolak.
- Plausibility memakai rentang target training yang diperluas error band 95%.
- Prediksi tidak pernah di-clamp diam-diam; angka mentah tetap tersedia bersama diagnosis.

## Penggunaan yang dimaksud

Artifact ini ditujukan sebagai indikator ukuran berbasis BodyM untuk pipeline
penelitian. Integrasi produksi harus tetap memvalidasi kualitas segmentasi,
skala A4/KTP, pose, perspektif, dan domain gap foto nyata.

## Batasan utama

- BodyM berisi siluet terkontrol, bukan foto rumah dengan pakaian/latar bervariasi.
- Skala pada training berasal dari metadata tinggi BodyM; produksi memakai A4/KTP.
- Calibration coverage berlaku empiris pada BodyM, bukan jaminan individual.
- Dataset dan model tidak menggantikan verifikasi penjahit sebelum produksi.
