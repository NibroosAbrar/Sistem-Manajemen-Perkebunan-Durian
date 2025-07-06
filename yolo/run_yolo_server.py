#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
Menjalankan server YOLO menggunakan Flask
"""
import argparse
import base64
import io
import json
import os
import sys
import time
from datetime import datetime, timedelta
from pathlib import Path

from flask import Flask, jsonify, request
from flask_cors import CORS

# Coba import fungsi dari modul, gunakan fallback jika gagal
try:
    from NEW_inferensi_model_dari_gpt import process_image_from_api
except ImportError:
    try:
        # Fallback jika nama modul berbeda
        from NEW import process_image_from_api
    except ImportError:
        print("❌ Error: Module NEW_inferensi_model_dari_gpt atau NEW tidak ditemukan")
        sys.exit(1)

# Default model path
DEFAULT_MODEL_PATH = "best14.pt"

# Mencatat waktu server dimulai
SERVER_START_TIME = time.time()

# Inisialisasi Flask app
app = Flask(__name__)
CORS(app)  # Mengaktifkan CORS untuk semua rute

# Kelas untuk pemrosesan YOLO
class_names = {
    0: "pohon_sehat",
    1: "pohon_sakit",
    2: "pohon_mati"
}

# Fungsi untuk memformat waktu uptime
def format_uptime(seconds):
    """Format durasi uptime ke dalam bentuk yang mudah dibaca"""
    delta = timedelta(seconds=seconds)
    days = delta.days
    hours, remainder = divmod(delta.seconds, 3600)
    minutes, seconds = divmod(remainder, 60)

    parts = []
    if days > 0:
        parts.append(f"{days} hari")
    if hours > 0:
        parts.append(f"{hours} jam")
    if minutes > 0:
        parts.append(f"{minutes} menit")
    if seconds > 0 or not parts:
        parts.append(f"{seconds} detik")

    return ", ".join(parts)

# Root endpoint untuk health check
@app.route("/")
def health_check():
    """Endpoint untuk memeriksa apakah server berjalan"""
    return jsonify({
        "status": "healthy",
        "message": "YOLO server is running",
        "time": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    })

# Status endpoint untuk melihat informasi server
@app.route("/status", methods=["GET"])
def status():
    """Endpoint untuk memeriksa status server"""
    uptime_seconds = time.time() - SERVER_START_TIME
    return jsonify({
        "status": "OK",
        "version": "1.0.0",
        "model_path": os.getenv("YOLO_MODEL_PATH", DEFAULT_MODEL_PATH),
        "uptime": format_uptime(uptime_seconds),
        "uptime_seconds": uptime_seconds,
        "current_time": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    })

# Endpoint utama untuk inferensi YOLO
@app.route("/yolo-inference", methods=["POST"])
def yolo_inference():
    """Endpoint utama untuk menjalankan inferensi YOLO pada gambar"""
    start_time = time.time()

    # Validasi data JSON yang diterima
    if not request.is_json:
        return jsonify({
            "status": "error",
            "message": "Request harus dalam format JSON",
            "time": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        }), 400

    data = request.get_json()

    # Verifikasi input gambar (base64_image atau image_base64)
    image_path = data.get("image_path")
    base64_image = data.get("base64_image")
    image_base64 = data.get("image_base64")  # Support untuk format lama

    # Pilih yang tidak kosong, dengan preferensi base64_image
    base64_data = base64_image or image_base64

    if not image_path and not base64_data:
        return jsonify({
            "status": "error",
            "message": "Harus menyediakan image_path, base64_image, atau image_base64",
            "time": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        }), 400

    # Verifikasi data GeoJSON kebun
    plantation_geojson = data.get("plantation_geojson")
    if not plantation_geojson:
        return jsonify({
            "status": "error",
            "message": "Data GeoJSON kebun (plantation_geojson) harus disediakan",
            "time": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        }), 400

    # Path model YOLO
    model_path = data.get("model_path", os.getenv("YOLO_MODEL_PATH", DEFAULT_MODEL_PATH))

    # Custom class names jika disediakan
    custom_class_names = data.get("class_names", class_names)

    # Log request yang diterima
    print(f"🔍 Menerima request inferensi YOLO pada {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")

    try:
        # Persiapkan data gambar
        image_bytes = None

        if image_path:
            # Jika path gambar diberikan, baca file
            print(f"📂 Membaca gambar dari path: {image_path}")
            with open(image_path, "rb") as f:
                image_bytes = f.read()
        else:
            # Jika base64 diberikan, decode
            print(f"📤 Mendekode gambar base64 ({len(base64_data) // 1024} KB)")
            try:
                # Hapus header data URI jika ada
                if "base64," in base64_data:
                    base64_data = base64_data.split("base64,")[1]

                image_bytes = base64.b64decode(base64_data)
            except Exception as e:
                return jsonify({
                    "status": "error",
                    "message": f"Gagal mendekode gambar base64: {str(e)}",
                    "time": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                }), 400

        # Eksekusi inferensi YOLO
        print(f"🧠 Menjalankan inferensi YOLO dengan model: {model_path}")
        result = process_image_from_api(
            image_bytes=image_bytes,
            plantation_geojson=plantation_geojson,
            model_path=model_path,
            class_names=custom_class_names
        )

        # Tambahkan informasi waktu pemrosesan
        processing_time = time.time() - start_time
        result["processing_time"] = processing_time
        result["processing_time_formatted"] = f"{processing_time:.2f} detik"

        print(f"✅ Inferensi selesai dalam {processing_time:.2f} detik dengan {len(result.get('features', []))} objek terdeteksi")

        return jsonify(result)

    except Exception as e:
        import traceback
        error_detail = traceback.format_exc()

        print(f"❌ Error dalam inferensi YOLO: {str(e)}")
        print(f"❌ Detail: {error_detail}")

        return jsonify({
            "status": "error",
            "message": f"Terjadi kesalahan saat menjalankan inferensi: {str(e)}",
            "detail": error_detail,
            "time": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        }), 500

if __name__ == "__main__":
    # Parse argumen baris perintah
    parser = argparse.ArgumentParser(description='Run YOLO inference server')
    parser.add_argument('--port', type=int, default=5000, help='Port untuk menjalankan server (default: 5000)')
    parser.add_argument('--host', type=str, default='0.0.0.0', help='Host address (default: 0.0.0.0)')
    parser.add_argument('--model', type=str, default=DEFAULT_MODEL_PATH, help=f'Path ke model YOLO (default: {DEFAULT_MODEL_PATH})')

    args = parser.parse_args()

    # Set environment variable untuk model path
    os.environ["YOLO_MODEL_PATH"] = args.model

    print(f"🚀 Memulai server YOLO pada http://{args.host}:{args.port}")
    print(f"📄 Menggunakan model: {args.model}")

    # Jalankan server Flask
    app.run(host=args.host, port=args.port, debug=False)
