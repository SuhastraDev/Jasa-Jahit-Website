"""
main.py — FastAPI entry point for ZRINTTAILOR CV Measurement Service.

Endpoint:
    POST /measure — Accept front, side, back photos + reference info, return measurements.
    GET  /health  — Health check endpoint.
"""
from fastapi import FastAPI, File, UploadFile, Form, HTTPException
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


@app.get("/health")
async def health_check():
    return {"status": "ok", "service": "zrinttailor-cv"}


@app.post("/measure")
async def measure(
    front_photo: UploadFile = File(..., description="Foto badan tampak depan"),
    side_photo: UploadFile = File(..., description="Foto badan tampak samping"),
    back_photo: UploadFile = File(..., description="Foto badan tampak belakang"),
    ref_object: str = Form(..., description="Jenis marker referensi: aruco_a4, checkerboard_a4, a4, custom"),
    ref_width_cm: float = Form(None, description="Lebar benda referensi (cm) jika custom"),
    ref_height_cm: float = Form(None, description="Tinggi benda referensi (cm) jika custom"),
):
    """
    Analyze body photo and estimate measurements.

    - **front_photo**: Image file of user standing upright, front view
    - **side_photo**: Image file of user standing upright, side view
    - **back_photo**: Image file of user standing upright, back view
    - **ref_object**: Type of reference marker ('aruco_a4', 'checkerboard_a4', 'a4', 'custom')
    - **ref_width_cm**: Width in cm (required if ref_object is 'custom')
    - **ref_height_cm**: Height in cm (required if ref_object is 'custom')
    """
    # Validate ref_object
    if ref_object not in ("aruco_a4", "checkerboard_a4", "a4", "custom"):
        raise HTTPException(status_code=422, detail="ref_object harus 'aruco_a4', 'checkerboard_a4', 'a4', atau 'custom'")

    if ref_object == "custom" and (not ref_width_cm or not ref_height_cm):
        raise HTTPException(status_code=422, detail="Custom reference memerlukan ref_width_cm dan ref_height_cm")

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
        )
        return result
    except Exception as e:
        return {
            "success": False,
            "error": f"Terjadi kesalahan saat memproses gambar: {str(e)}"
        }


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
