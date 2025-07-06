@extends('layouts.app')

@section('title', 'Deteksi Pohon Durian Otomatis dengan YOLO')

@section('styles')
<style>
    .preview-container {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 15px;
        min-height: 300px;
        position: relative;
    }
    .preview-placeholder {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: #adb5bd;
        text-align: center;
    }
    .preview-loading {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10;
    }
    .detection-result {
        display: none;
        margin-top: 20px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Deteksi Pohon Durian Otomatis dengan YOLO</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('digitasi.index') }}">Digitasi Pohon</a></li>
        <li class="breadcrumb-item active">Deteksi Otomatis</li>
    </ol>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Deteksi kanopi pohon durian secara otomatis menggunakan model YOLO dari foto udara yang sudah tersedia berdasarkan area blok kebun tertentu.
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-camera me-1"></i> 1. Pilih Foto Udara</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="aerial_photo_id" class="form-label">Citra Foto Udara Tersedia</label>
                        <select id="aerial_photo_id" class="form-select">
                            <option value="">-- Pilih Foto Udara --</option>
                            @foreach($aerialPhotos as $photo)
                                <option value="{{ $photo->id }}">ID: {{ $photo->id }} - {{ $photo->capture_time->format('d/m/Y') }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Silakan pilih citra foto udara dari daftar yang tersedia.</div>
                        <div id="aerial-photo-confirmation" class="mt-2 alert alert-success" style="display:none;"></div>
                    </div>

                    <div class="preview-container">
                        <div id="aerial-photo-loading" class="preview-loading" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div id="aerial-photo-placeholder" class="preview-placeholder">
                            <i class="fas fa-image fa-3x mb-2"></i>
                            <p>Preview foto udara</p>
                        </div>
                        <img id="aerial-photo-preview" src="" alt="Preview foto udara" style="max-width: 100%; max-height: 400px; display: none;">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-map-marked-alt me-1"></i> 2. Pilih Area Blok Kebun</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="plantation_id" class="form-label">Blok Kebun Tersedia</label>
                        <select id="plantation_id" class="form-select">
                            <option value="">-- Pilih Blok Kebun --</option>
                            @foreach($plantations as $plantation)
                                <option value="{{ $plantation->id }}">{{ $plantation->name }} ({{ $plantation->luas_area }} Ha)</option>
                            @endforeach
                        </select>
                        <div class="form-text">Pilih blok kebun dari tabel plantations yang akan menjadi area deteksi.</div>
                        <div id="plantation-confirmation" class="mt-2 alert alert-success" style="display:none;"></div>
                    </div>

                    <div class="preview-container">
                        <div id="plantation-loading" class="preview-loading" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div id="plantation-placeholder" class="preview-placeholder">
                            <i class="fas fa-draw-polygon fa-3x mb-2"></i>
                            <p>Preview area blok kebun</p>
                        </div>
                        <div id="plantation-map" style="width: 100%; height: 300px; display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mb-4">
        <button id="start-detection" class="btn btn-primary btn-lg" disabled>
            <i class="fas fa-play-circle"></i> Mulai Deteksi Pohon pada Area Terpilih
        </button>
    </div>

    <div id="detection-result" class="card mb-4 detection-result">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-leaf me-1"></i> Hasil Deteksi Pohon</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-5">
                    <div class="alert alert-success">
                        <h6 class="alert-heading"><i class="fas fa-check-circle"></i> Deteksi Berhasil!</h6>
                        <p class="mb-0">Berhasil mendeteksi <strong id="detection-count">0</strong> pohon durian di area yang dipilih.</p>
                    </div>

                    <form id="save-detection-form">
                        <div class="mb-3">
                            <label for="detection_name" class="form-label">Nama Proses Digitasi</label>
                            <input type="text" class="form-control" id="detection_name" required>
                            <div class="form-text">Nama untuk identifikasi hasil digitasi ini</div>
                        </div>

                        <button type="submit" class="btn btn-success btn-block w-100">
                            <i class="fas fa-save"></i> Simpan Hasil Deteksi
                        </button>
                    </form>
                </div>

                <div class="col-md-7">
                    <div class="preview-container">
                        <div id="result-map" style="width: 100%; height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />

<script>
    $(document).ready(function() {
        let aerialPhotoData = null;
        let plantationData = null;
        let detectionResults = null;
        let plantationMap = null;
        let resultMap = null;

        // Inisialisasi maps
        function initPlantationMap() {
            if (plantationMap) {
                plantationMap.remove();
            }

            plantationMap = L.map('plantation-map').setView([-6.2, 106.8], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(plantationMap);

            return plantationMap;
        }

        function initResultMap() {
            if (resultMap) {
                resultMap.remove();
            }

            resultMap = L.map('result-map').setView([-6.2, 106.8], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(resultMap);

            return resultMap;
        }

        // Fungsi untuk memuat preview foto udara berdasarkan ID
        function loadAerialPhotoPreview(aerialPhotoId) {
            if (!aerialPhotoId) {
                $('#aerial-photo-preview').hide();
                $('#aerial-photo-placeholder').show();
                checkDetectionReady();
                return;
            }

            $('#aerial-photo-loading').show();
            $('#aerial-photo-placeholder').hide();
            $('#aerial-photo-preview').hide();

            // Ambil data foto udara dari API
            $.ajax({
                url: `/aerial-photo/${aerialPhotoId}/preview`,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        aerialPhotoData = response.data;

                        // Gunakan endpoint langsung untuk mengakses image, tanpa path
                        const directImageUrl = `/aerial-photo-image/${aerialPhotoId}`;
                        console.log('Mengakses gambar langsung dari endpoint:', directImageUrl);
                        
                        // Tampilkan gambar
                            const imgElement = $('#aerial-photo-preview');
                        // Hapus semua event handlers dan langsung set src
                        imgElement.off();
                        imgElement.attr('src', directImageUrl);
                        imgElement.show();
                        
                        // Tampilkan konfirmasi
                        $('#aerial-photo-confirmation').show().text('Foto udara berhasil dimuat: ID ' + aerialPhotoId);
                        
                        // Simpan ID foto udara yang dipilih
                        aerialPhotoData.id = aerialPhotoId;
                    } else {
                        $('#aerial-photo-placeholder').show().html(`
                            <i class="fas fa-exclamation-circle fa-3x mb-2 text-warning"></i>
                            <p>Gagal mengambil data foto udara</p>
                        `);
                    }

                    $('#aerial-photo-loading').hide();
                    checkDetectionReady();
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching aerial photo:', error);
                    $('#aerial-photo-loading').hide();
                    $('#aerial-photo-placeholder').show().html(`
                        <i class="fas fa-exclamation-triangle fa-3x mb-2 text-danger"></i>
                        <p>Terjadi kesalahan saat mengambil data foto udara</p>
                        <small>${error}</small>
                    `);
                    checkDetectionReady();
                }
            });
        }
        
        // Muat foto udara pertama dari daftar yang ada secara otomatis
        const firstAerialPhotoId = $('#aerial_photo_id option:eq(1)').val();
        if (firstAerialPhotoId) {
            console.log('Memuat foto udara pertama dengan ID:', firstAerialPhotoId);
            $('#aerial_photo_id').val(firstAerialPhotoId);
            loadAerialPhotoPreview(firstAerialPhotoId);
        }

        // Pilih Foto Udara
        $('#aerial_photo_id').change(function() {
            const aerialPhotoId = $(this).val();
            loadAerialPhotoPreview(aerialPhotoId);
        });

        // Fungsi untuk memuat preview blok kebun berdasarkan ID
        function loadPlantationPreview(plantationId) {
            if (!plantationId) {
                if (plantationMap) {
                    plantationMap.remove();
                    plantationMap = null;
                }
                $('#plantation-map').hide();
                $('#plantation-placeholder').show();
                checkDetectionReady();
                return;
            }

            $('#plantation-loading').show();
            $('#plantation-placeholder').hide();
            $('#plantation-map').hide();

            // Ambil data plantation
            $.ajax({
                url: `/plantation/${plantationId}/preview`,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        plantationData = response.data;
                        
                        console.log('Data plantation berhasil dimuat:', plantationData);
                        console.log('Data plantation JSON:', JSON.stringify(plantationData));
                        console.log('Tipe data geojson:', typeof plantationData.geojson);

                        // Tampilkan di peta
                        if (plantationData.geojson) {
                            const map = initPlantationMap();
                            $('#plantation-map').show();

                            try {
                                // Tampilkan batas plantation
                                if (plantationData.geojson) {
                                    // Jika geojson sudah berupa objek, gunakan langsung
                                    // Jika string, parse dulu
                                    let geojsonData = null;

                                    if (typeof plantationData.geojson === 'string') {
                                        try {
                                            geojsonData = JSON.parse(plantationData.geojson);
                                            console.log('Parsed geojson from string:', geojsonData);
                                        } catch(e) {
                                            console.error('Failed to parse geojson string:', e);
                                            $('#plantation-placeholder').show().html(`
                                                <i class="fas fa-exclamation-triangle fa-3x mb-2 text-warning"></i>
                                                <p>Error parsing GeoJSON: ${e.message}</p>
                                            `);
                                            return;
                                        }
                                    } else if (typeof plantationData.geojson === 'object') {
                                        geojsonData = plantationData.geojson;
                                        console.log('Using geojson object directly:', geojsonData);
                                    } else {
                                        console.error('Invalid geojson type:', typeof plantationData.geojson);
                                        $('#plantation-placeholder').show().html(`
                                            <i class="fas fa-exclamation-triangle fa-3x mb-2 text-warning"></i>
                                            <p>Invalid GeoJSON format</p>
                                        `);
                                        return;
                                    }

                                    if (!geojsonData) {
                                        console.error('No valid geojson data');
                                        return;
                                    }

                                    try {
                                        const plantationLayer = L.geoJSON(geojsonData, {
                                            style: {
                                                color: '#0d6efd',
                                                weight: 3,
                                                opacity: 0.9,
                                                fillColor: '#10B981',
                                                fillOpacity: 0.3
                                            }
                                        }).addTo(map);

                                        map.fitBounds(plantationLayer.getBounds());
                                        
                                        // Tambah marker di tengah blok kebun
                                        try {
                                            // Gunakan bounds dari layer untuk menentukan center
                                            const bounds = plantationLayer.getBounds();
                                            const center = bounds.getCenter();
                                            
                                            if (center) {
                                                L.marker(center)
                                                    .addTo(map)
                                                    .bindPopup(`<strong>${plantationData.name}</strong><br>Luas: ${plantationData.luas_area} ha`)
                                                    .openPopup();
                                                
                                                console.log('Marker center ditambahkan di koordinat:', center);
                                            } else {
                                                console.warn('Tidak dapat menentukan center blok kebun');
                                            }
                                        } catch (e) {
                                            console.warn('Error menambahkan marker center:', e);
                                            // Tidak fatal, biarkan saja
                                        }
                                        
                                        // Tambahkan text konfirmasi bahwa blok kebun sudah terpilih
                                        $('#plantation-confirmation').show().text('Blok kebun berhasil dimuat: ' + plantationData.name);
                                    } catch(e) {
                                        console.error('Error creating Leaflet layer:', e);
                                        $('#plantation-placeholder').show().html(`
                                            <i class="fas fa-exclamation-triangle fa-3x mb-2 text-warning"></i>
                                            <p>Error creating map layer: ${e.message}</p>
                                        `);
                                    }
                                }
                            } catch (e) {
                                console.error('Error parsing GeoJSON:', e);
                                $('#plantation-placeholder').show().html(`
                                    <i class="fas fa-exclamation-triangle fa-3x mb-2 text-warning"></i>
                                    <p>Error menampilkan data area: ${e.message}</p>
                                `);
                            }
                        } else {
                            // Tampilkan informasi walaupun tidak ada geometri
                            $('#plantation-placeholder').show().html(`
                                <i class="fas fa-exclamation-circle fa-3x mb-2 text-info"></i>
                                <p>Area blok kebun tidak memiliki data geometri</p>
                                <div class="mt-2 p-2 border border-blue-300 rounded">
                                    <strong>Nama:</strong> ${plantationData.name}<br>
                                    <strong>Luas Area:</strong> ${plantationData.luas_area} ha
                                </div>
                            `);
                        }
                    } else {
                        console.error('Error response:', response);
                        $('#plantation-placeholder').show().html(`
                            <i class="fas fa-exclamation-triangle fa-3x mb-2 text-danger"></i>
                            <p>Gagal mengambil data area: ${response.message || 'Unknown error'}</p>
                        `);
                    }

                    $('#plantation-loading').hide();
                    checkDetectionReady();
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching plantation data:', error, xhr.responseText);
                    $('#plantation-loading').hide();
                    $('#plantation-placeholder').show().html(`
                        <i class="fas fa-exclamation-triangle fa-3x mb-2 text-danger"></i>
                        <p>Terjadi kesalahan saat mengambil data area</p>
                    `);
                    checkDetectionReady();
                }
            });
        }
        
        // Muat blok kebun pertama dari daftar yang ada secara otomatis
        const firstPlantationId = $('#plantation_id option:eq(1)').val();
        if (firstPlantationId) {
            console.log('Memuat blok kebun pertama dengan ID:', firstPlantationId);
            console.log('Nama blok kebun:', $('#plantation_id option:eq(1)').text());
            $('#plantation_id').val(firstPlantationId);
            loadPlantationPreview(firstPlantationId);
        } else {
            console.warn('Tidak ada blok kebun tersedia dalam daftar dropdown');
        }
        
        // Pilih Plantation
        $('#plantation_id').change(function() {
            const plantationId = $(this).val();
            loadPlantationPreview(plantationId);
        });

        // Cek apakah tombol deteksi bisa diaktifkan
        function checkDetectionReady() {
            if ($('#aerial_photo_id').val() && $('#plantation_id').val()) {
                // Aktifkan tombol jika kedua dropdown terisi, terlepas apakah preview berhasil dimuat atau tidak
                $('#start-detection').prop('disabled', false);
                console.log('Tombol deteksi diaktifkan: Foto udara dan blok kebun terpilih');
            } else {
                $('#start-detection').prop('disabled', true);
                console.log('Tombol deteksi dinonaktifkan: Foto udara atau blok kebun belum dipilih');
            }
        }

        // Deteksi Pohon
        $('#start-detection').click(function() {
            if (!aerialPhotoData || !plantationData) {
                alert('Harap pilih foto udara dan area blok kebun terlebih dahulu');
                return;
            }

            const button = $(this);
            const originalText = button.html();
            button.html('<i class="fas fa-spinner fa-spin"></i> Mendeteksi Pohon...').prop('disabled', true);

            // Panggil API deteksi
            $.ajax({
                url: '{{ route("digitasi.detect-trees") }}',
                method: 'POST',
                data: {
                    aerial_photo_id: aerialPhotoData.id,
                    plantation_id: plantationData.id,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        detectionResults = response;
                        $('#detection-count').text(response.tree_count);

                        // Tampilkan hasil di peta
                        const map = initResultMap();

                        try {
                            // Tampilkan batas plantation
                            if (plantationData.geojson) {
                                const plantationLayer = L.geoJSON(JSON.parse(plantationData.geojson), {
                                    style: {
                                        color: '#0d6efd',
                                        weight: 2,
                                        opacity: 0.8,
                                        fillOpacity: 0.1
                                    }
                                }).addTo(map);

                                map.fitBounds(plantationLayer.getBounds());
                            }

                            // Tampilkan hasil deteksi pohon
                            if (response.preview_data && response.preview_data.features) {
                                L.geoJSON(response.preview_data, {
                                    style: function(feature) {
                                        return {
                                            color: '#28a745',
                                            weight: 1,
                                            opacity: 0.8,
                                            fillColor: '#28a745',
                                            fillOpacity: 0.5
                                        };
                                    }
                                }).addTo(map);
                            }
                        } catch (e) {
                            console.error('Error displaying results:', e);
                        }

                        // Tampilkan form simpan
                        $('#detection-result').show();
                        $('#detection_name').val('Digitasi ' + new Date().toLocaleDateString());

                        // Scroll ke hasil
                        $('html, body').animate({
                            scrollTop: $('#detection-result').offset().top - 50
                        }, 500);
                    } else {
                        alert('Gagal mendeteksi pohon: ' + response.message);
                    }

                    button.html(originalText).prop('disabled', false);
                },
                error: function() {
                    alert('Terjadi kesalahan saat mendeteksi pohon');
                    button.html(originalText).prop('disabled', false);
                }
            });
        });

        // Simpan hasil deteksi
        $('#save-detection-form').submit(function(e) {
            e.preventDefault();

            if (!detectionResults) {
                alert('Tidak ada hasil deteksi yang dapat disimpan');
                return;
            }

            const button = $(this).find('button[type="submit"]');
            const originalText = button.html();
            button.html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...').prop('disabled', true);

            // Konversi GeoJSON ke WKT (simulasi)
            const wktGeometry = 'MULTIPOLYGON(((106.8 -6.2, 106.801 -6.2, 106.801 -6.201, 106.8 -6.201, 106.8 -6.2)))';

            // Panggil API simpan
            $.ajax({
                url: '{{ route("digitasi.save-detection") }}',
                method: 'POST',
                data: {
                    name: $('#detection_name').val(),
                    aerial_photo_id: aerialPhotoData.id,
                    plantation_id: plantationData.id,
                    tree_count: detectionResults.tree_count,
                    geometry: wktGeometry,
                    detection_data: JSON.stringify(detectionResults.preview_data),
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Hasil deteksi berhasil disimpan!');
                        window.location.href = '{{ route("digitasi.index") }}';
                    } else {
                        alert('Gagal menyimpan hasil deteksi: ' + response.message);
                        button.html(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan saat menyimpan hasil deteksi');
                    button.html(originalText).prop('disabled', false);
                }
            });
        });
    });
</script>
@endsection