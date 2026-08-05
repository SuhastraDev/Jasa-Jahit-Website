# Fase 5 - Integrasi Inference FastAPI

## Tujuan

Fase ini menghubungkan artifact final `bodym-v1.joblib` ke service FastAPI. Skala dari A4/KTP dan siluet depan-samping diubah menjadi 224 fitur beku, lalu model menghasilkan 14 ukuran BodyM beserta interval prediksi, confidence empiris, dan diagnostic code.

## Alur Runtime

1. OpenCV memvalidasi A4/KTP dan menghasilkan skala pixel per cm untuk setiap foto.
2. Segmentasi menghasilkan mask tubuh tampak depan dan samping.
3. `bodym_inference.build_bodym_features` menormalisasi mask dan menyusun tepat 224 fitur sesuai `bodym-preprocess.v1`.
4. `BodyMModelService` memuat artifact sekali secara thread-safe dan memeriksa versi model, kontrak, preprocessing, urutan fitur, serta urutan target.
5. Guardrail memberi status `accepted`, `review`, atau `rejected`. Prediksi tidak pernah dibatasi diam-diam.
6. Hasil 14 ukuran tersedia di `bodym_data`; hanya ukuran yang maknanya sama yang dipetakan ke field lama.

## Konfigurasi

Artifact model sengaja tidak masuk Git karena merupakan hasil pelatihan. Salin artifact terverifikasi ke server, misalnya:

```text
/var/www/Jasa-Jahit-Website/python-cv/models/bodym-v1.joblib
```

Atur environment service Python:

```dotenv
BODYM_ENABLED=true
BODYM_MODEL_VERSION=bodym-v1
BODYM_MODEL_PATH=/var/www/Jasa-Jahit-Website/python-cv/models/bodym-v1.joblib
```

Restart service Python setelah perubahan. Jangan mengaktifkan `BODYM_ENABLED` jika artifact belum tersedia atau health check gagal.

## Pemeriksaan Health

`GET /health` harus menampilkan:

```json
{
  "status": "ok",
  "service_version": "2.0.0",
  "bodym": {
    "loaded": true,
    "available": true,
    "model_version": "bodym-v1",
    "feature_count": 224,
    "target_count": 14,
    "load_error": null
  }
}
```

Endpoint `POST /bodym/predict` menerima satu vektor 224 fitur. Endpoint utama `/measure` tetap menerima tiga foto dan menjalankan pipeline skala, siluet, fitur, serta inference secara otomatis.

## Kontrak Hasil

- `response_contract_version`: `bodym-response.v1`
- `measurement_method`: `bodym_ml` saat model aktif
- `bodym_data`: seluruh 14 prediksi dengan nama semantik BodyM
- `bodym_prediction_intervals_cm`: interval per ukuran
- `bodym_per_field_confidence`: confidence empiris per ukuran
- `photo_diagnostics`: diagnosis `front`, `side`, dan `back`
- `legacy_fallback_fields`: field tampilan lama yang belum memiliki padanan semantik BodyM

Field BodyM yang tidak identik dengan field lama tidak dipaksakan. Contohnya `forearm_girth`, `shoulder_to_crotch`, dan `leg_length` tetap tersedia di `bodym_data`, bukan disalin ke ukuran lain yang artinya berbeda.

## Progres Analisis

Pipeline melaporkan tahap nyata: validasi foto, deteksi benda patokan, pose, segmentasi, ekstraksi fitur BodyM, inference BodyM, dan finalisasi hasil. Jika gagal, response menyebut `failed_view`, `failed_reason`, dan `correction` agar pengguna tahu foto depan, samping, atau belakang yang perlu diperbaiki.

## Verifikasi Lokal

Smoke test artifact final berhasil memuat `bodym-v1`, 224 fitur, dan 14 target. Vektor nol ditolak sebagai OOD dan implausible dengan `silent_clipping=false`. Unit test API memakai stub untuk modul CV; pengujian OpenCV/MediaPipe nyata memerlukan runtime yang memasang dependency tersebut.
