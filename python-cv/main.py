"""
main.py — FastAPI entry point for ZRINTTAILOR CV Measurement Service.

Endpoint:
    POST /measure — Accept front, side, back photos + reference info, return measurements.
    GET  /health  — Health check endpoint.
"""
import threading
import time
import uuid

from fastapi import BackgroundTasks, FastAPI, File, UploadFile, Form, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from measurement import process_measurement

app = FastAPI(
    title="ZRINTTAILOR CV Measurement Service",
    description="Estimasi ukuran badan menggunakan Computer Vision",
    version="1.0.0",
)

# Allow Laravel to call this service
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

measurement_jobs = {}
measurement_jobs_lock = threading.Lock()


def update_job(job_id, **values):
    with measurement_jobs_lock:
        if job_id in measurement_jobs:
            measurement_jobs[job_id].update(values)
            measurement_jobs[job_id]["updated_at"] = time.time()


def cleanup_jobs():
    cutoff = time.time() - 3600
    with measurement_jobs_lock:
        expired = [
            job_id
            for job_id, job in measurement_jobs.items()
            if job.get("updated_at", job.get("created_at", 0)) < cutoff
        ]
        for job_id in expired:
            measurement_jobs.pop(job_id, None)


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
    return {"status": "ok", "service": "zrinttailor-cv"}


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

    if reference_mode == "handheld" and ref_object != "a4":
        raise HTTPException(status_code=422, detail="Mode praktis hanya tersedia untuk A4")


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
        )
        return result
    except Exception as e:
        return {
            "success": False,
            "error": f"Terjadi kesalahan saat memproses gambar: {str(e)}"
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
    if reference_mode == "handheld" and ref_object != "a4":
        raise HTTPException(status_code=422, detail="Mode praktis hanya tersedia untuk A4")

    photos = (front_photo, side_photo, back_photo)
    for photo in photos:
        if photo.content_type not in ("image/jpeg", "image/png", "image/webp"):
            raise HTTPException(status_code=422, detail="Format gambar harus JPG, PNG, atau WEBP")

    cleanup_jobs()
    job_id = uuid.uuid4().hex
    now = time.time()
    with measurement_jobs_lock:
        measurement_jobs[job_id] = {
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
        }

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
        },
    )
    return {"success": True, "job_id": job_id, "status": "queued"}


@app.get("/measure/jobs/{job_id}")
async def measurement_job_status(job_id: str):
    cleanup_jobs()
    with measurement_jobs_lock:
        job = measurement_jobs.get(job_id)
        if job is None:
            raise HTTPException(status_code=404, detail="Pekerjaan analisis tidak ditemukan")
        return dict(job)


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
