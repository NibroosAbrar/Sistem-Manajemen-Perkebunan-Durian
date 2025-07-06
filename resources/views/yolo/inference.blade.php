@extends('layouts.app')

@section('title', 'YOLO Inferensi - Segmentasi Citra')

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #map {
        height: 600px;
        width: 100%;
        border-radius: 10px;
    }
    .card-yolo {
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }
    .loader {
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
        margin: 20px auto;
        display: none;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .hidden {
        display: none;
    }
    .legend {
        padding: 10px;
        background: white;
        border-radius: 5px;
        box-shadow: 0 0 15px rgba(0,0,0,0.2);
    }
    .legend-item {
        margin-bottom: 5px;
    }
    .legend-color {
        display: inline-block;
        width: 20px;
        height: 20px;
        margin-right: 5px;
        border: 1px solid #ccc;
    }
    #console {
        background-color: #2d2d2d;
        color: #f8f8f8;
        font-family: 'Courier New', Courier, monospace;
        padding: 10px;
        border-radius: 5px;
        height: 300px;
        overflow-y: auto;
        font-size: 14px;
        line-height: 1.5;
        margin-top: 20px;
    }
    #console p {
        margin: 0;
        padding: 2px 0;
    }
    .log-time {
        color: #a8a8a8;
        margin-right: 10px;
    }
    .log-error {
        color: #ff6b6b;
    }
    .log-success {
        color: #69db7c;
    }
    .log-info {
        color: #74c0fc;
    }
    .log-warning {
        color: #ffd43b;
    }
    .console-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #202020;
        padding: 5px 10px;
        border-top-left-radius: 5px;
        border-top-right-radius: 5px;
        margin-top: 20px;
    }
    .console-title h5 {
        margin: 0;
        color: white;
    }
    .console-title button {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        font-size: 14px;
    }
</style>
@endsection

@section('content')
<div class="container">
    <h1 class="mb-4">Inferensi YOLO - Segmentasi Citra</h1>

    <div class="row">
        <div class="col-md-4">
            <div class="card card-yolo">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Parameter Inferensi</h5>
                </div>
                <div class="card-body">
                    <form id="inferenceForm">
                        <div class="mb-3">
                            <label for="aerialPhoto" class="form-label">Foto Udara</label>
                            <select class="form-select" id="aerialPhoto" name="aerial_photo_id" required>
                                <option value="">Pilih Foto Udara</option>
                                @foreach($aerialPhotos as $photo)
                                    <option value="{{ $photo->id }}">{{ $photo->path }} ({{ $photo->capture_time }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="plantation" class="form-label">Lokasi Kebun</label>
                            <select class="form-select" id="plantation" name="plantation_id" required>
                                <option value="">Pilih Kebun</option>
                                @foreach($plantations as $plantation)
                                    <option value="{{ $plantation->id }}">{{ $plantation->name }} ({{ $plantation->area_size }} ha)</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="button" id="selectAreaBtn" class="btn btn-primary mb-3" disabled>
                            Pilih Area Di Peta
                        </button>

                        <div class="mb-3 hidden" id="selectedAreaInfo">
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill"></i> Area telah dipilih
                                <button type="button" id="clearSelectionBtn" class="btn btn-sm btn-outline-danger float-end">
                                    Hapus Pilihan
                                </button>
                            </div>
                        </div>

                        <input type="hidden" id="plantationGeojson" name="plantation_geojson">

                        <button type="submit" id="processBtn" class="btn btn-success w-100" disabled>
                            Proses Inferensi
                        </button>
                    </form>

                    <div class="loader" id="loader"></div>

                    <div id="resultsInfo" class="mt-3 hidden">
                        <div class="alert alert-info">
                            <h5>Hasil Inferensi:</h5>
                            <p id="detectionCount">Jumlah objek terdeteksi: <span></span></p>
                            <p id="processingTime">Waktu pemrosesan: <span></span> detik</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-yolo">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Keterangan</h5>
                </div>
                <div class="card-body">
                    <p>
                        <i class="bi bi-info-circle"></i>
                        Modul ini menggunakan YOLOv8 (instance segmentation) untuk mendeteksi dan memisahkan objek pada citra.
                    </p>
                    <p>
                        <strong>Langkah-langkah:</strong>
                    </p>
                    <ol>
                        <li>Pilih foto udara dan lokasi kebun</li>
                        <li>Pilih area spesifik di peta</li>
                        <li>Klik "Proses Inferensi"</li>
                        <li>Tunggu hasil pemrosesan</li>
                    </ol>
                </div>
            </div>

            <div class="console-title">
                <h5><i class="bi bi-terminal"></i> Konsol Log</h5>
                <button id="clearConsole">Bersihkan</button>
            </div>
            <div id="console">
                <p><span class="log-info">Siap memproses gambar. Pilih parameter dan klik "Proses Inferensi".</span></p>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card card-yolo">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Peta</h5>
                </div>
                <div class="card-body">
                    <div id="map"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Variabel global
    let map, imageBounds, imageLayer, drawnItems;
    let selectedArea = null;
    let resultLayer = null;

    // Inisialisasi peta
    document.addEventListener('DOMContentLoaded', function() {
        // Buat peta
        map = L.map('map').setView([-6.2088, 106.8456], 12);

        // Tambahkan basemap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Layer untuk menampilkan hasil
        drawnItems = L.featureGroup().addTo(map);

        // Handle pemilihan foto udara dan kebun
        document.getElementById('aerialPhoto').addEventListener('change', onSelectionChange);
        document.getElementById('plantation').addEventListener('change', onSelectionChange);

        // Handle klik tombol pilih area
        document.getElementById('selectAreaBtn').addEventListener('click', enableAreaSelection);

        // Handle klik tombol hapus pilihan
        document.getElementById('clearSelectionBtn').addEventListener('click', clearSelection);

        // Handle submit form
        document.getElementById('inferenceForm').addEventListener('submit', processInference);

        // Handle clear console
        document.getElementById('clearConsole').addEventListener('click', clearConsole);
    });

    // Handle perubahan dropdown
    function onSelectionChange() {
        const aerialPhotoId = document.getElementById('aerialPhoto').value;
        const plantationId = document.getElementById('plantation').value;

        // Aktifkan/nonaktifkan tombol pilih area
        document.getElementById('selectAreaBtn').disabled = !(aerialPhotoId && plantationId);

        // Tampilkan batas kebun jika dipilih
        if (plantationId) {
            fetchPlantationBoundary(plantationId);
        }

        // Tampilkan gambar foto udara jika dipilih
        if (aerialPhotoId) {
            fetchAerialPhoto(aerialPhotoId);
        }
    }

    // Ambil dan tampilkan batas kebun
    function fetchPlantationBoundary(plantationId) {
        fetch(`/api/plantations/${plantationId}/geojson`)
            .then(response => response.json())
            .then(data => {
                // Hapus layer kebun yang ada
                if (drawnItems) {
                    drawnItems.clearLayers();
                }

                // Tambahkan layer baru
                L.geoJSON(data, {
                    style: {
                        color: '#3388ff',
                        weight: 3,
                        fillOpacity: 0.1
                    }
                }).addTo(drawnItems);

                // Zoom ke batas kebun
                map.fitBounds(drawnItems.getBounds());
            })
            .catch(error => {
                console.error('Error fetching plantation boundary:', error);
                addToConsole(`Error mengambil data batas kebun: ${error.message}`, 'error');
            });
    }

    // Ambil dan tampilkan foto udara
    function fetchAerialPhoto(aerialPhotoId) {
        fetch(`/api/aerial-photos/${aerialPhotoId}`)
            .then(response => response.json())
            .then(data => {
                // Hapus layer gambar yang ada
                if (imageLayer) {
                    map.removeLayer(imageLayer);
                }

                // Tambahkan layer gambar baru
                const bounds = [[data.bounds.miny, data.bounds.minx], [data.bounds.maxy, data.bounds.maxx]];
                imageLayer = L.imageOverlay(`/storage/${data.path}`, bounds).addTo(map);
                imageBounds = bounds;

                // Zoom ke gambar
                map.fitBounds(bounds);

                addToConsole(`Memuat gambar: ${data.path}`, 'info');
            })
            .catch(error => {
                console.error('Error fetching aerial photo:', error);
                addToConsole(`Error mengambil data foto udara: ${error.message}`, 'error');
            });
    }

    // Fungsi untuk mengaktifkan pemilihan area
    function enableAreaSelection() {
        clearSelection();

        addToConsole('Klik pada peta untuk memilih area yang akan diproses', 'info');

        // Mode pemilihan area
        map.on('click', function(e) {
            if (selectedArea) {
                map.removeLayer(selectedArea);
            }

            // Buat polygon berdasarkan klik pada peta
            selectedArea = L.rectangle([
                [e.latlng.lat - 0.001, e.latlng.lng - 0.001],
                [e.latlng.lat + 0.001, e.latlng.lng + 0.001]
            ], {
                color: '#ff7800',
                weight: 2,
                fillOpacity: 0.3
            }).addTo(map);

            // Simpan GeoJSON
            const selectedGeoJson = selectedArea.toGeoJSON();
            document.getElementById('plantationGeojson').value = JSON.stringify(selectedGeoJson);

            // Tampilkan info area terpilih
            document.getElementById('selectedAreaInfo').classList.remove('hidden');

            // Aktifkan tombol proses
            document.getElementById('processBtn').disabled = false;

            addToConsole('Area telah dipilih', 'success');

            // Matikan event listener setelah satu klik
            map.off('click');
        });
    }

    // Fungsi untuk menghapus pilihan
    function clearSelection() {
        if (selectedArea) {
            map.removeLayer(selectedArea);
            selectedArea = null;
        }

        // Sembunyikan info area terpilih
        document.getElementById('selectedAreaInfo').classList.add('hidden');

        // Nonaktifkan tombol proses
        document.getElementById('processBtn').disabled = true;

        // Hapus nilai GeoJSON
        document.getElementById('plantationGeojson').value = '';

        addToConsole('Pilihan area dihapus', 'info');
    }

    // Fungsi untuk memproses inferensi
    function processInference(event) {
        event.preventDefault();

        // Tampilkan loader
        document.getElementById('loader').style.display = 'block';
        document.getElementById('processBtn').disabled = true;

        // Bersihkan konsol sebelum memulai
        clearConsole();
        addToConsole('Memulai proses inferensi YOLO...', 'info');

        // Ambil data form
        const formData = new FormData(document.getElementById('inferenceForm'));
        const data = {
            aerial_photo_id: formData.get('aerial_photo_id'),
            plantation_id: formData.get('plantation_id'),
            plantation_geojson: formData.get('plantation_geojson')
        };

        // Kirim permintaan
        const startTime = new Date();

        fetch('/api/yolo/inference', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            // Hitung waktu pemrosesan
            const endTime = new Date();
            const processingTime = (endTime - startTime) / 1000;

            // Sembunyikan loader
            document.getElementById('loader').style.display = 'none';

            if (data.error) {
                addToConsole(`Error: ${data.error}`, 'error');
                alert(`Error: ${data.error}`);
                return;
            }

            // Tampilkan log dari Python
            if (data.logs && data.logs.length > 0) {
                data.logs.forEach(log => {
                    let logType = 'info';
                    if (log.message.includes('❌')) logType = 'error';
                    else if (log.message.includes('⚠️')) logType = 'warning';
                    else if (log.message.includes('✅')) logType = 'success';

                    addToConsole(log.message, logType, log.time);
                });
            }

            // Tampilkan hasil
            displayResults(data, processingTime);

            // Tampilkan info hasil
            document.getElementById('resultsInfo').classList.remove('hidden');
            document.getElementById('detectionCount').querySelector('span').textContent =
                data.features.length;
            document.getElementById('processingTime').querySelector('span').textContent =
                (data.processing_time || processingTime).toFixed(2);

            // Aktifkan kembali tombol proses
            document.getElementById('processBtn').disabled = false;

            // Tambahkan log ringkasan
            addToConsole(`Inferensi selesai: ${data.features.length} objek terdeteksi dalam ${processingTime.toFixed(2)} detik`, 'success');
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('loader').style.display = 'none';
            document.getElementById('processBtn').disabled = false;
            addToConsole(`Error: ${error.message}`, 'error');
            alert('Terjadi kesalahan saat memproses data');
        });
    }

    // Fungsi untuk menampilkan hasil di peta
    function displayResults(geojsonData, processingTime) {
        // Hapus layer hasil sebelumnya jika ada
        if (resultLayer) {
            map.removeLayer(resultLayer);
        }

        // Buat objek untuk menyimpan warna berdasarkan kelas
        const classColors = {
            'pohon': '#2ecc71',
            'rumput': '#f1c40f',
            'unknown': '#95a5a6'
        };

        // Tampilkan hasil di peta
        resultLayer = L.geoJSON(geojsonData, {
            style: function(feature) {
                const className = feature.properties.class_name || 'unknown';
                return {
                    color: classColors[className] || '#3388ff',
                    weight: 2,
                    fillOpacity: 0.5
                };
            },
            onEachFeature: function(feature, layer) {
                const className = feature.properties.class_name || 'unknown';
                const confidence = feature.properties.confidence
                    ? (feature.properties.confidence * 100).toFixed(2) + '%'
                    : 'N/A';

                layer.bindPopup(`
                    <strong>Kelas:</strong> ${className}<br>
                    <strong>Keyakinan:</strong> ${confidence}
                `);
            }
        }).addTo(map);

        // Zoom ke hasil
        map.fitBounds(resultLayer.getBounds());

        // Tambahkan legenda
        addLegend(classColors);
    }

    // Fungsi untuk menambahkan legenda
    function addLegend(classColors) {
        // Hapus legenda yang ada jika ada
        const existingLegend = document.querySelector('.legend');
        if (existingLegend) {
            existingLegend.remove();
        }

        // Buat legenda baru
        const legend = L.control({position: 'bottomright'});

        legend.onAdd = function(map) {
            const div = L.DomUtil.create('div', 'legend');
            div.innerHTML = '<h4>Keterangan</h4>';

            for (const className in classColors) {
                div.innerHTML += `
                    <div class="legend-item">
                        <span class="legend-color" style="background-color: ${classColors[className]}"></span>
                        ${className}
                    </div>
                `;
            }

            return div;
        };

        legend.addTo(map);
    }

    // Fungsi untuk menambahkan pesan ke konsol
    function addToConsole(message, type = 'info', time = null) {
        const console = document.getElementById('console');
        const timestamp = time || new Date().toTimeString().split(' ')[0];

        // Menyesuaikan warna berdasarkan jenis pesan
        let cssClass = `log-${type}`;

        const logEntry = document.createElement('p');
        logEntry.innerHTML = `<span class="log-time">[${timestamp}]</span> <span class="${cssClass}">${message}</span>`;

        console.appendChild(logEntry);

        // Auto scroll to bottom
        console.scrollTop = console.scrollHeight;
    }

    // Fungsi untuk membersihkan konsol
    function clearConsole() {
        const console = document.getElementById('console');
        console.innerHTML = '';
    }
</script>
@endsection
