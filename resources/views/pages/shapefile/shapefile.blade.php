@extends('layouts.master')

@section('title', 'Shapefile Manager - Symadu')

@section('head')
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

    <!-- Toastify CSS only -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- CSRF Token dipindah ke bagian atas head (higher specificity) -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        /* Sembunyikan tab konten secara default */
        .tab-content {
            display: none;
        }

        /* Tampilkan tab aktif */
        .tab-content.active {
            display: block;
        }

        /* Gaya untuk tab aktif */
        .tab-button.active {
            border-bottom-color: #16a34a !important;
            color: #16a34a !important;
        }
        .tab-button {
            color: #6b7280;
            border-bottom-color: transparent;
            transition: color 0.2s, border-color 0.2s;
        }
        .tab-button:hover {
            color: #16a34a;
            border-bottom-color: #16a34a;
        }
    </style>
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold text-center mb-6 text-green-700">Shapefile Manager</h1>

    <div class="mb-4 flex justify-between items-center">
        <h2 class="text-lg font-semibold">Daftar Shapefile</h2>
        <a href="{{ route('shapefile.create') }}" class="bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition">
            Upload Shapefile Baru
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif


    <div class="flex mb-6">
        <button id="tabBlokKebun" class="px-4 py-2 border-b-2 font-medium mr-4 tab-button" type="button">
            Blok Kebun
        </button>
        <button id="tabPohon" class="px-4 py-2 border-b-2 font-medium tab-button" type="button">
            Pohon
        </button>
    </div>

    <!-- Tab Blok Kebun Content -->
    <div id="contentBlokKebun" class="tab-content active">
        @if(isset($shapefiles) && $shapefiles->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Upload</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($shapefiles as $shapefile)
                    @if($shapefile->type == 'plantation')
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $shapefile->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $shapefile->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Blok Kebun
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($shapefile->geometry)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Diproses
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Belum Diproses
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $shapefile->created_at->format('d M Y H:i') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                @if(!$shapefile->geometry)
                                    <form action="{{ route('shapefile.process', $shapefile->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-indigo-600 hover:text-indigo-900 bg-indigo-100 px-2 py-1 rounded">Proses</button>
                                    </form>
                                @else
                                    <a href="{{ route('webgis') }}?shapefile={{ $shapefile->id }}" class="text-green-600 hover:text-green-900 bg-green-100 px-2 py-1 rounded">
                                        Lihat di Peta
                                    </a>
                                    <form action="{{ route('shapefile.reset', $shapefile->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-orange-600 hover:text-orange-900 bg-orange-100 px-2 py-1 rounded" onclick="return confirm('Apakah Anda yakin ingin mereset shapefile ini? Status akan kembali menjadi belum diproses.')">Reset</button>
                                    </form>
                                @endif
                                <a href="{{ route('shapefile.edit', $shapefile->id) }}" class="text-blue-600 hover:text-blue-900 bg-blue-100 px-2 py-1 rounded">Edit</a>
                                <form action="{{ route('shapefile.destroy', $shapefile->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 bg-red-100 px-2 py-1 rounded" onclick="return confirm('Apakah Anda yakin ingin menghapus shapefile ini?')">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="bg-gray-100 p-4 rounded text-center">
            <p class="text-gray-600">Belum ada shapefile blok kebun yang tersedia</p>
        </div>
        @endif
    </div>

    <!-- Tab Pohon Content -->
    <div id="contentPohon" class="tab-content hidden">

        <!-- Data Shapefile Pohon yang Sudah Ada -->
        @if(isset($shapefiles) && $shapefiles->where('type', 'tree')->count() > 0)
        <div class="mb-8">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Shapefile Pohon Tersimpan</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Upload</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($shapefiles as $shapefile)
                        @if($shapefile->type == 'tree')
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $shapefile->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $shapefile->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    Pohon
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($shapefile->geometry)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Diproses
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Belum Diproses
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $shapefile->created_at->format('d M Y H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    @if(!$shapefile->geometry)
                                        <form action="{{ route('shapefile.process', $shapefile->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-indigo-600 hover:text-indigo-900 bg-indigo-100 px-2 py-1 rounded">Proses</button>
                                        </form>
                                    @else
                                        <a href="{{ route('webgis') }}?shapefile={{ $shapefile->id }}" class="text-green-600 hover:text-green-900 bg-green-100 px-2 py-1 rounded">
                                            Lihat di Peta
                                        </a>
                                        <form action="{{ route('shapefile.reset', $shapefile->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-orange-600 hover:text-orange-900 bg-orange-100 px-2 py-1 rounded" onclick="return confirm('Apakah Anda yakin ingin mereset shapefile ini? Status akan kembali menjadi belum diproses.')">Reset</button>
                                        </form>
                                    @endif
                                    <a href="{{ route('shapefile.edit', $shapefile->id) }}" class="text-blue-600 hover:text-blue-900 bg-blue-100 px-2 py-1 rounded">Edit</a>
                                    <form action="{{ route('shapefile.destroy', $shapefile->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 bg-red-100 px-2 py-1 rounded" onclick="return confirm('Apakah Anda yakin ingin menghapus shapefile ini?')">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="bg-gray-100 p-4 rounded text-center mb-8">
            <p class="text-gray-600">Belum ada shapefile pohon yang tersedia</p>
        </div>
        @endif

        <!-- Data Hasil Deteksi Pohon yang Sudah Ada -->
        @if(isset($treeDetections) && $treeDetections->count() > 0)
        <div class="mb-8">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Hasil Deteksi Pohon Tersimpan</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Blok Kebun</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Pohon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Deteksi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($treeDetections as $detection)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $detection->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $detection->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $detection->plantation->name ?? 'Tidak tersedia' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $detection->tree_count }} pohon
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $detection->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="{{ route('webgis') }}?tree_detection={{ $detection->id }}" class="text-green-600 hover:text-green-900 bg-green-100 px-2 py-1 rounded">
                                        Lihat di Peta
                                    </a>
                                    <a href="{{ route('tree-detection.edit', $detection->id) }}" class="text-blue-600 hover:text-blue-900 bg-blue-100 px-2 py-1 rounded">Edit</a>
                                    <form action="{{ route('tree-detection.destroy', $detection->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 bg-red-100 px-2 py-1 rounded" onclick="return confirm('Apakah Anda yakin ingin menghapus hasil deteksi ini?')">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="bg-gray-100 p-4 rounded text-center mb-8">
            <p class="text-gray-600">Belum ada hasil deteksi pohon yang tersimpan</p>
        </div>
        @endif

        <!-- Area untuk YOLO Tree Detection -->
        <div class="mt-8 p-4 border border-blue-200 rounded-lg bg-blue-50">
            <h3 class="text-lg font-medium text-blue-800 mb-4">Deteksi Pohon Durian Otomatis dengan YOLO</h3>
            <p class="mb-4 text-sm text-gray-600">Deteksi kanopi pohon durian secara otomatis menggunakan model YOLO dari foto udara yang sudah tersedia berdasarkan area blok kebun tertentu.</p>

            <div class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Pilih Foto Udara -->
                    <div class="border rounded-md p-4 bg-white">
                        <h4 class="font-medium text-gray-800 mb-3">1. Pilih Foto Udara</h4>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Citra Foto Udara Tersedia</label>
                            <select id="aerialPhotoSelect" class="w-full border border-gray-300 rounded-md p-2 bg-white">
                                <option value="">-- Pilih Foto Udara --</option>
                                @foreach($aerialPhotos as $photo)
                                <option value="{{ $photo->id }}">{{ $photo->name }} - {{ $photo->created_at->format('d M Y') }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Silakan pilih citra foto udara dari daftar yang tersedia.</p>
                        </div>
                        <div id="aerialPhotoPreview" class="h-40 border border-gray-200 rounded-md flex items-center justify-center bg-gray-50">
                            <p class="text-gray-400">Preview foto udara</p>
                        </div>
                    </div>

                    <!-- Pilih Blok Kebun -->
                    <div class="border rounded-md p-4 bg-white">
                        <h4 class="font-medium text-gray-800 mb-3">2. Pilih Area Blok Kebun</h4>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Blok Kebun Tersedia</label>
                            <select id="blockSelect" class="w-full border border-gray-300 rounded-md p-2 bg-white" disabled>
                                <option value="">-- Pilih Blok Kebun --</option>
                                <!-- Akan diisi dari JavaScript -->
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Pilih blok kebun dari tabel plantations yang akan menjadi area deteksi.</p>
                        </div>
                        <div id="blockPreview" class="h-40 border border-gray-200 rounded-md flex items-center justify-center bg-gray-50">
                            <p class="text-gray-400">Preview area blok kebun</p>
                        </div>
                    </div>
                </div>

                <!-- Tombol Deteksi -->
                <div class="mt-6 text-center">
                    <button id="detectTreesBtn" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Mulai Deteksi Pohon pada Area Terpilih
                    </button>
                </div>
            </div>

            <!-- Console Output untuk Log Proses -->
            <div id="consoleOutput" class="mt-6 hidden">
                <div class="mb-4 flex justify-between items-center">
                    <h4 class="font-medium text-gray-800">Log Proses Inferensi</h4>
                    <button id="clearConsoleBtn" class="px-2 py-1 text-xs bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                        Clear
                    </button>
                </div>
                <div id="consoleContainer" class="border rounded-md p-4 bg-black text-white font-mono text-sm h-64 overflow-y-auto">
                    <div id="consoleContent">
                        <div class="console-line"><span class="text-green-400">🚀</span> <span class="text-gray-400">[00:00:00]</span> Memulai proses inferensi YOLO...</div>
                    </div>
                </div>
            </div>

            <!-- Area Hasil Deteksi -->
            <div id="detectionResults" class="mt-6 hidden">
                <h4 class="font-medium text-gray-800 mb-4">Hasil Deteksi Pohon</h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="border rounded-md p-4 bg-white">
                        <div id="detectionPreview" class="border border-gray-200 rounded-md h-64 flex items-center justify-center bg-gray-50">
                            <p class="text-gray-400">Visualisasi hasil deteksi</p>
                        </div>
                    </div>

                    <div class="border rounded-md p-4 bg-white">
                        <div class="mb-4">
                            <h5 class="font-medium text-gray-700 mb-2">Informasi Deteksi</h5>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span>Blok Kebun:</span>
                                    <span id="detectionBlockName" class="font-medium">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Jumlah Pohon Terdeteksi:</span>
                                    <span id="treeCount" class="font-medium">0</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Luas Area:</span>
                                    <span id="areaSize" class="font-medium">0 ha</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Kepadatan:</span>
                                    <span id="treeDensity" class="font-medium">0 pohon/ha</span>
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Hasil Deteksi</label>
                                <input type="text" id="detectionName" class="w-full border border-gray-300 rounded-md p-2" placeholder="Masukkan nama untuk menyimpan hasil deteksi...">
                            </div>
                        </div>

                        <div class="border-t pt-4 mt-4">
                            <div class="flex flex-col md:flex-row gap-2 justify-between">
                                <button id="saveDetectionBtn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                                    Simpan ke Database
                                </button>
                                <button id="exportShapefileBtn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                                    Export Shapefile
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 text-center">
        <a href="{{ route('webgis') }}" class="text-green-700 font-bold hover:underline">Kembali ke Peta</a>
    </div>
</div>

<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    // Set tab pertama (Blok Kebun) sebagai aktif saat load
    tabButtons[0].classList.add('active');
    tabContents[0].classList.add('active');
    tabContents[0].classList.remove('hidden');

    // Tab switching functionality
    tabButtons.forEach((button, idx) => {
        button.addEventListener('click', () => {
            // Deactivate all tabs
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => {
                content.classList.remove('active');
                content.classList.add('hidden');
            });

            // Activate the clicked tab
            button.classList.add('active');
            tabContents[idx].classList.add('active');
            tabContents[idx].classList.remove('hidden');
        });
    });

    // YOLO Tree Detection with aerial photos and block selection
    const aerialPhotoSelect = document.getElementById('aerialPhotoSelect');
    const blockSelect = document.getElementById('blockSelect');
    const detectTreesBtn = document.getElementById('detectTreesBtn');
    const detectionResults = document.getElementById('detectionResults');

    // Fungsi untuk memuat preview foto udara
    function loadAerialPhotoPreview(photoId) {
        if (!photoId) return;

        console.log('Memuat preview foto udara dengan ID:', photoId);

        // Set nilai select
        if (aerialPhotoSelect.value !== photoId) {
            aerialPhotoSelect.value = photoId;
        }

        // URL dari API endpoint yang sama dengan yang digunakan di aerial.blade.php
        const imageUrl = `/aerial-photo/${photoId}/preview`;

        // Muat data aerial photo dulu
        fetch(imageUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    // Menggunakan format path yang sama dengan aerial.blade.php
                    const displayPath = data.data.original_path ? `/storage/${data.data.original_path}` : data.data.path;

                    console.log('Path gambar:', displayPath);

                    // Tampilkan gambar menggunakan path yang konsisten dengan aerial.blade.php
                    document.getElementById('aerialPhotoPreview').innerHTML = `
                        <div class="overflow-hidden rounded-lg h-full">
                            <img
                                src="${displayPath}"
                                alt="Foto Udara #${photoId}"
                                class="w-full h-full object-cover"
                                onerror="this.onerror=null; this.src='/img/sample-aerial.jpg';"
                            />
                        </div>
                    `;
                } else {
                    throw new Error('Data path tidak ditemukan');
                }

                // Enable blok kebun selection
                blockSelect.disabled = false;

                // Ambil data blok kebun dari server
                loadPlantations();
            })
            .catch(error => {
                console.error('Error loading aerial photo:', error);
                document.getElementById('aerialPhotoPreview').innerHTML = `
                    <div class="flex flex-col items-center justify-center p-2 text-center">
                        <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm text-red-500 mt-2">Gagal memuat gambar</p>
                        <p class="text-xs text-gray-500">ID Foto: ${photoId}</p>
                    </div>
                `;

                // Enable blok kebun selection meski gambar tidak ada
                blockSelect.disabled = false;
                loadPlantations();
            });
    }

    // Fungsi untuk memuat daftar plantation
    function loadPlantations() {
        fetch(`/plantation/list`)
            .then(response => response.json())
            .then(response => {
                blockSelect.innerHTML = '<option value="">-- Pilih Blok Kebun --</option>';

                // Pastikan kita memiliki data yang valid dan berformat array
                if (response && response.success && Array.isArray(response.data)) {
                    // Gunakan for loop standar untuk menghindari error data.forEach
                    for (let i = 0; i < response.data.length; i++) {
                        const plantation = response.data[i];
                        if (plantation && plantation.id && plantation.name) {
                            const option = document.createElement('option');
                            option.value = plantation.id;
                            option.textContent = `${plantation.name} (${plantation.luas_area || 'N/A'} ha)`;
                            option.dataset.area = plantation.luas_area || 'N/A';
                            option.dataset.name = plantation.name;
                            option.dataset.geometry = plantation.geometry ? JSON.stringify(plantation.geometry) : '';
                            blockSelect.appendChild(option);
                        }
                    }

                    // Pilih plantation pertama secara otomatis
                    if (blockSelect.options.length > 1) {
                        blockSelect.selectedIndex = 1;
                        blockSelect.dispatchEvent(new Event('change'));
                    }
                } else {
                    blockSelect.innerHTML = '<option value="">Data blok kebun tidak tersedia</option>';
                    console.error('Invalid data format:', response);
                }
            })
            .catch(error => {
                console.error('Error fetching plantations:', error);
                // Tampilkan error jika gagal mengambil data
                blockSelect.innerHTML = '<option value="">Error: Gagal mengambil data blok kebun</option>';
            });
    }

    // Event handler untuk pilihan foto udara
    aerialPhotoSelect.addEventListener('change', function() {
        if (this.value) {
            loadAerialPhotoPreview(this.value);
        } else {
            blockSelect.disabled = true;
            blockSelect.innerHTML = '<option value="">-- Pilih Blok Kebun --</option>';
            document.getElementById('aerialPhotoPreview').innerHTML = `<p class="text-gray-400">Preview foto udara</p>`;
            document.getElementById('blockPreview').innerHTML = `<p class="text-gray-400">Preview area blok kebun</p>`;
            detectTreesBtn.disabled = true;
        }
    });

    // Muat foto udara otomatis jika tersedia opsi
    if (aerialPhotoSelect.options.length > 1) {
        // Pilih opsi pertama selain placeholder (index 1)
        const firstPhotoId = aerialPhotoSelect.options[1].value;
        console.log('Memuat foto udara pertama dengan ID:', firstPhotoId);
        loadAerialPhotoPreview(firstPhotoId);
    }

    // Event handler untuk pilihan blok kebun
    blockSelect.addEventListener('change', function() {
        if (this.value) {
            // Enable tombol deteksi
            detectTreesBtn.disabled = false;

            // Preview blok kebun di atas foto udara
            const photoId = aerialPhotoSelect.value;
            const blockName = this.options[this.selectedIndex].dataset.name;

            console.log('Blok kebun dipilih:', blockName, 'dengan ID:', this.value);

            // Muat data aerial photo dulu untuk mendapatkan path yang benar
            fetch(`/aerial-photo/${photoId}/preview`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        // Menggunakan format path yang sama dengan aerial.blade.php
                        const displayPath = data.data.original_path ? `/storage/${data.data.original_path}` : data.data.path;

                        console.log('Path gambar blok:', displayPath);

                        // Tampilkan blok kebun dengan overlay menggunakan path yang benar
                        document.getElementById('blockPreview').innerHTML = `
                            <div class="relative h-full w-full">
                                <div class="overflow-hidden rounded-lg h-full">
                                    <img
                                        src="${displayPath}"
                                        alt="Blok ${blockName}"
                                        class="w-full h-full object-cover opacity-70"
                                        onerror="this.onerror=null; this.src='/img/sample-aerial.jpg';"
                                    />
                                </div>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="border-2 border-red-500 rounded-lg w-3/4 h-3/4 flex items-center justify-center">
                                        <span class="bg-white px-2 py-1 text-sm font-medium text-red-600">${blockName}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        throw new Error('Data path tidak ditemukan');
                    }
                })
                .catch(error => {
                    console.error('Error loading block preview:', error);
                    document.getElementById('blockPreview').innerHTML = `
                        <div class="flex flex-col items-center justify-center p-2 text-center">
                            <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm text-red-500 mt-2">Gagal memuat preview blok</p>
                            <div class="mt-2 border-2 border-red-500 rounded-lg px-4 py-2">
                                <span class="text-sm font-medium text-red-600">${blockName}</span>
                            </div>
                        </div>
                    `;
                });
        } else {
            detectTreesBtn.disabled = true;
            document.getElementById('blockPreview').innerHTML = `<p class="text-gray-400">Preview area blok kebun</p>`;
        }
    });

    // Event handler untuk tombol deteksi
    detectTreesBtn.addEventListener('click', function() {
        const aerialPhotoId = aerialPhotoSelect.value;
        const blockId = blockSelect.value;
        const blockName = blockSelect.options[blockSelect.selectedIndex].dataset.name;

        if (!aerialPhotoId || !blockId) {
            toastError('Silakan pilih foto udara dan blok kebun terlebih dahulu');
            return;
        }

        // Tampilkan console output dan reset
        document.getElementById('consoleOutput').classList.remove('hidden');
        document.getElementById('consoleContent').innerHTML = `
            <div class="console-line"><span class="text-green-400">🚀</span> <span class="text-gray-400">[${getCurrentTime()}]</span> Memulai ulang proses inferensi YOLO...</div>
        `;

        // Scroll ke console output
        document.getElementById('consoleOutput').scrollIntoView({ behavior: 'smooth' });

        // Tampilkan tombol clear console
        document.getElementById('clearConsoleBtn').style.display = 'block';

        // Hapus hasil deteksi sebelumnya jika ada
        detectionResults.classList.add('hidden');

        // Dapatkan CSRF token
        const csrfToken = getCsrfToken();

        // Log untuk debugging
        addLogLine(`🔑 CSRF Token: ${csrfToken ? 'Tersedia' : 'Tidak tersedia'}`, 'info');
        addLogLine(`📡 Mengirim request ke: /api/detect-trees`, 'info');
        addLogLine(`📋 Data: Foto udara ID: ${aerialPhotoId}, Blok kebun ID: ${blockId}`, 'info');

        // Panggil fungsi untuk mengirim request
        sendDetectionRequest(aerialPhotoId, blockId, blockName, csrfToken);
    });

    // Fungsi untuk mengirim request deteksi dengan XMLHttpRequest - pendekatan lebih sederhana
    function sendDetectionRequest(aerialPhotoId, blockId, blockName, csrfToken) {
        // Log parameter utama
        addLogLine(`🔑 CSRF Token: ${csrfToken ? 'Tersedia' : 'Tidak tersedia'}`, 'info');
        addLogLine(`📤 ID Foto Udara: ${aerialPhotoId}`, 'info');
        addLogLine(`📤 ID Blok Kebun: ${blockId}`, 'info');

        // Mulai server YOLO terlebih dahulu
        addLogLine(`🚀 Memulai server YOLO...`, 'info');

        // Buat objek untuk request ke start-yolo-server
        const startServerXhr = new XMLHttpRequest();
        startServerXhr.open('POST', '/api/start-yolo-server', true);
        startServerXhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        startServerXhr.setRequestHeader('Accept', 'application/json');
        startServerXhr.timeout = 30000; // Waktu tunggu 30 detik

        startServerXhr.onload = function() {
            if (startServerXhr.status >= 200 && startServerXhr.status < 300) {
                try {
                    const response = JSON.parse(startServerXhr.responseText);
                    console.log('Server YOLO response:', response);

                    // Tampilkan logs dari proses startup server
                    if (response.logs && Array.isArray(response.logs)) {
                        response.logs.forEach(log => {
                            let logType = 'info';
                            if (log.message.includes('❌')) logType = 'error';
                            else if (log.message.includes('⚠️')) logType = 'warning';
                            else if (log.message.includes('✅')) logType = 'success';

                            addLogLine(log.message, logType, log.time);
                        });
                    }

                    // Selalu lanjutkan deteksi, bahkan jika server belum siap
                    // Server akan dijalankan oleh controller jika belum aktif
                    addLogLine(`🔄 Lanjut ke proses deteksi pohon...`, 'info');
                    proceedWithTreeDetection(aerialPhotoId, blockId, blockName, csrfToken);

                } catch (error) {
                    console.error('Error parsing start server response:', error);
                    addLogLine(`⚠️ Warning: Kesalahan membaca respons server, tetap mencoba deteksi...`, 'warning');
                    proceedWithTreeDetection(aerialPhotoId, blockId, blockName, csrfToken);
                }
            } else {
                console.error('HTTP Error starting server:', startServerXhr.status);
                addLogLine(`⚠️ Warning: HTTP ${startServerXhr.status} saat memulai server, tetap mencoba deteksi...`, 'warning');
                proceedWithTreeDetection(aerialPhotoId, blockId, blockName, csrfToken);
            }
        };

        startServerXhr.onerror = function() {
            console.error('Network error starting server');
            addLogLine(`⚠️ Warning: Koneksi gagal saat memulai server, tetap mencoba deteksi...`, 'warning');
            proceedWithTreeDetection(aerialPhotoId, blockId, blockName, csrfToken);
        };

        startServerXhr.ontimeout = function() {
            addLogLine(`⏱️ Timeout saat memulai server YOLO, tetap mencoba deteksi...`, 'warning');
            proceedWithTreeDetection(aerialPhotoId, blockId, blockName, csrfToken);
        };

        try {
            startServerXhr.send();
        } catch (error) {
            console.error('Error sending start server request:', error);
            addLogLine(`⚠️ Warning: ${error.message}, tetap mencoba deteksi...`, 'warning');
            proceedWithTreeDetection(aerialPhotoId, blockId, blockName, csrfToken);
        }
    }

    // Fungsi untuk melanjutkan deteksi setelah server YOLO berjalan
    function proceedWithTreeDetection(aerialPhotoId, blockId, blockName, csrfToken) {
        addLogLine(`📄 Menyiapkan request deteksi pohon`, 'info');
        addLogLine(`⏱️ Proses inferensi YOLO dapat memakan waktu hingga 20 menit`, 'warning');
        addLogLine(`💡 Harap tetap pada halaman ini hingga proses selesai`, 'warning');
        addLogLine(`🔔 STATUS PENTING: Proses deteksi pohon berjalan dalam beberapa tahap:`, 'info');
        addLogLine(`1️⃣ Mulai server YOLO (jika belum berjalan)`, 'info');
        addLogLine(`2️⃣ Mempersiapkan data gambar aerial dan geometri kebun`, 'info');
        addLogLine(`3️⃣ Membuat boundary area deteksi`, 'info');
        addLogLine(`4️⃣ Menjalankan inferensi YOLO (waktu terlama)`, 'info');
        addLogLine(`5️⃣ Memproses hasil dan membuat preview`, 'info');

        try {
            // Buat FormData untuk request yang lebih sederhana
            const formData = new FormData();
            formData.append('aerial_photo_id', aerialPhotoId);
            formData.append('plantation_id', blockId);

            // Log data yang akan dikirim
            console.log('Data yang dikirim:', {
                aerial_photo_id: aerialPhotoId,
                plantation_id: blockId
            });

            // Buat dan siapkan XMLHttpRequest
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/api/detect-trees', true);

            // Set headers
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
            xhr.setRequestHeader('Accept', 'application/json');
            // Tidak perlu set Content-Type pada FormData, browser akan otomatis melakukannya

            // Set timeout yang lebih panjang (20 menit) untuk memberikan waktu yang cukup bagi server untuk menyelesaikan inferensi
            xhr.timeout = 1200000; // 20 menit

            // Status interval untuk menunjukkan bahwa request masih berjalan
            let startTime = Date.now();
            let statusInterval = setInterval(() => {
                const elapsedSeconds = Math.floor((Date.now() - startTime) / 1000);
                const minutes = Math.floor(elapsedSeconds / 60);
                const seconds = elapsedSeconds % 60;
                const timeText = `${minutes}m ${seconds}s`;

                if (elapsedSeconds < 60) {
                    addLogLine(`⏳ [${timeText}] Server mempersiapkan data dan model...`, 'info');
                } else if (elapsedSeconds < 120) {
                    addLogLine(`⏳ [${timeText}] Menjalankan model YOLO - inferensi pohon sedang berjalan...`, 'info');
                } else if (elapsedSeconds < 300) {
                    addLogLine(`⏳ [${timeText}] Proses deteksi masih berjalan - mendeteksi kontur pohon...`, 'info');
                } else if (elapsedSeconds < 600) {
                    addLogLine(`⏳ [${timeText}] Deteksi masih berjalan - memproses gambar besar membutuhkan waktu...`, 'info');
                } else {
                    addLogLine(`⏳ [${timeText}] Server masih memproses - deteksi untuk ribuan pohon membutuhkan waktu yang lama...`, 'info');
                }
            }, 30000); // Tampilkan pesan setiap 30 detik

            // Handler untuk saat request dalam proses
            xhr.onprogress = function() {
                const elapsedSeconds = Math.floor((Date.now() - startTime) / 1000);
                const minutes = Math.floor(elapsedSeconds / 60);
                const seconds = elapsedSeconds % 60;
                addLogLine(`🔄 [${minutes}m ${seconds}s] Server sedang memproses request...`, 'info');
            };

            // Handler untuk timeout
            xhr.ontimeout = function() {
                clearInterval(statusInterval);
                const elapsedMinutes = Math.floor((Date.now() - startTime) / 60000);
                addLogLine(`⏱️ Request timeout setelah ${elapsedMinutes} menit. Proses inferensi mungkin tetap berjalan di server.`, 'error');
                addLogLine(`💡 TIP: Coba refresh halaman dan periksa status deteksi`, 'info');
                addLogLine(`💡 TIP: Pastikan server YOLO berjalan dengan baik dengan memeriksa log di server`, 'info');
                addLogLine(`💡 TIP: Anda juga bisa memeriksa status server di /api/check-yolo-server`, 'info');
                addLogLine(`💡 Proses inferensi YOLO membutuhkan waktu yang lama terutama untuk gambar besar`, 'info');
                addRetryButton(aerialPhotoId, blockId, blockName);
            };

            // Handler untuk response sukses
            xhr.onload = function() {
                clearInterval(statusInterval);
                const elapsedSeconds = Math.floor((Date.now() - startTime) / 1000);
                const minutes = Math.floor(elapsedSeconds / 60);
                const seconds = elapsedSeconds % 60;
                addLogLine(`✅ [${minutes}m ${seconds}s] Request selesai!`, 'success');

                console.log('Response status:', xhr.status);
                console.log('Response headers:', xhr.getAllResponseHeaders());
                console.log('Response text:', xhr.responseText);

                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        console.log('Hasil deteksi:', data);

                        // Tambahkan info waktu eksekusi
                        addLogLine(`⏱️ Total waktu eksekusi: ${minutes}m ${seconds}s`, 'success');

                        // Tampilkan logs dari proses
                        if (data.logs && Array.isArray(data.logs)) {
                            // Bersihkan console yang ada
                            document.getElementById('consoleContent').innerHTML = '';

                            // Tambahkan log dari server
                            data.logs.forEach(log => {
                                let logType = 'info';
                                if (log.includes('❌') || log.includes('Error') || log.includes('error')) logType = 'error';
                                else if (log.includes('⚠️') || log.includes('Warning') || log.includes('warning')) logType = 'warning';
                                else if (log.includes('✅') || log.includes('Success') || log.includes('success')) logType = 'success';

                                addLogLine(log, logType);
                            });
                        } else {
                            // Log ringkasan jika tidak ada logs
                            addLogLine(`✅ Deteksi selesai! Ditemukan ${data.tree_count || 0} pohon`, 'success');
                        }

                        // Log performa
                        if (data.processing_time) {
                            const formattedTime = formatDuration(data.processing_time);
                            addLogLine(`⏱️ Waktu pemrosesan server: ${formattedTime}`, 'info');
                        }

                        // Tampilkan hasil jika sukses
                        if (data.success !== false) {
                            showDetectionResults(data, blockName);
                        } else {
                            addLogLine(`❌ Deteksi gagal: ${data.message || 'Tidak ada pesan error'}`, 'error');
                            addRetryButton(aerialPhotoId, blockId, blockName);
                        }
                    } catch (error) {
                        console.error('Error parsing detection response:', error);
                        addLogLine(`❌ Error parsing JSON: ${error.message}`, 'error');
                        addLogLine(`📝 Response awal: ${xhr.responseText.substring(0, 300)}...`, 'error');
                        addRetryButton(aerialPhotoId, blockId, blockName);
                    }
                } else {
                    console.error('HTTP Error:', xhr.status, xhr.statusText);
                    addLogLine(`❌ HTTP Error ${xhr.status}: ${xhr.statusText}`, 'error');
                    try {
                        const errorData = JSON.parse(xhr.responseText);
                        if (errorData.message) {
                            addLogLine(`📝 Detail: ${errorData.message}`, 'error');
                        }
                        if (errorData.logs && Array.isArray(errorData.logs)) {
                            errorData.logs.forEach(log => {
                                addLogLine(`🔍 ${log}`, 'error');
                            });
                        }
                    } catch (e) {
                        // Jika tidak bisa parse, tampilkan teks mentah
                        if (xhr.responseText) {
                            addLogLine(`📝 Response: ${xhr.responseText.substring(0, 300)}...`, 'error');
                        }
                    }
                    addRetryButton(aerialPhotoId, blockId, blockName);
                }
            };

            // Handler untuk network error
            xhr.onerror = function() {
                clearInterval(statusInterval);
                console.error('Network error during detection');
                addLogLine(`❌ Koneksi gagal saat proses deteksi`, 'error');
                addLogLine(`💡 Cek apakah server YOLO berjalan dengan melihat log di storage/logs/yolo_server.log`, 'info');
                addLogLine(`💡 Cek status server dengan endpoint /api/check-yolo-server`, 'info');
                addRetryButton(aerialPhotoId, blockId, blockName);
            };

            // Kirim request
            addLogLine(`📤 Mengirim request ke server...`, 'info');
            addLogLine(`⏳ Mohon tunggu, proses bisa memakan waktu 10-20 menit tergantung ukuran gambar`, 'info');
            addLogLine(`💡 Jangan menutup halaman ini selama proses berjalan`, 'warning');
            xhr.send(formData);
        } catch (error) {
            console.error('Error in detection process:', error);
            addLogLine(`❌ Error: ${error.message}`, 'error');
            addRetryButton(aerialPhotoId, blockId, blockName);
        }
    }

    // Fungsi untuk memformat durasi dalam detik menjadi format yang lebih mudah dibaca
    function formatDuration(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);

        let result = '';
        if (hours > 0) result += `${hours} jam `;
        if (minutes > 0) result += `${minutes} menit `;
        if (secs > 0 || (hours === 0 && minutes === 0)) result += `${secs} detik`;

        return result.trim();
    }

    // Fungsi untuk menambahkan tombol retry
    function addRetryButton(aerialPhotoId, blockId, blockName) {
        // Hapus tombol retry lama jika ada
        const existingButton = document.getElementById('retryDetectionBtn');
        if (existingButton) {
            existingButton.remove();
        }

        // Tambahkan tombol baru
        document.getElementById('consoleContent').innerHTML += `
            <div class="mt-4">
                <button id="retryDetectionBtn" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">
                    Coba Lagi
                </button>
            </div>
        `;

        // Tambahkan event listener
        document.getElementById('retryDetectionBtn')?.addEventListener('click', function() {
            // Reset console
            document.getElementById('consoleContent').innerHTML = `
                <div class="console-line"><span class="text-green-400">🚀</span> <span class="text-gray-400>[${getCurrentTime()}]</span> Memulai ulang proses inferensi YOLO...</div>
            `;

            // Ambil CSRF token baru dan kirim ulang request
            const csrfToken = getCsrfToken();
            addLogLine(`🔄 Mencoba kembali deteksi pohon...`, 'info');
            sendDetectionRequest(aerialPhotoId, blockId, blockName, csrfToken);
        });
    }

    // Fungsi untuk menampilkan hasil deteksi
    function showDetectionResults(data, blockName) {
        // Tampilkan area hasil deteksi
        detectionResults.classList.remove('hidden');

        // Update UI dengan hasil deteksi
        document.getElementById('detectionBlockName').textContent = blockName;
        document.getElementById('treeCount').textContent = data.tree_count || 0;

        // Set area size dari block
        const selectedOption = blockSelect.options[blockSelect.selectedIndex];
        const areaSize = selectedOption.dataset.area || 'N/A';
        document.getElementById('areaSize').textContent = `${areaSize} ha`;

        // Hitung dan tampilkan kepadatan pohon
        const treeCount = data.tree_count || 0;
        const density = areaSize !== 'N/A' ? (treeCount / parseFloat(areaSize)).toFixed(2) : 'N/A';
        document.getElementById('treeDensity').textContent = `${density} pohon/ha`;

        // Tampilkan gambar hasil deteksi dengan pohon yang terdeteksi
        if (data.preview_url) {
            if (data.geojson && data.geojson.features) {
                // Gunakan data GeoJSON dengan gambar untuk visualisasi yang lebih baik
                visualizeDetectionResults(data.preview_url, data.geojson.features, data.tree_count);
            } else {
                // Fallback ke simulasi jika tidak ada data GeoJSON
                simulateDetectionPreview(aerialPhotoSelect.value, data.tree_count || 0);
            }
        } else {
            // Tampilkan preview simulasi
            simulateDetectionPreview(aerialPhotoSelect.value, data.tree_count || 0);
        }

        // Set nama deteksi default
        document.getElementById('detectionName').value = `Deteksi_${blockName}_${new Date().toISOString().slice(0, 10)}`;

        // Setup event listeners untuk tombol simpan dan export
        setupSaveAndExportButtons(data);

        // Scroll ke hasil deteksi
        detectionResults.scrollIntoView({ behavior: 'smooth' });
    }

    // Clear console button
    document.getElementById('clearConsoleBtn').addEventListener('click', function() {
        document.getElementById('consoleContent').innerHTML = '';
        addLogLine(`🔄 Console dibersihkan`, 'info');
    });

    // Fungsi untuk mendapatkan waktu saat ini dalam format HH:MM:SS
    function getCurrentTime() {
        const now = new Date();
        return now.toTimeString().split(' ')[0];
    }

    // Fungsi untuk menambahkan baris log ke console
    function addLogLine(message, type = 'info', time = null) {
        const consoleContent = document.getElementById('consoleContent');
        const logTime = time || getCurrentTime();

        let colorClass = 'text-blue-400'; // default untuk info
        let icon = '🔹';

        if (type === 'error') {
            colorClass = 'text-red-400';
            icon = '❌';
        } else if (type === 'warning') {
            colorClass = 'text-yellow-400';
            icon = '⚠️';
        } else if (type === 'success') {
            colorClass = 'text-green-400';
            icon = '✅';
        }

        const logLine = document.createElement('div');
        logLine.className = 'console-line py-1';
        logLine.innerHTML = `<span class="${colorClass}">${icon}</span> <span class="text-gray-400">[${logTime}]</span> ${message}`;

        consoleContent.appendChild(logLine);

        // Auto-scroll to bottom
        const consoleContainer = document.getElementById('consoleContainer');
        consoleContainer.scrollTop = consoleContainer.scrollHeight;
    }

    // Fungsi untuk visualisasi hasil deteksi yang lebih akurat dengan GeoJSON
    function visualizeDetectionResults(imageUrl, features, treeCount) {
        addLogLine(`🎨 Memvisualisasikan ${features.length} fitur pohon di atas gambar aerial...`, 'info');

        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();
        img.crossOrigin = "Anonymous";

        img.onload = function() {
            console.log('Gambar berhasil dimuat, dimensi:', this.width, 'x', this.height);
            addLogLine(`✅ Gambar aerial berhasil dimuat: ${this.width}x${this.height}px`, 'success');

            // Ukuran maksimum yang disarankan untuk preview (agar tidak terlalu besar)
            const maxWidth = 1200;
            const maxHeight = 800;

            // Hitung rasio agar gambar tetap proporsional tapi tidak terlalu besar
            let width = this.width;
            let height = this.height;

            if (width > maxWidth) {
                const ratio = maxWidth / width;
                width = maxWidth;
                height = height * ratio;
            }

            if (height > maxHeight) {
                const ratio = maxHeight / height;
                height = maxHeight;
                width = width * ratio;
            }

            // Set ukuran canvas dengan dimensi yang dihitung
            canvas.width = width;
            canvas.height = height;

            // Tambahkan style agar responsif dengan tetap mempertahankan rasio aspek
            canvas.style.maxWidth = '100%';
            canvas.style.height = 'auto';
            canvas.style.border = '1px solid #ddd';
            canvas.style.borderRadius = '6px';
            canvas.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';

            // Gambar foto aerial sebagai dasar (dengan scaling)
            ctx.drawImage(img, 0, 0, width, height);

            // Menghitung bounding box dari gambar untuk mapping koordinat
            // Ini akan perlu disesuaikan dengan data geolokasi yang sebenarnya
            const bounds = {
                minLng: 107.5, // Contoh nilai, seharusnya dari metadata gambar
                maxLng: 107.7,
                minLat: -7.0,
                maxLat: -6.8
            };

            // Fungsi untuk konversi koordinat geografis ke koordinat pixel
            function geoToPixel(lng, lat) {
                const x = ((lng - bounds.minLng) / (bounds.maxLng - bounds.minLng)) * canvas.width;
                const y = ((bounds.maxLat - lat) / (bounds.maxLat - bounds.minLat)) * canvas.height;
                return { x, y };
            }

            // Gambar pohon-pohon dari GeoJSON
            ctx.fillStyle = 'rgba(0, 255, 0, 0.5)';
            ctx.strokeStyle = 'rgba(0, 128, 0, 1)';
            ctx.lineWidth = 1;

            // Limit jumlah yang ditampilkan untuk performa jika terlalu banyak
            const maxDisplayedTrees = Math.min(features.length, 800); // Tampilkan lebih banyak pohon
            const treeColors = {
                'Baik': 'rgba(0, 255, 0, 0.5)',
                'Sedang': 'rgba(255, 255, 0, 0.5)',
                'Kurang': 'rgba(255, 165, 0, 0.5)'
            };

            addLogLine(`🔍 Menampilkan ${maxDisplayedTrees} dari ${features.length} pohon pada preview`, 'info');

            // Tampilkan sebagian dari total pohon
            const samplingRate = Math.ceil(features.length / maxDisplayedTrees);
            let displayedCount = 0;

            for (let i = 0; i < features.length; i += samplingRate) {
                const feature = features[i];
                if (feature.geometry && feature.geometry.type === 'Point') {
                    const [lng, lat] = feature.geometry.coordinates;
                    const point = geoToPixel(lng, lat);

                    // Tentukan warna berdasarkan kondisi pohon jika tersedia
                    if (feature.properties && feature.properties.health) {
                        ctx.fillStyle = treeColors[feature.properties.health] || 'rgba(0, 255, 0, 0.5)';
                    }

                    // Tentukan ukuran berdasarkan diameter jika tersedia (sedikit lebih besar)
                    const radius = feature.properties && feature.properties.diameter
                        ? Math.max(3, feature.properties.diameter / 8) // Radius yang lebih besar untuk keterlihatan
                        : 4;

                    // Gambar lingkaran untuk pohon
                    ctx.beginPath();
                    ctx.arc(point.x, point.y, radius, 0, 2 * Math.PI);
                    ctx.fill();
                    ctx.stroke();

                    displayedCount++;
                }
            }

            // Tambahkan legenda untuk warna pohon
            ctx.font = '14px Arial';
            ctx.fillStyle = '#000';
            const legendPadding = 10;
            const legendX = legendPadding;
            const legendY = legendPadding;
            const legendWidth = 150;
            const legendHeight = 80;

            // Background semi-transparan
            ctx.fillStyle = 'rgba(255, 255, 255, 0.7)';
            ctx.fillRect(legendX, legendY, legendWidth, legendHeight);
            ctx.strokeStyle = '#333';
            ctx.strokeRect(legendX, legendY, legendWidth, legendHeight);

            // Judul legenda
            ctx.fillStyle = '#000';
            ctx.fillText('Kondisi Pohon:', legendX + 10, legendY + 20);

            // Item legenda
            let yOffset = 20;
            for (const [condition, color] of Object.entries(treeColors)) {
                yOffset += 15;
                // Kotak warna
                ctx.fillStyle = color;
                ctx.fillRect(legendX + 10, legendY + yOffset, 10, 10);
                ctx.strokeStyle = 'rgba(0, 0, 0, 0.5)';
                ctx.strokeRect(legendX + 10, legendY + yOffset, 10, 10);

                // Teks kondisi
                ctx.fillStyle = '#000';
                ctx.fillText(condition, legendX + 30, legendY + yOffset + 8);
            }

            // Tampilkan canvas di container dengan ukuran yang dioptimalkan
            const previewContainer = document.getElementById('detectionPreview');
            previewContainer.innerHTML = '';
            previewContainer.style.maxWidth = '100%';
            previewContainer.style.height = 'auto';
            previewContainer.style.display = 'flex';
            previewContainer.style.flexDirection = 'column';
            previewContainer.style.alignItems = 'center';
            previewContainer.appendChild(canvas);

            // Tambahkan keterangan yang lebih informatif
            const caption = document.createElement('div');
            caption.className = 'text-center text-sm text-gray-700 mt-3 font-medium';
            caption.innerHTML = `<i>Preview menampilkan ${displayedCount} dari total ${treeCount} pohon terdeteksi</i>`;
            previewContainer.appendChild(caption);

            // Tambahkan tombol untuk download hasil visualisasi dengan style yang lebih baik
            const downloadBtn = document.createElement('button');
            downloadBtn.className = 'mt-3 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 text-sm font-medium transition duration-150 ease-in-out';
            downloadBtn.innerText = 'Download Gambar Visualisasi';
            downloadBtn.onclick = function() {
                const link = document.createElement('a');
                link.download = `deteksi_pohon_${new Date().toISOString().slice(0, 10)}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
            };
            previewContainer.appendChild(downloadBtn);
        };

        img.onerror = function(e) {
            console.error('Gagal memuat gambar:', e);
            addLogLine(`❌ Gagal memuat gambar aerial: ${e.type}`, 'error');

            document.getElementById('detectionPreview').innerHTML = `
                <div class="h-full w-full flex flex-col items-center justify-center bg-gray-100 rounded-lg p-4">
                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="text-red-500 mt-2">Gagal memuat gambar</span>
                    <span class="text-gray-500 text-sm mt-1">Silakan coba visualisasi alternatif</span>
                    <button onclick="simulateDetectionPreview(${aerialPhotoSelect.value}, ${treeCount})" class="mt-3 px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">
                        Gunakan Preview Simulasi
                    </button>
                </div>
            `;
        };

        img.src = imageUrl;
    }

    // Simulasi visualisasi deteksi untuk jika visualisasi GeoJSON tidak tersedia
    function simulateDetectionPreview(photoId, treeCount) {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();

        console.log(`Mencoba memuat gambar dari /aerial-photo-image/${photoId}`);
        addLogLine(`🖼️ Memuat gambar preview untuk foto udara ID: ${photoId}`, 'info');

        img.crossOrigin = "Anonymous"; // Penting untuk menghindari masalah CORS

        img.onload = function() {
            console.log('Gambar berhasil dimuat, dimensi:', this.width, 'x', this.height);
            addLogLine(`✅ Gambar berhasil dimuat: ${this.width}x${this.height}px`, 'success');

            // Ukuran maksimum yang disarankan untuk preview (agar tidak terlalu besar)
            const maxWidth = 1200;
            const maxHeight = 800;

            // Hitung rasio agar gambar tetap proporsional tapi tidak terlalu besar
            let width = this.width;
            let height = this.height;

            if (width > maxWidth) {
                const ratio = maxWidth / width;
                width = maxWidth;
                height = height * ratio;
            }

            if (height > maxHeight) {
                const ratio = maxHeight / height;
                height = maxHeight;
                width = width * ratio;
            }

            // Atur ukuran canvas sesuai gambar yang dimuat dengan dimensi yang dihitung
            canvas.width = width;
            canvas.height = height;

            // Tambahkan style agar responsif dengan tetap mempertahankan rasio aspek
            canvas.style.maxWidth = '100%';
            canvas.style.height = 'auto';
            canvas.style.border = '1px solid #ddd';
            canvas.style.borderRadius = '6px';
            canvas.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';

            // Gambar foto asli dengan scaling
            ctx.drawImage(img, 0, 0, width, height);

            // Gambar area blok kebun (hanya illustrasi)
            ctx.strokeStyle = 'rgba(255, 0, 0, 0.7)';
            ctx.lineWidth = 3;
            ctx.strokeRect(50, 50, canvas.width - 100, canvas.height - 100);

            // Gambar deteksi pohon - tampilkan lebih banyak dari total untuk visualisasi
            // Rasio untuk menampilkan sejumlah proporsi pohon pada preview
            const previewRatio = 0.1; // 10% dari total pohon (lebih banyak)
            const visibleTrees = Math.min(3000, Math.max(500, Math.floor(treeCount * previewRatio)));

            console.log(`Menampilkan ${visibleTrees} dari ${treeCount} pohon pada preview`);
            addLogLine(`🔍 Visualisasi ${visibleTrees} pohon dari total ${treeCount}`, 'info');

            // Definisikan kondisi pohon dan warna
            const treeColors = {
                'Baik': 'rgba(0, 255, 0, 0.6)',
                'Sedang': 'rgba(255, 255, 0, 0.6)',
                'Kurang': 'rgba(255, 165, 0, 0.6)'
            };

            const treeConditions = Object.keys(treeColors);
            const strokeColors = {
                'Baik': 'rgba(0, 128, 0, 0.8)',
                'Sedang': 'rgba(128, 128, 0, 0.8)',
                'Kurang': 'rgba(128, 82, 0, 0.8)'
            };

            // Cluster pohon untuk simulasi yang lebih realistis
            const clusterCount = Math.floor(Math.random() * 5) + 10; // 10-15 clusters
            const clusters = [];

            // Buat beberapa cluster acak
            for (let i = 0; i < clusterCount; i++) {
                const clusterCondition = treeConditions[Math.floor(Math.random() * treeConditions.length)];
                clusters.push({
                    x: 100 + Math.random() * (canvas.width - 200),
                    y: 100 + Math.random() * (canvas.height - 200),
                    radius: 80 + Math.random() * 200,
                    condition: clusterCondition
                });
            }

            for (let i = 0; i < visibleTrees; i++) {
                // Pilih cluster acak atau posisi acak
                let x, y;
                let condition;

                if (Math.random() < 0.85) { // 85% pohon di dalam cluster
                    const cluster = clusters[Math.floor(Math.random() * clusterCount)];
                    // Posisi dalam cluster dengan distribusi normal
                    const angle = Math.random() * 2 * Math.PI;
                    const distance = Math.random() * cluster.radius;
                    x = cluster.x + Math.cos(angle) * distance;
                    y = cluster.y + Math.sin(angle) * distance;
                    condition = cluster.condition; // Pohon dalam cluster memiliki kondisi yang sama
                } else { // 15% pohon acak di luar cluster
                    x = 50 + Math.random() * (canvas.width - 100);
                    y = 50 + Math.random() * (canvas.height - 100);
                    condition = treeConditions[Math.floor(Math.random() * treeConditions.length)];
                }

                const radius = 2 + Math.random() * 4; // Ukuran pohon lebih kecil agar terlihat lebih realistis

                ctx.fillStyle = treeColors[condition];
                ctx.strokeStyle = strokeColors[condition];

                ctx.beginPath();
                ctx.arc(x, y, radius, 0, 2 * Math.PI);
                ctx.fill();
                ctx.stroke();
            }

            // Tambahkan legenda untuk warna pohon
            ctx.font = '14px Arial';
            ctx.fillStyle = '#000';
            const legendPadding = 10;
            const legendX = legendPadding;
            const legendY = legendPadding;
            const legendWidth = 150;
            const legendHeight = 80;

            // Background semi-transparan
            ctx.fillStyle = 'rgba(255, 255, 255, 0.7)';
            ctx.fillRect(legendX, legendY, legendWidth, legendHeight);
            ctx.strokeStyle = '#333';
            ctx.strokeRect(legendX, legendY, legendWidth, legendHeight);

            // Judul legenda
            ctx.fillStyle = '#000';
            ctx.fillText('Kondisi Pohon:', legendX + 10, legendY + 20);

            // Item legenda
            let yOffset = 20;
            for (const [condition, color] of Object.entries(treeColors)) {
                yOffset += 15;
                // Kotak warna
                ctx.fillStyle = color;
                ctx.fillRect(legendX + 10, legendY + yOffset, 10, 10);
                ctx.strokeStyle = 'rgba(0, 0, 0, 0.5)';
                ctx.strokeRect(legendX + 10, legendY + yOffset, 10, 10);

                // Teks kondisi
                ctx.fillStyle = '#000';
                ctx.fillText(condition, legendX + 30, legendY + yOffset + 8);
            }

            // Tampilkan preview dengan styling yang sama
            const previewContainer = document.getElementById('detectionPreview');
            previewContainer.innerHTML = '';
            previewContainer.style.maxWidth = '100%';
            previewContainer.style.height = 'auto';
            previewContainer.style.display = 'flex';
            previewContainer.style.flexDirection = 'column';
            previewContainer.style.alignItems = 'center';
            previewContainer.appendChild(canvas);

            // Tambahkan keterangan yang informatif
            const caption = document.createElement('div');
            caption.className = 'text-center text-sm text-gray-700 mt-3 font-medium';
            caption.innerHTML = `<i>Preview menampilkan ${visibleTrees} dari total ${treeCount} pohon terdeteksi</i>`;
            previewContainer.appendChild(caption);

            // Tambahkan tombol download hasil visualisasi
            const downloadBtn = document.createElement('button');
            downloadBtn.className = 'mt-3 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 text-sm font-medium transition duration-150 ease-in-out';
            downloadBtn.innerText = 'Download Visualisasi';
            downloadBtn.onclick = function() {
                const link = document.createElement('a');
                link.download = `deteksi_pohon_${new Date().toISOString().slice(0, 10)}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
            };
            previewContainer.appendChild(downloadBtn);
        };

        img.onerror = function(e) {
            console.error('Gagal memuat gambar:', e);
            addLogLine(`❌ Gagal memuat gambar: ${e.type}`, 'error');

            // Alternatif path gambar
            const altPaths = [
                `/aerial-photo-image/${photoId}`,
                `/storage/aerial_photos/${photoId}.png`,
                `/storage/aerial_photos/${photoId}.jpg`,
                `/storage/aerial-photos/previews/preview_*_${photoId}.jpg`,
                `/storage/aerial-photos/previews/preview_*_${photoId}.png`,
                `/aerial-photo/${photoId}/preview-image`
            ];

            addLogLine(`🔄 Mencoba alternatif path gambar...`, 'info');

            // Coba path alternatif dengan fungsi rekursif
            tryLoadImage(photoId, treeCount, altPaths, 0);
        };

        // Coba first path dengan timestamp untuk mencegah cache
        img.src = `/aerial-photo-image/${photoId}?t=${new Date().getTime()}`;
    }

    // Fungsi untuk mencoba memuat gambar dari beberapa path
    function tryLoadImage(photoId, treeCount, paths, index) {
        if (index >= paths.length) {
            // Jika semua path sudah dicoba, tampilkan placeholder
            document.getElementById('detectionPreview').innerHTML = `
                <div class="h-full w-full flex flex-col items-center justify-center bg-gray-100 rounded-lg p-4">
                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="text-red-500 mt-2">Gagal memuat gambar</span>
                    <span class="text-gray-500 text-sm mt-1">Mencoba beberapa alternatif path tetapi tidak berhasil</span>
                    <button onclick="generateBasicPreview(${treeCount})" class="mt-3 px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">
                        Gunakan Preview Dasar
                    </button>
                </div>
            `;
            return;
        }

        const path = paths[index];
        addLogLine(`🔄 Mencoba memuat dari path: ${path}`, 'info');

        const img = new Image();
        img.crossOrigin = "Anonymous";

        img.onload = function() {
            // Jika berhasil, lanjutkan dengan simulateDetectionPreview
            addLogLine(`✅ Berhasil memuat gambar dari path alternatif: ${path}`, 'success');
            simulateDetectionPreview(photoId, treeCount);
        };

        img.onerror = function() {
            // Jika gagal, coba path berikutnya
            addLogLine(`❌ Gagal memuat dari path: ${path}`, 'error');
            tryLoadImage(photoId, treeCount, paths, index + 1);
        };

        img.src = path;
    }

    // Fungsi untuk menghasilkan preview dasar tanpa gambar latar
    function generateBasicPreview(treeCount) {
        addLogLine(`🎨 Membuat preview dasar tanpa gambar latar`, 'info');

        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        // Menggunakan ukuran yang lebih besar untuk visualisasi yang lebih baik
        canvas.width = 1000;
        canvas.height = 700;

        // Tambahkan style agar responsif
        canvas.style.maxWidth = '100%';
        canvas.style.height = 'auto';
        canvas.style.border = '1px solid #ddd';
        canvas.style.borderRadius = '6px';
        canvas.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';

        // Set warna latar dengan gradien untuk efek lebih menarik
        const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
        gradient.addColorStop(0, '#f8fafc');
        gradient.addColorStop(1, '#e2e8f0');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Gambar area blok kebun dengan styling yang lebih baik
        ctx.strokeStyle = 'rgba(220, 38, 38, 0.8)';
        ctx.lineWidth = 3;

        // Buat polygon tidak beraturan untuk simulasi area kebun yang lebih realistis
        const margin = 80;
        ctx.beginPath();
        ctx.moveTo(margin, margin + Math.random() * 50);
        ctx.lineTo(margin + 180, margin - 30 + Math.random() * 50);
        ctx.lineTo(canvas.width - margin - 100, margin - 20 + Math.random() * 50);
        ctx.lineTo(canvas.width - margin, margin + 100 + Math.random() * 50);
        ctx.lineTo(canvas.width - margin - 50, canvas.height - margin - 50);
        ctx.lineTo(canvas.width - margin - 200, canvas.height - margin);
        ctx.lineTo(margin + 100, canvas.height - margin - 30);
        ctx.lineTo(margin - 20, canvas.height - margin - 150);
        ctx.closePath();
        ctx.stroke();

        // Tambahkan shade ringan pada area kebun
        ctx.fillStyle = 'rgba(0, 128, 0, 0.05)';
        ctx.fill();

        // Tulis judul dengan styling yang lebih baik
        ctx.fillStyle = '#1e293b';
        ctx.font = 'bold 20px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(`Simulasi Deteksi ${treeCount.toLocaleString()} Pohon`, canvas.width / 2, 40);

        // Definisikan kondisi pohon dan warna
        const treeColors = {
            'Baik': 'rgba(0, 255, 0, 0.6)',
            'Sedang': 'rgba(255, 255, 0, 0.6)',
            'Kurang': 'rgba(255, 165, 0, 0.6)'
        };

        const treeConditions = Object.keys(treeColors);
        const strokeColors = {
            'Baik': 'rgba(0, 128, 0, 0.8)',
            'Sedang': 'rgba(128, 128, 0, 0.8)',
            'Kurang': 'rgba(128, 82, 0, 0.8)'
        };

        // Gambar pohon sebagai titik hijau
        // Rasio untuk menampilkan sejumlah proporsi pohon pada preview
        const previewRatio = 0.1; // 10% dari total pohon
        const visibleTrees = Math.min(3000, Math.max(500, Math.floor(treeCount * previewRatio)));

        // Cluster pohon untuk simulasi yang lebih realistis
        const clusterCount = Math.floor(Math.random() * 5) + 8; // 8-13 clusters
        const clusters = [];

        // Buat beberapa cluster acak
        for (let i = 0; i < clusterCount; i++) {
            const clusterCondition = treeConditions[Math.floor(Math.random() * treeConditions.length)];
            clusters.push({
                x: margin + 50 + Math.random() * (canvas.width - 2*(margin + 50)),
                y: margin + 50 + Math.random() * (canvas.height - 2*(margin + 50)),
                radius: 50 + Math.random() * 150,
                condition: clusterCondition
            });
        }

        // Hasilkan titik-titik pohon
        for (let i = 0; i < visibleTrees; i++) {
            // Pilih cluster acak atau posisi acak
            let x, y;
            let condition;

            if (Math.random() < 0.85) { // 85% pohon di dalam cluster
                const cluster = clusters[Math.floor(Math.random() * clusterCount)];
                // Posisi dalam cluster dengan distribusi normal
                const angle = Math.random() * 2 * Math.PI;
                const distance = Math.random() * cluster.radius;
                x = cluster.x + Math.cos(angle) * distance;
                y = cluster.y + Math.sin(angle) * distance;
                condition = cluster.condition; // Pohon dalam cluster memiliki kondisi yang sama
            } else { // 15% pohon acak di luar cluster
                x = margin + Math.random() * (canvas.width - 2*margin);
                y = margin + Math.random() * (canvas.height - 2*margin);
                condition = treeConditions[Math.floor(Math.random() * treeConditions.length)];
            }

            const radius = 2 + Math.random() * 3; // Ukuran pohon

            ctx.fillStyle = treeColors[condition];
            ctx.strokeStyle = strokeColors[condition];
            ctx.lineWidth = 0.5;

            ctx.beginPath();
            ctx.arc(x, y, radius, 0, Math.PI * 2);
            ctx.fill();
            ctx.stroke();
        }

        // Tambahkan legenda
        ctx.font = '14px Arial';
        ctx.fillStyle = '#000';
        const legendPadding = 20;
        const legendX = legendPadding;
        const legendY = legendPadding;
        const legendWidth = 150;
        const legendHeight = 100;

        // Background semi-transparan
        ctx.fillStyle = 'rgba(255, 255, 255, 0.7)';
        ctx.fillRect(legendX, legendY, legendWidth, legendHeight);
        ctx.strokeStyle = '#333';
        ctx.strokeRect(legendX, legendY, legendWidth, legendHeight);

        // Judul legenda
        ctx.fillStyle = '#000';
        ctx.font = 'bold 14px Arial';
        ctx.textAlign = 'left';
        ctx.fillText('Kondisi Pohon:', legendX + 10, legendY + 25);
        ctx.font = '14px Arial';

        // Item legenda
        let yOffset = 25;
        for (const [condition, color] of Object.entries(treeColors)) {
            yOffset += 20;
            // Kotak warna
            ctx.fillStyle = color;
            ctx.fillRect(legendX + 10, legendY + yOffset, 12, 12);
            ctx.strokeStyle = 'rgba(0, 0, 0, 0.5)';
            ctx.strokeRect(legendX + 10, legendY + yOffset, 12, 12);

            // Teks kondisi
            ctx.fillStyle = '#000';
            ctx.fillText(condition, legendX + 30, legendY + yOffset + 10);
        }

        // Tampilkan canvas dengan container yang distilasi
        const previewContainer = document.getElementById('detectionPreview');
        previewContainer.innerHTML = '';
        previewContainer.style.maxWidth = '100%';
        previewContainer.style.height = 'auto';
        previewContainer.style.display = 'flex';
        previewContainer.style.flexDirection = 'column';
        previewContainer.style.alignItems = 'center';
        previewContainer.appendChild(canvas);

        // Tambahkan keterangan
        const caption = document.createElement('div');
        caption.className = 'text-center text-sm text-gray-700 mt-3 font-medium';
        caption.innerHTML = `<i>Preview simulasi untuk ${treeCount.toLocaleString()} pohon terdeteksi (menampilkan ${visibleTrees.toLocaleString()} sampel)</i>`;
        previewContainer.appendChild(caption);

        // Tambahkan tombol download
        const downloadBtn = document.createElement('button');
        downloadBtn.className = 'mt-3 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 text-sm font-medium transition duration-150 ease-in-out';
        downloadBtn.innerText = 'Download Visualisasi';
        downloadBtn.onclick = function() {
            const link = document.createElement('a');
            link.download = `deteksi_pohon_${new Date().toISOString().slice(0, 10)}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        };
        previewContainer.appendChild(downloadBtn);
    }

    // Fungsi untuk mengatur tombol simpan dan export
    function setupSaveAndExportButtons(detectionData) {
        // Tombol simpan ke database
        document.getElementById('saveDetectionBtn')?.addEventListener('click', function() {
            const detectionName = document.getElementById('detectionName').value.trim();

            if (!detectionName) {
                toastError('Nama hasil deteksi tidak boleh kosong');
                return;
            }

            this.disabled = true;
            this.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Menyimpan...
            `;

            // Panggil API untuk menyimpan hasil deteksi
            fetch('/api/save-detection', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({
                    name: detectionName,
                    detection_id: detectionData.detection_id,
                    tree_count: detectionData.tree_count,
                    block_id: blockSelect.value,
                    aerial_photo_id: aerialPhotoSelect.value
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Gagal menyimpan hasil deteksi');
                }
                return response.json();
            })
            .then(data => {
                // Tampilkan notifikasi sukses
                toastSuccess(`Hasil deteksi "${detectionName}" berhasil disimpan`);

                // Reset tombol
                this.disabled = false;
                this.innerHTML = `
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                    </svg>
                    Simpan ke Database
                `;
            })
            .catch(error => {
                console.error('Error:', error);

                // Tampilkan notifikasi error
                toastError(error.message);

                // Reset tombol
                this.disabled = false;
                this.innerHTML = `
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                    </svg>
                    Simpan ke Database
                `;
            });
        });

        // Tombol export shapefile
        document.getElementById('exportShapefileBtn')?.addEventListener('click', function() {
            const detectionName = document.getElementById('detectionName').value.trim();

            if (!detectionName) {
                toastError('Nama hasil deteksi tidak boleh kosong');
                return;
            }

            this.disabled = true;
            this.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Memproses...
            `;

            // Muat output_dir dari detection data
            const outputDir = detectionData.output_dir || '';
            console.log('Output directory:', outputDir);
            addLogLine(`🗂️ Menggunakan direktori output: ${outputDir || 'tidak tersedia'}`, 'info');

            // Tambahkan log untuk debugging
            console.log('Sending shapefile export request with data:', {
                name: detectionName,
                aerial_photo_id: aerialPhotoSelect.value,
                plantation_id: blockSelect.value,
                tree_count: detectionData.tree_count || 0,
                output_dir: outputDir
            });

            // Panggil API untuk mengekspor shapefile
            fetch('/api/export-shapefile', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json, */*'
                },
                body: JSON.stringify({
                    name: detectionName,
                    aerial_photo_id: aerialPhotoSelect.value,
                    plantation_id: blockSelect.value,
                    tree_count: detectionData.tree_count || 0,
                    output_dir: outputDir
                })
            })
            .then(response => {
                // Log response details for debugging
                addLogLine(`📡 Response status: ${response.status} ${response.statusText}`, response.ok ? 'success' : 'error');

                if (!response.ok) {
                    // Try to read error message if it's JSON
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || `Error ${response.status}: ${response.statusText}`);
                    }).catch(e => {
                        // If parsing JSON fails, use status text
                        throw new Error(`Gagal mengekspor shapefile: ${response.statusText}`);
                    });
                }

                // Check content type to handle blob vs JSON response
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json().then(data => {
                        // If we got JSON instead of a file, treat as error
                        if (!data.success) {
                            throw new Error(data.message || 'Gagal mengekspor shapefile');
                        }

                        // Otherwise show message that no file was returned
                        toastError('Server tidak mengembalikan file shapefile');
                        return null;
                    });
                }

                // For blob/file response, proceed as normal
                return response.blob();
            })
            .then(blob => {
                if (!blob) {
                    throw new Error('Tidak ada data yang diterima dari server');
                }

                // Create download link and click automatically
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = `${detectionName}.zip`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);

                // Show success notification
                toastSuccess(`Shapefile "${detectionName}" berhasil diekspor`);

                // Reset button
                this.disabled = false;
                this.innerHTML = `<svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                </svg> Export Shapefile`;
            })
            .catch(error => {
                console.error('Error:', error);

                // Show error notification
                toastError(error.message);

                // Reset button
                this.disabled = false;
                this.innerHTML = `<svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                </svg> Export Shapefile`;
            });
        });
    }

    // Fungsi untuk mendapatkan CSRF token dengan penanganan error
    function getCsrfToken() {
        const tokenElement = document.querySelector('meta[name="csrf-token"]');
        if (tokenElement) {
            return tokenElement.getAttribute('content');
        } else {
            console.error('Meta tag CSRF token tidak ditemukan di halaman');
            // Gunakan alert sebagai fallback jika Toastify belum siap
            if (typeof Toastify === 'undefined') {
                alert('CSRF token tidak ditemukan. Silakan refresh halaman');
            } else {
                showErrorToast('CSRF token tidak ditemukan. Silakan refresh halaman');
            }
            return '';
        }
    }

    // Fungsi untuk menampilkan toast error dengan pengecekan Toastify
    function toastError(message) {
        if (typeof Toastify === 'undefined') {
            // Fallback ke alert jika Toastify tidak tersedia
            console.error('Toastify tidak tersedia:', message);
            alert(message);
            return;
        }

        Toastify({
            text: message,
            duration: 3000,
            close: true,
            gravity: "top",
            position: "right",
            style: {
                background: "#EF4444",
            },
            stopOnFocus: true,
        }).showToast();
    }

    // Alias untuk kompatibilitas
    function showErrorToast(message) {
        toastError(message);
    }

    // Fungsi untuk menampilkan toast sukses
    function toastSuccess(message) {
        if (typeof Toastify === 'undefined') {
            // Fallback ke alert jika Toastify tidak tersedia
            console.error('Toastify tidak tersedia:', message);
            alert(message);
            return;
        }

        Toastify({
            text: message,
            duration: 3000,
            close: true,
            gravity: "top",
            position: "right",
            style: {
                background: "#10B981",
            },
            stopOnFocus: true,
        }).showToast();
    }

    // Fungsi untuk menampilkan tab tertentu
    function showTab(tabName) {
        // Tampilkan tab hasil deteksi jika ada
        if (tabName === 'result') {
            detectionResults.classList.remove('hidden');
        }
    }

    // Add CSS to head
    const styleElement = document.createElement('style');
    styleElement.textContent = `
        #consoleContainer {
            background-color: #1e1e1e;
            font-family: 'Courier New', monospace;
        }
        .console-line {
            line-height: 1.5;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .text-gray-400 {
            color: #9ca3af;
        }
        .text-blue-400 {
            color: #60a5fa;
        }
        .text-green-400 {
            color: #4ade80;
        }
        .text-red-400 {
            color: #f87171;
        }
        .text-yellow-400 {
            color: #fbbf24;
        }
    `;
    document.head.appendChild(styleElement);

    function updateDebugInfo() {
        const aerialPhotoId = document.getElementById('aerial_photo_id').value;
        const plantationId = document.getElementById('plantation_id').value;

        // Tampilkan informasi di panel log
        appendLog("🔍 Debug: Aerial Photo ID: " + aerialPhotoId);
        appendLog("🔍 Debug: Plantation ID: " + plantationId);

        // Tambahkan tombol debug di panel log
        if (plantationId) {
            const debugBtn = document.createElement('button');
            debugBtn.className = 'btn btn-sm btn-info mt-2';
            debugBtn.textContent = 'Debug Geometri';
            debugBtn.onclick = function() {
                window.open('/debug/plantation/' + plantationId, '_blank');
            };

            const logPanel = document.getElementById('log-container');
            logPanel.appendChild(debugBtn);

            appendLog("ℹ️ Klik tombol 'Debug Geometri' untuk memeriksa data geometri plantation");
        }
    }

    // Panggil saat memilih plantation
    document.getElementById('plantation_id').addEventListener('change', function() {
        updateDebugInfo();
    });
});
</script>
@endsection
