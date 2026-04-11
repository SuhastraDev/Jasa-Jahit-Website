"""
main.py — FastAPI entry point for ZRINTTAILOR CV Measurement Service.

Endpoint:
    POST /measure — Accept body photo + reference info, return measurements.
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
    body_photo: UploadFile = File(..., description="Foto badan user"),
    ref_object: str = Form(..., description="Jenis benda referensi: a4, atm, custom"),
    ref_width_cm: float = Form(None, description="Lebar benda referensi (cm) jika custom"),
    ref_height_cm: float = Form(None, description="Tinggi benda referensi (cm) jika custom"),
):
    """
    Analyze body photo and estimate measurements.

    - **body_photo**: Image file (JPG, PNG) of user standing upright
    - **ref_object**: Type of reference object ('a4', 'atm', 'custom')
    - **ref_width_cm**: Width in cm (required if ref_object is 'custom')
    - **ref_height_cm**: Height in cm (required if ref_object is 'custom')
    """
    # Validate ref_object
    if ref_object not in ("a4", "atm", "custom"):
        raise HTTPException(status_code=422, detail="ref_object harus 'a4', 'atm', atau 'custom'")

    if ref_object == "custom" and (not ref_width_cm or not ref_height_cm):
        raise HTTPException(status_code=422, detail="Custom reference memerlukan ref_width_cm dan ref_height_cm")

    # Validate file type
    if body_photo.content_type not in ("image/jpeg", "image/png", "image/webp"):
        raise HTTPException(status_code=422, detail="Format gambar harus JPG, PNG, atau WEBP")

    try:
        image_bytes = await body_photo.read()
        result = process_measurement(image_bytes, ref_object, ref_width_cm, ref_height_cm)
        return result
    except Exception as e:
        return {
            "success": False,
            "error": f"Terjadi kesalahan saat memproses gambar: {str(e)}"
        }


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
