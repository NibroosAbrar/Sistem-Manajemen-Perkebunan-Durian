from ultralytics import YOLO
import rasterio
from rasterio.transform import Affine
import numpy as np
import cv2
import geopandas as gpd
from shapely.geometry import Polygon, box, MultiPolygon
from shapely.validation import make_valid
from tkinter import Tk
from tkinter.filedialog import askopenfilename
import os
from PIL import Image
import matplotlib.pyplot as plt
from tqdm import tqdm
import uuid
import shutil
from shapely.errors import TopologicalError
from shapely.ops import unary_union
import json
import base64
import io
import sys
import tempfile
from flask import Flask, request, jsonify
import time

# --- Parameter yang dapat dikontrol ---
# Overlap untuk sliding window
OVERLAP_PERCENTAGE = 0.2  # 20% overlap
# Margin tepi (persentase dari tile_size) untuk mengidentifikasi objek di tepi
EDGE_MARGIN_PERCENTAGE = 0.1  # 10% dari ukuran tile dianggap sebagai area tepi
# Jarak maksimum antara poligon yang terpotong (dalam satuan koordinat)
MAX_SNAP_DISTANCE = 0.000001  # Jarak maksimal untuk menggabungkan poligon terpotong

# --- Fungsi bantu ---
def choose_file():
    Tk().withdraw()
    return askopenfilename(title="Pilih file GeoTIFF", filetypes=[("GeoTIFF", "*.tif *.tiff")])

def mask_to_polygons(mask, transform: Affine):
    contours, _ = cv2.findContours(mask, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    polygons = []
    for contour in contours:
        if len(contour) < 3:
            continue
        coords = [transform * (int(pt[0][0]), int(pt[0][1])) for pt in contour]
        try:
            poly = Polygon(coords)
            if not poly.is_valid:
                poly = make_valid(poly).buffer(0)
            if poly.is_valid and not poly.is_empty:
                polygons.append(poly)
        except Exception as e:
            print(f"❌ Polygon error: {e}")
    return polygons

def is_at_tile_edge(polygon, tile_bounds, margin_percent=EDGE_MARGIN_PERCENTAGE):
    """Menentukan apakah polygon berada di tepi tile dengan toleransi margin"""
    # Ekstrak koordinat tile
    minx, miny, maxx, maxy = tile_bounds

    # Hitung margin
    width = maxx - minx
    height = maxy - miny
    margin_x = width * margin_percent
    margin_y = height * margin_percent

    # Buat box untuk area tepi
    left_edge = box(minx, miny, minx + margin_x, maxy)
    right_edge = box(maxx - margin_x, miny, maxx, maxy)
    top_edge = box(minx, miny, maxx, miny + margin_y)
    bottom_edge = box(minx, maxy - margin_y, maxx, maxy)

    # Periksa apakah polygon bersinggungan dengan tepi
    return (polygon.intersects(left_edge) or
            polygon.intersects(right_edge) or
            polygon.intersects(top_edge) or
            polygon.intersects(bottom_edge))

def calculate_iou(poly1, poly2):
    try:
        intersection = poly1.intersection(poly2).area
        union = poly1.union(poly2).area
        return intersection / union if union != 0 else 0
    except TopologicalError:
        # Handle invalid geometries
        valid_poly1 = make_valid(poly1).buffer(0)
        valid_poly2 = make_valid(poly2).buffer(0)
        intersection = valid_poly1.intersection(valid_poly2).area
        union = valid_poly1.union(valid_poly2).area
        return intersection / union if union != 0 else 0

def are_polygons_adjacent(poly1, poly2, distance=MAX_SNAP_DISTANCE):
    """Memeriksa apakah dua poligon berdekatan dalam jarak tertentu"""
    try:
        return poly1.distance(poly2) <= distance
    except:
        # Handle invalid geometries
        valid_poly1 = make_valid(poly1).buffer(0)
        valid_poly2 = make_valid(poly2).buffer(0)
        return valid_poly1.distance(valid_poly2) <= distance

def merge_edge_polygons(gdf, tile_positions, tile_size):
    """Menggabungkan poligon yang terpotong di tepi tile tanpa mengubah bentuknya"""
    # Pastikan semua geometri valid
    gdf['geometry'] = gdf['geometry'].apply(lambda geom: make_valid(geom).buffer(0) if not geom.is_valid else geom)

    # Tambahkan kolom untuk menandai poligon di tepi
    gdf['at_edge'] = False

    # Identifikasi poligon-poligon di tepi
    for idx, row in gdf.iterrows():
        for x, y in tile_positions:
            # Buat batas tile dalam CRS yang sama
            tile_bounds = transform * (x, y), transform * (x+tile_size, y+tile_size)
            tile_minx, tile_miny = tile_bounds[0]
            tile_maxx, tile_maxy = tile_bounds[1]

            # Periksa apakah polygon berada di tepi
            if is_at_tile_edge(row.geometry, (tile_minx, tile_miny, tile_maxx, tile_maxy)):
                gdf.at[idx, 'at_edge'] = True
                break

    # Pisahkan poligon tepi dan non-tepi
    edge_polygons = gdf[gdf['at_edge'] == True].copy()
    non_edge_polygons = gdf[gdf['at_edge'] == False].copy()

    print(f"🔍 Total poligon di tepi: {len(edge_polygons)}")
    print(f"🔍 Total poligon non-tepi: {len(non_edge_polygons)}")

    # Proses penggabungan poligon di tepi untuk setiap kelas
    merged_edge_polygons = []
    for class_id in edge_polygons['class_id'].unique():
        class_edges = edge_polygons[edge_polygons['class_id'] == class_id]

        # Buat graf keterhubungan poligon
        groups = []
        processed = set()

        # Untuk setiap poligon, temukan poligon berdekatan dan bentuk grup
        for idx, row in class_edges.iterrows():
            if idx in processed:
                continue

            # Mulai grup baru
            current_group = [idx]
            processed.add(idx)

            # Proses secara rekursif
            to_process = [idx]
            while to_process:
                current_idx = to_process.pop(0)
                current_poly = class_edges.loc[current_idx, 'geometry']

                # Cari poligon yang berdekatan
                for other_idx, other_row in class_edges.iterrows():
                    if other_idx in processed:
                        continue

                    other_poly = other_row['geometry']

                    # Jika berdekatan, tambahkan ke grup dan antrean proses
                    if are_polygons_adjacent(current_poly, other_poly):
                        current_group.append(other_idx)
                        processed.add(other_idx)
                        to_process.append(other_idx)

            # Simpan grup
            if current_group:
                groups.append(current_group)

        # Gabungkan poligon dalam setiap grup
        for group in groups:
            if len(group) == 1:
                # Jika hanya ada satu poligon, tidak perlu digabungkan
                merged_edge_polygons.append({
                    'geometry': class_edges.loc[group[0], 'geometry'],
                    'class_id': class_id
                })
            else:
                # Gabungkan poligon dalam grup (tanpa buffer)
                group_polys = [class_edges.loc[idx, 'geometry'] for idx in group]
                try:
                    # Gunakan unary_union untuk menggabungkan poligon yang bersentuhan
                    merged = unary_union(group_polys)

                    # Pastikan hasil valid
                    if not merged.is_valid:
                        merged = make_valid(merged).buffer(0)

                    # Tambahkan hasil gabungan
                    if merged.geom_type == 'Polygon':
                        merged_edge_polygons.append({
                            'geometry': merged,
                            'class_id': class_id
                        })
                    elif merged.geom_type == 'MultiPolygon':
                        for geom in merged.geoms:
                            merged_edge_polygons.append({
                                'geometry': geom,
                                'class_id': class_id
                            })
                except Exception as e:
                    print(f"⚠️ Gagal menggabungkan grup poligon: {e}")
                    # Jika gagal, tambahkan poligon-poligon secara individual
                    for idx in group:
                        merged_edge_polygons.append({
                            'geometry': class_edges.loc[idx, 'geometry'],
                            'class_id': class_id
                        })

    # Gabungkan hasil poligon tepi yang diproses dengan poligon non-tepi
    edge_gdf = gpd.GeoDataFrame(merged_edge_polygons, crs=gdf.crs)

    # Gabungkan hasil
    result_gdf = pd.concat([edge_gdf, non_edge_polygons[['geometry', 'class_id']]], ignore_index=True)
    return gpd.GeoDataFrame(result_gdf, crs=gdf.crs)

def filter_duplicate_polygons(polygons, iou_threshold=0.5):
    filtered = []
    for i, poly1 in enumerate(polygons):
        is_duplicate = False
        for poly2 in filtered:
            if poly1['class_id'] == poly2['class_id']:
                try:
                    iou = calculate_iou(poly1['geometry'], poly2['geometry'])
                    if iou > iou_threshold:
                        is_duplicate = True
                        break
                except Exception as e:
                    print(f"❗ IOU error: {e}")
                    continue
        if not is_duplicate:
            filtered.append(poly1)
    return filtered

# --- API untuk Laravel ---
def process_image_from_api(image_bytes, plantation_geojson, model_path, class_names=None):
    """
    Memproses gambar yang diterima dari API Laravel

    Args:
        image_bytes: Byte array dari gambar (PNG)
        plantation_geojson: GeoJSON dari polygon kebun yang digunakan untuk clipping
        model_path: Path ke model YOLO
        class_names: Dictionary mapping label id ke nama label

    Returns:
        GeoJSON hasil segmentasi
    """
    # Buat temporary directory
    temp_dir = tempfile.mkdtemp()

    # Inisialisasi array untuk menyimpan log
    logs = []

    # Fungsi untuk mencatat log
    def log_message(message):
        print(message)
        logs.append(message)

    log_message(f"🚀 Mulai proses inferensi YOLO pada {time.strftime('%Y-%m-%d %H:%M:%S')}")

    try:
        # Load gambar dari bytes
        log_message("📥 Memuat gambar input...")
        image_io = io.BytesIO(image_bytes)
        image = Image.open(image_io)
        image_array = np.array(image)
        log_message(f"✅ Gambar dimuat: {image.width}x{image.height} piksel")

        # Simpan gambar sementara untuk YOLO
        temp_image_path = os.path.join(temp_dir, "input.png")
        image.save(temp_image_path)

        # Ekstrak bounds dari GeoJSON untuk transformasi koordinat
        log_message("🗺️ Mengekstrak bounds dari GeoJSON...")
        plantation_data = json.loads(plantation_geojson)
        bounds = extract_bounds_from_geojson(plantation_data)
        minx, miny, maxx, maxy = bounds
        log_message(f"📐 Bounds: ({minx}, {miny}) - ({maxx}, {maxy})")

        # Buat transformasi dari pixel ke koordinat dunia
        width, height = image.size
        transform = Affine(
            (maxx - minx) / width, 0, minx,
            0, (miny - maxy) / height, maxy
        )

        # Load model YOLO
        log_message(f"🧠 Memuat model YOLO dari {model_path}...")
        model = YOLO(model_path)
        log_message("✅ Model YOLO berhasil dimuat")

        # --- Sliding Window dengan Overlap ---
        # Gunakan parameter yang lebih optimal
        tile_size = 1080  # Ukuran optimal untuk model YOLO
        overlap = int(tile_size * OVERLAP_PERCENTAGE)  # 20% overlap
        stride = tile_size - overlap

        log_message(f"🔍 Mengonfigurasi sliding window dengan tile_size={tile_size}, overlap={overlap}px, stride={stride}px")

        all_polygons = []
        tiles = []
        tile_positions = []
        height, width = image_array.shape[:2]

        # Buat tile dengan overlap
        log_message(f"🧩 Memotong gambar menjadi tiles untuk proses inferensi...")

        for y in range(0, height - tile_size + 1, stride):
            for x in range(0, width - tile_size + 1, stride):
                tile = image_array[y:y+tile_size, x:x+tile_size]

                # Skip tile yang terlalu gelap/kosong
                if np.mean(tile) < 5:
                    continue

                tile_img = Image.fromarray(tile)
                tile_filename = os.path.join(temp_dir, f"{uuid.uuid4().hex}.jpg")
                tile_img.save(tile_filename)
                tiles.append(tile_filename)
                tile_positions.append((x, y))

        log_message(f"🧠 Total tile yang akan diproses: {len(tiles)}")

        # --- Inference pada setiap tile ---
        for i, tile_path in enumerate(tiles):
            x, y = tile_positions[i]
            log_message(f"⏳ Memproses tile {i+1}/{len(tiles)} (posisi x={x}, y={y})...")

            # Inferensi menggunakan model YOLO dengan parameter optimal
            result = model.predict(
                source=tile_path,
                conf=0.5,  # Threshold deteksi
                imgsz=1080,
                save=False,
                verbose=False,
                retina_masks=True  # Untuk hasil mask yang lebih detail
            )[0]

            if result.masks is not None:
                tile_polygons = []
                detected_classes = []

                for k, mask in enumerate(result.masks.data):
                    # Konversi mask ke numpy array
                    mask_np = (mask.cpu().numpy() * 255).astype(np.uint8)

                    # Gunakan transformasi dengan offset sesuai posisi tile
                    offset_transform = transform * Affine.translation(x, y)

                    # Konversi mask ke polygon
                    polygons = mask_to_polygons(mask_np, offset_transform)

                    # Dapatkan class ID dan confidence
                    class_id = int(result.boxes.cls[k]) if result.boxes is not None else -1
                    confidence = float(result.boxes.conf[k]) if result.boxes is not None else 0.0
                    detected_classes.append(class_id)

                    # Simpan polygon dengan informasi tambahan
                    for poly in polygons:
                        tile_polygons.append({
                            "geometry": poly,
                            "class_id": class_id,
                            "confidence": confidence,
                            "tile_x": x,
                            "tile_y": y
                        })

                # Filter duplikat polygon menggunakan IOU
                orig_count = len(tile_polygons)
                tile_polygons = filter_duplicate_polygons(tile_polygons)
                filtered_count = orig_count - len(tile_polygons)

                # Tambahkan polygon yang sudah difilter ke hasil akhir
                all_polygons.extend(tile_polygons)

                # Log hasil deteksi per tile
                classes_str = ", ".join([f"Class {c}" for c in set(detected_classes)])
                log_message(f"✅ Tile {i+1}/{len(tiles)}: {len(tile_polygons)} objek ({filtered_count} duplikat dihapus) → {classes_str}")
            else:
                log_message(f"⚪ Tile {i+1}/{len(tiles)}: Tidak ada objek terdeteksi")

        # Jika tidak ada polygon terdeteksi, kembalikan GeoJSON kosong
        if not all_polygons:
            log_message("⚠️ Tidak ada objek terdeteksi di seluruh gambar")
            return {
                "type": "FeatureCollection",
                "features": [],
                "logs": logs
            }

        # Import pandas
        import pandas as pd

        # Buat GeoDataFrame dari semua polygon terdeteksi
        log_message(f"🔄 Membuat GeoDataFrame dari {len(all_polygons)} objek terdeteksi...")
        gdf = gpd.GeoDataFrame(all_polygons, crs="EPSG:4326")

        # Pastikan semua geometri valid
        log_message("🛠️ Validasi geometri untuk semua objek...")
        gdf['geometry'] = gdf['geometry'].apply(lambda geom: make_valid(geom).buffer(0) if not geom.is_valid else geom)

        # Simpan versi tanpa penggabungan untuk perbandingan jika diperlukan
        raw_count = len(gdf)
        log_message(f"📊 Total objek sebelum penggabungan: {raw_count}")

        # Gabungkan poligon di tepi tile
        try:
            log_message("🔄 Menggabungkan poligon di tepi tile untuk menghilangkan efek 'pemotongan'...")
            gdf_merged = merge_edge_polygons(gdf, tile_positions, tile_size)
            merged_count = len(gdf_merged)
            log_message(f"✅ Penggabungan berhasil: {raw_count - merged_count} objek digabungkan, {merged_count} objek akhir")
        except Exception as e:
            log_message(f"⚠️ Gagal menggabungkan poligon di tepi: {str(e)}")
            log_message("⚠️ Menggunakan hasil tanpa penggabungan sebagai fallback")
            gdf_merged = gdf

        # Konversi class_id ke nama kelas jika tersedia
        if class_names:
            gdf_merged['class_name'] = gdf_merged['class_id'].apply(lambda x: class_names.get(x, f"class_{x}"))
            log_message("✅ Nama kelas ditambahkan ke setiap objek")
        else:
            gdf_merged['class_name'] = gdf_merged['class_id'].apply(lambda x: f"class_{x}")

        # Hitung statistik per kelas
        class_counts = gdf_merged['class_id'].value_counts().to_dict()
        class_stats = []
        for class_id, count in class_counts.items():
            class_name = class_names.get(class_id, f"class_{class_id}") if class_names else f"class_{class_id}"
            class_stats.append(f"{class_name}: {count} objek")

        log_message(f"📊 Statistik hasil: {', '.join(class_stats)}")

        # Konversi ke GeoJSON untuk dikirim kembali ke Laravel
        log_message("📤 Mengkonversi hasil ke format GeoJSON...")
        geojson_data = json.loads(gdf_merged.to_json())

        # Tambahkan confidence ke properti setiap feature
        for i, feature in enumerate(geojson_data['features']):
            if 'confidence' in gdf_merged.columns and i < len(gdf_merged):
                feature['properties']['confidence'] = float(gdf_merged.iloc[i]['confidence']) if 'confidence' in gdf_merged.iloc[i] else 0.0

        # Tambahkan metadata ke output
        geojson_data['logs'] = logs
        geojson_data['metadata'] = {
            "processed_at": time.strftime("%Y-%m-%d %H:%M:%S"),
            "tile_size": tile_size,
            "overlap_percentage": OVERLAP_PERCENTAGE,
            "total_tiles": len(tiles),
            "total_objects": len(geojson_data['features']),
            "class_counts": class_counts
        }

        log_message(f"✅ Inferensi selesai: {len(geojson_data['features'])} objek terdeteksi")
        return geojson_data

    except Exception as e:
        log_message(f"❌ Error: {str(e)}")
        import traceback
        log_message(f"❌ Detail: {traceback.format_exc()}")
        return {
            "type": "FeatureCollection",
            "features": [],
            "logs": logs,
            "error": str(e)
        }

    finally:
        # Bersihkan temporary directory
        log_message("🧹 Membersihkan file sementara...")
        shutil.rmtree(temp_dir, ignore_errors=True)

def extract_bounds_from_geojson(geojson_data):
    """Ekstrak bounds (minx, miny, maxx, maxy) dari GeoJSON"""
    if isinstance(geojson_data, str):
        geojson_data = json.loads(geojson_data)

    # Jika GeoJSON adalah FeatureCollection
    if geojson_data['type'] == 'FeatureCollection':
        geometries = [feature['geometry'] for feature in geojson_data['features']]
    # Jika GeoJSON adalah Feature
    elif geojson_data['type'] == 'Feature':
        geometries = [geojson_data['geometry']]
    # Jika GeoJSON langsung berupa geometry
    else:
        geometries = [geojson_data]

    # Ekstrak semua koordinat
    all_coords = []
    for geom in geometries:
        if geom['type'] == 'Polygon':
            coords = geom['coordinates'][0]  # Outer ring
            all_coords.extend(coords)
        elif geom['type'] == 'MultiPolygon':
            for poly in geom['coordinates']:
                coords = poly[0]  # Outer ring setiap polygon
                all_coords.extend(coords)

    # Hitung bounds
    xs = [coord[0] for coord in all_coords]
    ys = [coord[1] for coord in all_coords]

    return min(xs), min(ys), max(xs), max(ys)

def png_to_geotiff(png_bytes, bounds, output_path=None):
    """
    Mengkonversi PNG ke GeoTIFF dengan bounds yang ditentukan

    Args:
        png_bytes: Byte array dari PNG
        bounds: (minx, miny, maxx, maxy) dalam koordinat dunia
        output_path: Path output GeoTIFF (opsional)

    Returns:
        Path ke GeoTIFF jika output_path ditentukan, atau array GeoTIFF
    """
    # Baca PNG dari bytes
    img = Image.open(io.BytesIO(png_bytes))
    img_array = np.array(img)

    # Ekstrak bounds
    minx, miny, maxx, maxy = bounds

    # Hitung transform dari bounds
    width, height = img.size
    transform = Affine(
        (maxx - minx) / width, 0, minx,
        0, (miny - maxy) / height, maxy
    )

    # Buat GeoTIFF
    if output_path:
        with rasterio.open(
            output_path,
            'w',
            driver='GTiff',
            height=height,
            width=width,
            count=3 if img_array.ndim == 3 and img_array.shape[2] == 3 else 4,
            dtype=img_array.dtype,
            crs='EPSG:4326',
            transform=transform
        ) as dst:
            if img_array.ndim == 3 and img_array.shape[2] == 3:
                # RGB image
                dst.write(img_array[:, :, 0], 1)
                dst.write(img_array[:, :, 1], 2)
                dst.write(img_array[:, :, 2], 3)
            elif img_array.ndim == 3 and img_array.shape[2] == 4:
                # RGBA image
                dst.write(img_array[:, :, 0], 1)
                dst.write(img_array[:, :, 1], 2)
                dst.write(img_array[:, :, 2], 3)
                dst.write(img_array[:, :, 3], 4)
            else:
                # Grayscale
                dst.write(img_array, 1)

        return output_path
    else:
        return img_array, transform

# Flask server untuk menerima permintaan dari Laravel
app = Flask(__name__)

@app.route('/yolo-inference', methods=['POST'])
def yolo_inference_api():
    # Dapatkan data dari request
    request_data = request.json

    if not request_data:
        return jsonify({"error": "No JSON data received"}), 400

    # Validasi input
    required_fields = ['image_base64', 'plantation_geojson', 'model_path']
    for field in required_fields:
        if field not in request_data:
            return jsonify({"error": f"Missing required field: {field}"}), 400

    try:
        # Tambahkan timestamp untuk log
        start_time = time.time()

        # Decode image dari base64
        image_bytes = base64.b64decode(request_data['image_base64'])

        # Dapatkan GeoJSON dan model path
        plantation_geojson = request_data['plantation_geojson']
        model_path = request_data['model_path']

        # Dapatkan class_names jika ada
        class_names = request_data.get('class_names', {})

        # Proses gambar
        result_geojson = process_image_from_api(
            image_bytes,
            plantation_geojson,
            model_path,
            class_names
        )

        # Tambahkan waktu pemrosesan
        processing_time = time.time() - start_time
        result_geojson['processing_time'] = processing_time

        # Kembalikan hasil
        return jsonify(result_geojson), 200

    except Exception as e:
        return jsonify({
            "error": str(e),
            "logs": [{"time": time.strftime("%H:%M:%S"), "message": f"❌ Error: {str(e)}"}]
        }), 500

# Main function untuk menjalankan server dari command line
if __name__ == '__main__':
    # Import time untuk log
    import time

    if len(sys.argv) > 1 and sys.argv[1] == 'serve':
        # Jalankan sebagai server
        port = int(sys.argv[2]) if len(sys.argv) > 2 else 5000
        print(f"🚀 Menjalankan server YOLO Inference pada port {port}...")
        app.run(host='0.0.0.0', port=port)
    else:
        # Jalankan script asli untuk penggunaan standalone
        # --- Load Model YOLO ---
        model = YOLO(r"D:\ipb capekk\SKRIPSI COY\Data Drone RGB\best14.pt")

        # --- Load Citra TIF ---
        image_path = choose_file()
        if not image_path:
            print("❌ Tidak ada file dipilih.")
            exit()

        with rasterio.open(image_path) as src:
            image = src.read([1, 2, 3])
            transform = src.transform
            crs = src.crs
            image = np.moveaxis(image, 0, -1).astype(np.uint8)
            height, width, _ = image.shape

        # --- Sliding Window dengan Overlap ---
        tile_size = 1080
        # Tambahkan overlap yang lebih kecil
        overlap = int(tile_size * OVERLAP_PERCENTAGE)
        stride = tile_size - overlap
        temp_dir = "temp_tiles"
        os.makedirs(temp_dir, exist_ok=True)

        tiles = []
        tile_positions = []
        image_disp = image.copy()
        all_polygons = []

        # Buat tile dengan overlap
        for y in range(0, height - tile_size + 1, stride):
            for x in range(0, width - tile_size + 1, stride):
                tile = image[y:y+tile_size, x:x+tile_size]
                if tile.mean() < 5:
                    continue
                tile_img = Image.fromarray(tile)
                tile_filename = os.path.join(temp_dir, f"{uuid.uuid4().hex}.jpg")
                tile_img.save(tile_filename)
                tiles.append(tile_filename)
                tile_positions.append((x, y))

        # --- Inference ---
        print(f"🧠 Total tile yang diproses: {len(tiles)}")
        for i, tile_path in tqdm(enumerate(tiles), total=len(tiles), desc="🔍 Inference"):
            x, y = tile_positions[i]
            result = model.predict(
                source=tile_path,
                conf=0.5,
                imgsz=1080,
                save=False,
                verbose=False,
                retina_masks=True
            )[0]

            if result.masks is not None:
                tile_polygons = []
                detected_classes = []
                for k, mask in enumerate(result.masks.data):
                    mask_np = (mask.cpu().numpy() * 255).astype(np.uint8)
                    offset_transform = transform * Affine.translation(x, y)
                    polygons = mask_to_polygons(mask_np, offset_transform)

                    class_id = int(result.boxes.cls[k]) if result.boxes is not None else -1
                    detected_classes.append(class_id)

                    for poly in polygons:
                        tile_polygons.append({
                            "geometry": poly,
                            "class_id": class_id,
                            "tile_x": x,
                            "tile_y": y
                        })

                    contours, _ = cv2.findContours(mask_np, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
                    for cnt in contours:
                        cnt[:, 0, 0] += x
                        cnt[:, 0, 1] += y
                    color = (0, 255, 0) if class_id == 0 else (255, 0, 0)
                    cv2.drawContours(image_disp, contours, -1, color, 1)

                # Gunakan threshold dari parameter global
                tile_polygons = filter_duplicate_polygons(tile_polygons)
                all_polygons.extend(tile_polygons)

                print(f"🧩 Tile {i+1}/{len(tiles)} (x={x}, y={y}): {len(tile_polygons)} objek setelah filter → Class IDs: {detected_classes}")
            else:
                print(f"⚪ Tile {i+1}/{len(tiles)} (x={x}, y={y}): Tidak ada objek.")

        # --- Simpan Shapefile ---
        if all_polygons:
            # Import pandas jika belum
            import pandas as pd

            # Buat GeoDataFrame dari semua poligon terdeteksi
            gdf = gpd.GeoDataFrame(all_polygons, crs=crs)

            # Pastikan semua geometri valid sebelum melanjutkan
            gdf['geometry'] = gdf['geometry'].apply(lambda geom: make_valid(geom).buffer(0))

            # Simpan versi tanpa penggabungan untuk perbandingan
            raw_path = os.path.splitext(image_path)[0] + "_kanopi_raw.shp"
            gdf.to_file(raw_path)
            print(f"✅ Shapefile tanpa penggabungan disimpan di: {raw_path}")

            try:
                # Gabungkan poligon di tepi tanpa mengubah bentuk
                print("🔄 Menggabungkan poligon di tepi tile tanpa mengubah bentuk...")
                gdf_merged = merge_edge_polygons(gdf, tile_positions, tile_size)

                # Simpan output
                final_path = os.path.splitext(image_path)[0] + "_kanopi_final.shp"
                gdf_merged.to_file(final_path)
                print(f"✅ Shapefile hasil akhir disimpan di: {final_path}")
            except Exception as e:
                print(f"❌ Error saat memproses geometri: {e}")
        else:
            print("⚠️ Tidak ada objek terdeteksi.")

        # --- Bersihkan ---
        shutil.rmtree(temp_dir, ignore_errors=True)

        # --- Tampilkan hasil ---
        # plt.figure(figsize=(12, 12))
        # plt.imshow(image_disp)
        # plt.title("Segmentasi Kanopi Durian (Filtered & Merged)")
        # plt.axis("off")
        # plt.show()
