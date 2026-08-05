# Model Card BodyM v1

BodyM v1 adalah MLP ringan untuk 14 indikator ukuran tubuh dari 224 fitur
siluet. Model memakai seed `20260805`, validation macro MAE 1,177 cm, dan final
test macro MAE 1,637 cm pada dataset BodyM terkontrol.

Confidence adalah empirical coverage residual validation. Interval nominal 90%
memiliki coverage validation 91,95%, tetapi hanya 82,56% pada testB. Karena itu
confidence bukan jaminan akurasi untuk satu foto pengguna.

Guardrail memakai PCA dan jarak k-nearest-neighbor untuk OOD serta rentang
training yang diperluas error band untuk plausibility. Sistem tidak melakukan
silent clipping: angka mentah tetap tersedia dan hasil bermasalah ditandai
`review` atau `rejected` beserta kode diagnosis.

Model ditujukan untuk penelitian ZRINTTAILOR. Foto produksi tetap wajib melalui
validasi segmentasi, pose, perspektif, skala A4/KTP, serta verifikasi penjahit.
