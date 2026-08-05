# BodyM Fase 3: Baseline dan Model Regresi

## Tujuan

Fase 3 membandingkan baseline sederhana dengan beberapa model regresi ringan
untuk 14 target BodyM. Model dipilih hanya dari validation split. Final test
tidak dipakai untuk memilih algoritma atau konfigurasi.

Fase ini belum mengaktifkan model pada FastAPI atau aplikasi Laravel.

## Artifact

Kode yang masuk repository:

- `python-cv/bodym_modeling.py`: loader anti-leakage, baseline, kandidat,
  evaluasi, seleksi model, dan verifier artifact.
- `tools/bodym_train.py`: CLI training dan verifikasi.
- `python-cv/tests/test_bodym_modeling.py`: test split, metrik, eksperimen, dan
  artifact.
- `docs/bodym/phase-3-summary-v1.json`: ringkasan metrik yang dibekukan.
- `docs/bodym/phase-3-validation-mae-v1.csv`: MAE validation per indikator
  untuk seluruh baseline dan kandidat.

Artifact besar/hasil training disimpan di luar Git:

```text
C:\laragon\www\Jasa_jahit\datasets\bodym\models\phase-3\
|-- bodym-phase3-selected.joblib
|-- phase-3-metrics.csv
`-- phase-3-report.json
```

## Kebijakan split dan anti-leakage

Split resmi BodyM dipakai sebagai berikut:

| Fungsi | Split sumber | Baris | Subject |
| --- | --- | ---: | ---: |
| Training | train | 6.132 | 2.018 |
| Pemilihan model | testA | 1.684 | 87 |
| Final test | testB | 1.150 | 399 |

Audit menemukan satu subject ID berada pada testA dan testB. Subject tersebut
dipertahankan pada testA sesuai urutan prioritas split, sedangkan tujuh fotonya
dikeluarkan dari testB. Setelah kebijakan ini diterapkan, jumlah subject lintas
split adalah nol.

Model dilatih per foto, tetapi metrik utama dihitung setelah prediksi seluruh
foto milik subject yang sama dirata-ratakan. Ini mencegah subject dengan banyak
foto memperoleh bobot lebih besar. Metrik row-level tetap tersedia di laporan
lokal sebagai diagnosis kestabilan antar foto.

## Model yang dibandingkan

Semua eksperimen memakai seed `20260805` dan 224 fitur Fase 2.

| Kelompok | Model | Validation macro MAE |
| --- | --- | ---: |
| Baseline | Median per target | 4,058 cm |
| Baseline | Nearest-neighbor | 1,925 cm |
| Kandidat | Random Forest | 1,618 cm |
| Kandidat | Extra Trees | 1,605 cm |
| Kandidat | HistGradientBoosting | 1,234 cm |
| Kandidat | MLP ringan | **1,177 cm** |

MLP dipilih karena memiliki subject-level macro MAE terendah pada testA.
Hasilnya 38,84% lebih baik daripada baseline validation terbaik dan mengalahkan
baseline terbaik pada 14 dari 14 indikator.

## Final test

Setelah pemilihan model selesai, MLP dievaluasi satu kali pada testB bersih.
Subject-level macro MAE final adalah **1,637 cm**.

| Target | MAE | RMSE | Bias |
| --- | ---: | ---: | ---: |
| Lingkar pergelangan kaki | 0,842 | 1,105 | -0,161 |
| Panjang lengan | 1,004 | 1,439 | -0,132 |
| Lingkar lengan atas | 1,591 | 2,110 | -0,717 |
| Lingkar betis | 1,266 | 1,708 | -0,211 |
| Lingkar dada | 2,876 | 4,064 | -0,108 |
| Lingkar lengan bawah | 1,011 | 1,314 | -0,337 |
| Tinggi badan | 1,865 | 3,357 | -0,645 |
| Lingkar pinggul | 2,597 | 3,560 | -0,665 |
| Panjang kaki | 1,446 | 2,190 | 0,002 |
| Lebar bahu | 0,910 | 1,227 | -0,237 |
| Bahu ke pesak | 1,383 | 2,033 | -0,106 |
| Lingkar paha | 2,031 | 2,706 | -0,587 |
| Lingkar pinggang | 3,471 | 4,805 | 1,111 |
| Lingkar pergelangan tangan | 0,627 | 0,824 | -0,124 |

Bias didefinisikan sebagai `prediksi - ground truth`. Nilai positif berarti
model cenderung melebihkan ukuran, sedangkan nilai negatif berarti cenderung
mengecilkan ukuran.

## Reproduksi

Install dependency dari `python-cv/requirements.txt`, lalu jalankan:

```powershell
python tools/bodym_train.py train `
  --matrix C:\laragon\www\Jasa_jahit\datasets\bodym\processed\bodym-features-v1.csv `
  --manifest C:\laragon\www\Jasa_jahit\datasets\bodym\processed\bodym-features-v1.manifest.json `
  --output-dir C:\laragon\www\Jasa_jahit\datasets\bodym\models\phase-3

python tools/bodym_train.py verify `
  --report C:\laragon\www\Jasa_jahit\datasets\bodym\models\phase-3\phase-3-report.json
```

Selected model berukuran 432.683 byte dengan SHA-256
`ac8b8e79302b363f37fdda1b951239dc4569ca413d617712cdd6f92d4f03eb07`.
Artifact membawa versi kontrak, versi preprocessing, hash matrix, urutan fitur,
urutan target, dan estimator.

## Batasan

- Angka ini adalah performa pada siluet BodyM terkontrol, bukan bukti akurasi
  foto pengguna ZRINTTAILOR.
- Validation hanya berisi 87 subject meskipun memiliki 1.684 foto. TestB yang
  lebih besar tetap menunjukkan kenaikan MAE dari 1,177 cm menjadi 1,637 cm.
- Fitur fisik training memperoleh skala dari tinggi ground truth BodyM. Pada
  produksi, skala harus berasal dari A4/KTP dan kesetaraannya perlu diuji.
- Pakaian, latar, pose, segmentasi, perspektif, dan ROI A4/KTP dapat menambah
  domain gap yang belum direpresentasikan oleh metrik ini.
- Confidence belum dibuat pada fase ini. Fase 4 wajib mengkalibrasinya dari
  residual nyata, bukan dari angka tetap.

## Keputusan Fase 3

Acceptance criteria terpenuhi: tidak ada subject lintas split setelah kebijakan
eksklusi, beberapa kandidat dilatih secara reproducible, dan MLP mengalahkan
baseline terbaik pada mayoritas indikator (14 dari 14). MLP menjadi kandidat
terpilih untuk kalibrasi dan model card pada Fase 4, bukan langsung model
produksi.
