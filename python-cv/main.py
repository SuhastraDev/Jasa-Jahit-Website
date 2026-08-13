"""
main.py — FastAPI entry point for ZRINTTAILOR CV Measurement Service.

Endpoint:
    POST /measure — Accept front, side, back photos + reference info, return measurements.
    GET  /health  — Health check endpoint.
"""
import time
import uuid
from pathlib import Path
from typing import Literal

from diskcache import Cache
from fastapi import BackgroundTasks, FastAPI, File, UploadFile, Form, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from bodym_inference import BodyMInferenceError, get_bodym_service
from bodym_preprocessing import feature_names
from garment_measurement import process_garment_measurement
from measurement import process_measurement

app = FastAPI(
    title="ZRINTTAILOR CV Measurement Service",
    description="Estimasi ukuran badan menggunakan Computer Vision",
    version="2.0.0",
)

# Allow Laravel to call this service
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# Persisted to disk (not in-process memory) so in-flight jobs survive a
# service restart (e.g. the deploy pipeline restarting zrinttailor-cv).
JOB_CACHE_DIR = Path(__file__).resolve().parent / ".job_cache"
JOB_TTL_SECONDS = 3600
measurement_jobs = Cache(str(JOB_CACHE_DIR))


class BodyMFeatureRequest(BaseModel):
    features: list[float] = Field(..., min_length=len(feature_names()), max_length=len(feature_names()))
    coverage: Literal[0.80, 0.90, 0.95] = 0.90


def update_job(job_id, **values):
    job = measurement_jobs.get(job_id)
    if job is not None:
        job.update(values)
        job["updated_at"] = time.time()
        measurement_jobs.set(job_id, job, expire=JOB_TTL_SECONDS)


def cleanup_jobs():
    measurement_jobs.expire()


INTERRUPTED_JOB_MESSAGE = "Analisis terhenti karena server sedang diperbarui. Silakan ulangi proses pengukuran."


@app.on_event("startup")
def recover_interrupted_jobs():
    """Jobs left 'queued'/'processing' belonged to a background task on the
    previous process — that task is gone after a restart and will never
    finish, so mark them failed instead of leaving clients polling forever.
    """
    for job_id in list(measurement_jobs.iterkeys()):
        job = measurement_jobs.get(job_id)
        if job and job.get("status") in ("queued", "processing"):
            job["status"] = "failed"
            job["error"] = INTERRUPTED_JOB_MESSAGE
            job["updated_at"] = time.time()
            measurement_jobs.set(job_id, job, expire=JOB_TTL_SECONDS)


def run_measurement_job(job_id, front_bytes, side_bytes, back_bytes, options):
    update_job(job_id, status="processing")

    def report(progress):
        update_job(job_id, progress=progress)

    try:
        result = process_measurement(
            front_bytes,
            side_bytes,
            back_bytes,
            options["ref_object"],
            options.get("ref_width_cm"),
            options.get("ref_height_cm"),
            options.get("reference_boxes"),
            report,
            reference_mode=options.get("reference_mode", "fixed"),
        )
        if result.get("success"):
            update_job(
                job_id,
                status="completed",
                progress={
                    "stage": "completed",
                    "percent": 100,
                    "message": "Analisis selesai dan hasil siap diperiksa",
                    "view": None,
                },
                result=result,
            )
        else:
            update_job(job_id, status="failed", error=result.get("error"), result=result)
    except Exception as exc:
        update_job(job_id, status="failed", error=f"Terjadi kesalahan saat memproses gambar: {exc}")


@app.get("/health")
async def health_check():
    return {
        "status": "ok",
        "service": "zrinttailor-cv",
        "service_version": app.version,
        "bodym": get_bodym_service().status(load=True),
    }


@app.post("/bodym/predict")
async def predict_bodym_features(payload: BodyMFeatureRequest):
    """Versioned inference endpoint for an already extracted BodyM feature vector."""
    try:
        return {
            "success": True,
            **get_bodym_service().predict_features(payload.features, coverage=payload.coverage),
        }
    except BodyMInferenceError as exc:
        raise HTTPException(
            status_code=503 if exc.code in ("model_file_missing", "model_load_failed") else 422,
            detail={"code": exc.code, "message": str(exc), "details": exc.details},
        ) from exc


@app.post("/measure")
async def measure(
    front_photo: UploadFile = File(..., description="Foto badan tampak depan"),
    side_photo: UploadFile = File(..., description="Foto badan tampak samping"),
    back_photo: UploadFile = File(..., description="Foto badan tampak belakang"),
    ref_object: str = Form(..., description="Jenis marker referensi: aruco_a4, checkerboard_a4, a4, ktp, custom"),
    ref_width_cm: float = Form(None, description="Lebar benda referensi (cm) jika custom"),
    ref_height_cm: float = Form(None, description="Tinggi benda referensi (cm) jika custom"),
    reference_mode: str = Form("fixed", description="Mode benda referensi: fixed atau handheld"),
    front_reference_box: str = Form(None, description="Koordinat manual benda patokan untuk foto depan"),
    side_reference_box: str = Form(None, description="Koordinat manual benda patokan untuk foto samping"),
    back_reference_box: str = Form(None, description="Koordinat manual benda patokan untuk foto belakang"),
):
    """
    Analyze body photo and estimate measurements.

    - **front_photo**: Image file of user standing upright, front view
    - **side_photo**: Image file of user standing upright, side view
    - **back_photo**: Image file of user standing upright, back view
    - **ref_object**: Type of reference marker ('aruco_a4', 'checkerboard_a4', 'a4', 'ktp', 'custom')
    - **ref_width_cm**: Width in cm (required if ref_object is 'custom')
    - **ref_height_cm**: Height in cm (required if ref_object is 'custom')
    """
    # Validate ref_object
    if ref_object not in ("a4", "ktp", "atm"):
        raise HTTPException(status_code=422, detail="ref_object harus 'a4' atau 'ktp'")

    if reference_mode not in ("fixed", "handheld"):
        raise HTTPException(status_code=422, detail="reference_mode harus 'fixed' atau 'handheld'")

    for photo in (front_photo, side_photo, back_photo):
        if photo.content_type not in ("image/jpeg", "image/png", "image/webp"):
            raise HTTPException(status_code=422, detail="Format gambar harus JPG, PNG, atau WEBP")

    try:
        result = process_measurement(
            await front_photo.read(),
            await side_photo.read(),
            await back_photo.read(),
            ref_object,
            ref_width_cm,
            ref_height_cm,
            {
                "front": front_reference_box,
                "side": side_reference_box,
                "back": back_reference_box,
            },
            reference_mode=reference_mode,
        )
        return result
    except Exception as e:
        return {
            "success": False,
            "error": f"Terjadi kesalahan saat memproses gambar: {str(e)}"
        }


@app.post("/measure/garment")
async def measure_garment(
    photo: UploadFile = File(..., description="Foto pakaian (baju/celana) rata di lantai/meja"),
    garment_type: str = Form(..., description="Jenis pakaian: 'shirt' atau 'pants'"),
    ref_object: str = Form(..., description="Jenis marker referensi: a4, ktp, custom"),
    ref_width_cm: float = Form(None, description="Lebar benda referensi (cm) jika custom"),
    ref_height_cm: float = Form(None, description="Tinggi benda referensi (cm) jika custom"),
    reference_box: str = Form(None, description="Koordinat manual benda patokan"),
):
    """
    Analyze a flat-lay garment photo (shirt or pants laid flat next to a
    reference marker) and estimate its measurements. Synchronous — no
    pose/segmentation model involved, so this is much lighter than /measure.

    - **photo**: Image of the garment laid flat, reference marker beside it
    - **garment_type**: 'shirt' or 'pants'
    - **ref_object**: Type of reference marker ('a4', 'ktp', 'custom')
    """
    if garment_type not in ("shirt", "pants"):
        raise HTTPException(status_code=422, detail="garment_type harus 'shirt' atau 'pants'")

    if ref_object not in ("a4", "ktp", "atm", "custom"):
        raise HTTPException(status_code=422, detail="ref_object harus 'a4', 'ktp', atau 'custom'")

    if photo.content_type not in ("image/jpeg", "image/png", "image/webp"):
        raise HTTPException(status_code=422, detail="Format gambar harus JPG, PNG, atau WEBP")

    try:
        result = process_garment_measurement(
            await photo.read(),
            garment_type,
            ref_object,
            ref_width_cm,
            ref_height_cm,
            reference_box,
        )
        return result
    except Exception as e:
        return {
            "success": False,
            "error": f"Terjadi kesalahan saat memproses gambar: {str(e)}",
        }


@app.post("/measure/jobs")
async def create_measurement_job(
    background_tasks: BackgroundTasks,
    front_photo: UploadFile = File(...),
    side_photo: UploadFile = File(...),
    back_photo: UploadFile = File(...),
    ref_object: str = Form(...),
    ref_width_cm: float = Form(None),
    ref_height_cm: float = Form(None),
    reference_mode: str = Form("fixed"),
    front_reference_box: str = Form(None),
    side_reference_box: str = Form(None),
    back_reference_box: str = Form(None),
):
    if ref_object not in ("a4", "ktp", "atm"):
        raise HTTPException(status_code=422, detail="ref_object harus 'a4' atau 'ktp'")
    if reference_mode not in ("fixed", "handheld"):
        raise HTTPException(status_code=422, detail="reference_mode harus 'fixed' atau 'handheld'")
    photos = (front_photo, side_photo, back_photo)
    for photo in photos:
        if photo.content_type not in ("image/jpeg", "image/png", "image/webp"):
            raise HTTPException(status_code=422, detail="Format gambar harus JPG, PNG, atau WEBP")

    cleanup_jobs()
    job_id = uuid.uuid4().hex
    now = time.time()
    measurement_jobs.set(
        job_id,
        {
            "job_id": job_id,
            "status": "queued",
            "progress": {
                "stage": "queued",
                "percent": 2,
                "message": "Foto diterima dan menunggu proses CV",
                "view": None,
            },
            "created_at": now,
            "updated_at": now,
        },
        expire=JOB_TTL_SECONDS,
    )

    background_tasks.add_task(
        run_measurement_job,
        job_id,
        await front_photo.read(),
        await side_photo.read(),
        await back_photo.read(),
        {
            "ref_object": ref_object,
            "ref_width_cm": ref_width_cm,
            "ref_height_cm": ref_height_cm,
            "reference_boxes": {
                "front": front_reference_box,
                "side": side_reference_box,
                "back": back_reference_box,
            },
            "reference_mode": reference_mode,
        },
    )
    return {"success": True, "job_id": job_id, "status": "queued"}


@app.get("/measure/jobs/{job_id}")
async def measurement_job_status(job_id: str):
    cleanup_jobs()
    job = measurement_jobs.get(job_id)
    if job is None:
        raise HTTPException(status_code=404, detail="Pekerjaan analisis tidak ditemukan")
    return dict(job)


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
