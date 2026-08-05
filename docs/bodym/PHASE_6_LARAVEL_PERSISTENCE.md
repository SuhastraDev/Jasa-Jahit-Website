# Phase 6 - Laravel Persistence

Fase ini menghubungkan response BodyM dari FastAPI ke Laravel agar hasil ML tidak hanya tampil sementara, tetapi tersimpan sebagai kontrak data resmi.

## Hasil Implementasi

- Tabel `measurements` mendapat kolom `bodym_*` untuk 14 indikator BodyM.
- Metadata kontrak ikut disimpan:
  - `bodym_contract_version`
  - `bodym_response_contract_version`
  - `bodym_model_version`
  - `bodym_status`
- JSON diagnostik ikut disimpan:
  - `bodym_data`
  - `bodym_per_field_confidence`
  - `bodym_prediction_intervals_cm`
  - `bodym_diagnostics`
- Controller menampilkan hasil BodyM resmi di halaman hasil analisis.
- Field BodyM dapat diedit manual sebelum disimpan.
- Riwayat menampilkan label metode `BodyM ML` dan versi kontrak/model jika tersedia.

## Mapping Aman Ke Field Jahit

Field BodyM yang memiliki padanan langsung tetap diisi ke tampilan jahit:

| BodyM | Field jahit |
| --- | --- |
| `chest_girth` | `chest` |
| `waist_girth` | `waist` dan `pants_waist` |
| `hip_girth` | `hips` dan `pants_hips` |
| `shoulder_breadth` | `shoulder_width` |
| `arm_length` | `arm_length` |
| `bicep_girth` | `upper_arm` |
| `wrist_girth` | `wrist` |
| `height` | `height` |
| `thigh_girth` | `thigh` |
| `calf_girth` | `calf` |
| `ankle_girth` | `ankle` |

Field `forearm_girth`, `leg_length`, dan `shoulder_to_crotch` tetap disimpan sebagai indikator BodyM resmi, tetapi tidak dipaksa menjadi field jahit lain karena maknanya tidak identik dengan pola baju/celana yang sudah ada.

## Verifikasi

- `php artisan test --filter=BodyMContractTest`
- `php artisan test --filter=MeasurementMultiviewTest`
- `php artisan test`
- `php artisan view:cache`
- `php artisan route:cache`
- `php artisan config:cache`
- `git diff --check`

Catatan: `php artisan migrate --pretend` tidak dapat dijalankan di mesin lokal saat verifikasi karena MySQL lokal di `127.0.0.1:3306` menolak koneksi. Migration tetap tervalidasi lewat `RefreshDatabase` pada test suite.
