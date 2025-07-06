# Server Inferensi YOLO untuk Deteksi Kanopi Durian

Server ini menyediakan API untuk deteksi kanopi pohon durian menggunakan model YOLO. Server ini menerima gambar aerial dan geometri plantation dari Laravel, melakukan clipping gambar berdasarkan geometri, dan mendeteksi pohon dengan metode sliding window.

## Persyaratan

- Python 3.8+
- CUDA (opsional, untuk akselerasi GPU)
- Model YOLO terlatih (`best.pt`)

## Instalasi

1. Buat virtual environment Python:
```bash
python -m venv venv
source venv/bin/activate  # Linux/Mac
venv\Scripts\activate     # Windows
```

2. Install dependensi:
```bash
pip install -r requirements.txt
```

3. Pastikan model YOLO (`best.pt`) tersedia di direktori ini.

## Menjalankan Server

Jalankan server dengan perintah:
```bash
python run_yolo_server.py --port 5000 --model best.pt
```

Parameter:
- `--port`: Port untuk menjalankan server (default: 5000)
- `--debug`: Jalankan dalam mode debug
- `--model`: Path ke model YOLO (default: best.pt)

## API Endpoints

### GET /status
Memeriksa status server.

### POST /yolo-inference
Endpoint utama untuk inferensi YOLO.

Payload:
```json
{
  "image_base64": "base64_encoded_image",
  "plantation_geojson": "geojson_polygon",
  "model_path": "best.pt",
  "confidence_threshold": 0.5,
  "overlap_percentage": 0.2,
  "tile_size": 1024,
  "edge_margin": 0.1
}
```

Response:
```json
{
  "type": "FeatureCollection",
  "features": [...],
  "processing_time": 123.45,
  "created_at": "2023-05-20T12:34:56",
  "logs": [...],
  "metadata": {...}
}
```

## Integrasi dengan Laravel

Server ini didesain untuk bekerja dengan sistem Laravel. Pastikan Laravel dikonfigurasi dengan benar untuk mengirim request ke server ini. Lihat konfigurasi di file `.env` Laravel:

```
PYTHON_API_URL=http://127.0.0.1:5000/yolo-inference
YOLO_MODEL_PATH=best.pt
YOLO_OVERLAP_PERCENTAGE=0.2
YOLO_CONFIDENCE_THRESHOLD=0.5
YOLO_TILE_SIZE=1024
YOLO_EDGE_MARGIN=0.1
``` 
