{{-- filepath: /c:/laragon/www/laravel11/resources/views/pages/webgis.blade.php --}}
@extends('layouts.app')

@section('title', 'Peta - Symadu')
@section('header-title', 'Peta')

@section('content')

<!-- Add CSRF Token Meta -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Class webgis-page pada body tag -->
<script>
    document.body.classList.add('webgis-page');
    window.userRole = "{{ Auth::user()->role }}";
    console.log("User role:", window.userRole);
</script>

<style>
[x-cloak] {
    display: none !important;
}

/* Pastikan modal disembunyikan dengan benar sebelum Alpine.js siap */
.modal-hidden {
    display: none !important;
}

/* Info Panel Styling */
.sidebar-content {
    padding: 0.5rem 1rem 1.5rem 0.75rem;
    height: calc(100% - 60px);
    overflow-y: auto;
}

.info-section {
    margin-bottom: 1.25rem;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.info-section:last-child {
    margin-bottom: 0;
}

.info-section-title {
    background: #f8fafc;
    padding: 0.75rem;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    border-bottom: 1px solid #e2e8f0;
}

.info-section-content {
    padding: 0.75rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 0.375rem;
    transition: all 0.2s ease;
}

.info-item:hover {
    background: #f1f5f9;
    transform: translateY(-1px);
}

.info-label {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0.25rem;
}

.info-value {
    font-size: 1rem;
    font-weight: 500;
    color: #1e293b;
}

/* Scrollbar Styling */
.sidebar-content::-webkit-scrollbar {
    width: 6px;
}

.sidebar-content::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.sidebar-content::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.sidebar-content::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Styles untuk tampilan mobile */
@media (max-width: 768px) {
    /* Tambahkan padding atas yang lebih besar di mobile untuk menghindari tertutupi header */
    #map-container {
        padding-top: 60px !important; /* Sesuaikan dengan tinggi header di mobile */
    }

    /* Pastikan peta dimulai di bawah header */
    #map {
        top: 60px !important; /* Sesuaikan dengan tinggi header di mobile */
        height: calc(100vh - 60px) !important;
    }

    /* Atur tombol-tombol di peta agar tidak tertutupi */
    .leaflet-top {
        top: 15px !important; /* Kurangi gap antara header dan tombol Leaflet */
    }

    /* Perbaiki posisi kontrol di bagian bawah (scale bar dan mouse position) */
    .leaflet-bottom {
        bottom: 60px !important; /* Naikkan posisi kontrol bawah agar lebih terlihat (dari 30px ke 60px) */
        z-index: 1000 !important; /* Pastikan selalu tampil di atas elemen lain */
    }

    /* Pastikan mouse position control terlihat */
    .leaflet-control-mouseposition {
        margin-bottom: 10px !important; /* Tingkatkan margin-bottom dari 25px ke 35px */
        background-color: rgba(255, 255, 255, 0.8) !important; /* Tambahkan background agar mudah terbaca *//* Tambahkan padding agar lebih mudah dilihat */
        border-radius: 4px !important; /* Tambahkan sudut melengkung */
    }

    /* Pastikan konten peta dimulai setelah header */
    .h-screen {
        height: calc(100vh - 60px) !important;
        margin-top: 60px !important;
    }

    /* Atur posisi kontrol2 Leaflet pada mobile */
    .leaflet-control-container .leaflet-left {
        margin-top: 0 !important; /* Hapus margin-top tambahan */
    }

    /* Pastikan foto udara container tidak tertutupi header */
    .aerial-photos-container {
        padding-top: 15px;
    }
}
</style>

<div class="w-full flex flex-col h-screen overflow-y-hidden"
     x-data="{
         isDropdownOpen: false,
         showPhotoModal: false,
         openPhotoModal() {
             this.showPhotoModal = true;
         }
     }" id="map-container">

    <!-- Modal Foto Udara -->
    <div x-show="showPhotoModal"
         x-cloak
         class="fixed inset-0 flex items-center justify-center z-[9999]">
        <div class="modal-overlay absolute inset-0 bg-black opacity-50"></div>

        <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded shadow-lg relative z-[10000] overflow-y-auto">
            <div class="modal-content py-4 text-left px-6">
                <!-- Modal Header -->
                <div class="flex justify-between items-center pb-3">
                    <p class="text-2xl font-bold">Tambah Foto Udara</p>
                    <button @click="showPhotoModal = false" class="modal-close cursor-pointer z-70">
                        <span class="text-3xl">&times;</span>
                    </button>
                </div>

                <!-- Modal Body -->
                <form action="{{ route('aerial-photo.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="space-y-4">
                        <div class="form-group">
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Foto Udara
                            </label>
                            <input type="file" name="aerial_photo" accept="image/*"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div class="form-group">
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Resolusi (cm) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="resolution" step="0.1" value="1" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div class="form-group">
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Waktu Pengambilan <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" name="capture_time" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div class="form-group">
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Tipe Drone <span class="text-red-500">*</span>
                            </label>
                            <select name="drone_type" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="DJI Mavic 3 Multispectral">DJI Mavic 3 Multispectral</option>
                                <option value="DJI Phantom 4 Multispectral">DJI Phantom 4 Multispectral</option>
                                <option value="DJI Matrice 300 RTK">DJI Matrice 300 RTK</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Ketinggian (meter) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="height" value="75" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div class="form-group">
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Overlap (%) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="overlap" min="0" max="100" value="85" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="button"
                                    class="px-4 bg-transparent p-3 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-400 mr-2"
                                    @click="showPhotoModal = false">
                                Batal
                            </button>
                            <button type="submit"
                                    class="px-4 bg-blue-500 p-3 rounded-lg text-white hover:bg-blue-400">
                                Simpan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="loading-screen">
    </div>

    <div class="w-full flex flex-col h-screen overflow-y-hidden">
        <!-- Dropdown Menu -->
        <div x-show="isDropdownOpen" class="sidebar-dropdown py-2 mt-16 absolute left-6">
            <h1 class="sidebar-header text-2xl font-bold text-center mb-4" style="color: #4aa87a;">Symadu</h1>
            <a href="{{ route('webgis') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Peta</a>
            <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Dashboard Kebun</a>
            <a href="{{ route('pengelolaan') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Kegiatan Pengelolaan Kebun</a>

            @if(Auth::user()->role_id == 1)
                <a href="{{ route('stok') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Manajemen Stok Kebun</a>
                <a href="{{ route('akun') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Manajemen Pengguna</a>
            @elseif(Auth::user()->role_id == 2)
                <a href="{{ route('stok') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Manajemen Stok Kebun</a>
            @endif
        </div>

        <!-- Konten utama -->
        <div class="w-full h-screen overflow-x-hidden border-t flex flex-col">
            <main>
                <!-- Leaflet Map Container -->
                <div id="map" class="col-md-12"></div>

                <!-- Info Sidebar -->
                <div id="info" class="sidebar">
                    <div class="info-header">
                        <h2>Tentang Kami</h2>
                        <button onclick="toggleSidebar('info')" class="close-button">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="sidebar-content">
                        <div class="info-section">
                            <h3 class="info-section-title">Sejarah Penanaman</h3>
                            <div class="info-section-content">
                                <p class="text-justify text-gray-700 leading-relaxed">Bibit pertama kali ditanam pada tahun 2012 dengan total area 60 hektar. Kebun ini dikelola secara profesional dengan standar internasional untuk menghasilkan kualitas terbaik.</p>
                            </div>
                        </div>

                        <div class="info-section">
                            <h3 class="info-section-title">Lokasi</h3>
                            <div class="info-section-content">
                                <p class="text-justify text-gray-700 leading-relaxed">Kebun kami terletak di Kabupaten Sukabumi, Jawa Barat. Area ini dipilih karena memiliki kondisi geografis dan iklim yang ideal untuk pertumbuhan tanaman.</p>
                                <div class="mt-2">
                                    <span class="text-sm text-gray-500">Koordinat:</span>
                                    <p class="text-sm font-medium">Latitude: -6.9175° S</p>
                                    <p class="text-sm font-medium">Longitude: 106.9277° E</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Camera Info Sidebar -->
                <div id="camera" class="sidebar">
                    <div class="info-header">
                        <h2>Informasi Citra</h2>
                        <button onclick="closeImageInfo()" class="close-button">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="info-content">
                        <div id="noPhotoInfo" class="text-center py-4">
                            <p class="text-gray-600 mb-4">Belum ada foto udara yang tersedia</p>
                            <div class="flex flex-col space-y-3">
                                @if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2)
                                <a href="{{ route('aerial-photo.index') }}"
                                class="update-button block text-center">
                                    Masukkan Foto Udara
                                </a>
                                <a href="{{ route('shapefile.index') }}" class="update-button block text-center bg-green-600 hover:bg-green-700">
                                    Shapefile Manager
                                </a>
                                @endif
                            </div>
                        </div>

                        <div id="photoInfo" class="hidden space-y-4">
                            <div class="info-item">
                                <div class="info-label">Resolusi Pengambilan Citra</div>
                                <div class="info-value" id="imageResolution">-</div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">Waktu dan Tanggal</div>
                                <div class="info-value" id="imageDateTime">-</div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">Tipe Drone</div>
                                <div class="info-value" id="droneType">-</div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">Ketinggian</div>
                                <div class="info-value" id="flightHeight">-</div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">Overlap</div>
                                <div class="info-value" id="imageOverlap">-</div>
                            </div>

                            <div class="flex flex-col space-y-3 mt-6">
                                @if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2)
                                <a href="{{ route('aerial-photo.index') }}" id="editPhotoLink" class="update-button block text-center">
                                    Update Foto Udara
                                </a>
                                <a href="{{ route('shapefile.index') }}" class="update-button block text-center bg-green-600 hover:bg-green-700">
                                    Shapefile Manager
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<!-- Modal Form untuk Input Data Pohon -->
<div x-data="{ showTreeModal: false, isEditMode: false }"
     x-cloak
     @open-tree-modal.window="showTreeModal = true; isEditMode = $event.detail?.isEdit || false"
     @close-tree-modal.window="showTreeModal = false"
     class="fixed inset-0 z-50 overflow-y-auto tree-modal"
     :class="{'modal-hidden': !showTreeModal}"
     id="treeModalContainer">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="treeForm"
                  action="/api/trees"
                  method="POST"
                  class="p-6"
                  @submit.prevent="submitTreeForm()">
                @csrf
                <input type="hidden" name="_method" id="form_method" value="POST">
                <input type="hidden" id="tree_id" name="tree_id_hidden">
                <input type="hidden" id="form_mode" value="create">
                <input type="hidden" id="canopy_geometry" name="canopy_geometry">
                <h1 class="text-2xl font-bold text-center mb-6 text-green-700">Data Pohon</h1>

                <!-- ID Pohon field - only shown in edit mode -->
                <div x-show="isEditMode" class="mb-4">
                    <label for="display_id" class="block text-sm font-medium text-gray-700 mb-1">ID Pohon</label>
                    <input type="text" id="display_id" name="id" class="mt-1 block w-full border-gray-300 shadow-sm focus:border-[#4aa87a] focus:ring-[#4aa87a] px-3 py-2">
                </div>

                <!-- ID Pohon input untuk mode tambah baru -->
                <div x-show="!isEditMode" class="mb-4">
                    <label for="custom_id" class="block text-sm font-medium text-gray-700 mb-1">ID Pohon (opsional)</label>
                    <input type="text" id="custom_id" name="id" class="mt-1 block w-full border-gray-300 shadow-sm focus:border-[#4aa87a] focus:ring-[#4aa87a] px-3 py-2" placeholder="Misal: 1A, 25B, dll">
                    <p class="text-xs text-gray-500 mt-1">Biarkan kosong untuk ID otomatis</p>
                </div>

                <div class="mb-4">
                    <label for="plantation_id" class="block text-sm font-medium text-gray-700 mb-1">Kebun <span class="text-red-500">*</span></label>
                    <select id="plantation_id" name="plantation_id" required class="mt-1 block w-full border-gray-300 shadow-sm focus:border-[#4aa87a] focus:ring-[#4aa87a] px-3 py-2">
                        @foreach($plantations as $plantation)
                            <option value="{{ $plantation->id }}">{{ $plantation->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="varietas" class="block text-sm font-medium text-gray-700 mb-1">Varietas <span class="text-red-500">*</span></label>
                    <input type="string" id="varietas" name="varietas" required class="mt-1 block w-full border-gray-300 shadow-sm focus:border-[#4aa87a] focus:ring-[#4aa87a] px-3 py-2">
                </div>

                <div class="mb-4">
                    <label for="tahun_tanam" class="block text-sm font-medium text-gray-700 mb-1">Tahun Tanam <span class="text-red-500">*</span></label>
                    <input type="number" id="tahun_tanam" name="tahun_tanam" required class="mt-1 block w-full border-gray-300 shadow-sm focus:border-[#4aa87a] focus:ring-[#4aa87a] px-3 py-2">
                </div>

                <div class="mb-4">
                    <label for="health_status" class="block text-sm font-medium text-gray-700 mb-1">Status Kesehatan</label>
                    <select id="health_status" name="health_status" required class="mt-1 block w-full border-gray-300 shadow-sm focus:border-[#4aa87a] focus:ring-[#4aa87a] px-3 py-2">
                        <option value="Sehat">Sehat</option>
                        <option value="Stres">Stres</option>
                        <option value="Sakit">Sakit</option>
                        <option value="Mati">Mati</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="fase" class="block text-sm font-medium text-gray-700 mb-1">Fase Pertumbuhan</label>
                    <select id="fase" name="fase" required class="mt-1 block w-full border-gray-300 shadow-sm focus:border-[#4aa87a] focus:ring-[#4aa87a] px-3 py-2">
                        <option value="Vegetatif">Vegetatif</option>
                        <option value="Generatif">Generatif</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="sumber_bibit" class="block text-sm font-medium text-gray-700 mb-1">Sumber Bibit</label>
                    <input type="text" id="sumber_bibit" name="sumber_bibit" class="mt-1 block w-full border-gray-300 shadow-sm focus:border-[#4aa87a] focus:ring-[#4aa87a] px-3 py-2">
                </div>

                <div class="mt-5 sm:mt-6 flex justify-end space-x-2">
                    <button type="button" @click="showTreeModal = false" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded mr-2">
                        Batal
                    </button>
                    <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Form untuk Input Data Blok Kebun -->
<div x-data="{ showPlantationModal: false, isEditMode: false }"
     x-cloak
     @open-plantation-modal.window="showPlantationModal = true; isEditMode = $event.detail?.isEdit || false"
     @close-plantation-modal.window="showPlantationModal = false"
     class="fixed inset-0 z-50 overflow-y-auto plantation-modal"
     :class="{'modal-hidden': !showPlantationModal}"
     id="plantationModalContainer">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="plantationForm"
                  action="/api/plantations"
                  method="POST"
                  class="p-6"
                  @submit.prevent="submitPlantationForm()">
                @csrf
                <input type="hidden" name="_method" id="plantation_form_method" value="POST">
                <input type="hidden" id="plantation_id" name="id">
                <input type="hidden" id="boundary_geometry" name="geometry">
                <h1 class="text-2xl font-bold text-center mb-6 text-green-700">Data Blok Kebun</h1>

                <!-- ID Blok field - only shown in edit mode -->
                <div x-show="isEditMode" class="mb-4">
                    <label for="display_plantation_id" class="block text-sm font-medium text-gray-700 mb-1">ID Blok</label>
                    <input type="text" id="display_plantation_id" class="mt-1 block w-full bg-gray-100 border-gray-300 shadow-sm focus:border-[#4aa87a] focus:ring-[#4aa87a] px-3 py-2" readonly>
                </div>

                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Blok <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" required class="mt-1 block w-full border-gray-300 shadow-sm focus:border-[#4aa87a] focus:ring-[#4aa87a] px-3 py-2">
                </div>

                <div class="mb-4">
                    <label for="luas_area" class="block text-sm font-medium text-gray-700 mb-1">Luas Area (ha) <span class="text-red-500">*</span></label>
                    <input type="number" id="luas_area" name="luas_area" step="0.0001" required class="mt-1 block w-full border-gray-300 shadow-sm focus:border-[#4aa87a] focus:ring-[#4aa87a] px-3 py-2">
                    <p class="text-xs text-gray-500 mt-1">Luas area dihitung otomatis, namun dapat diubah manual jika perhitungan tidak akurat</p>
                </div>

                <div class="mb-4">
                    <label for="tipe_tanah" class="block text-sm font-medium text-gray-700 mb-1">Tipe Tanah</label>
                    <select id="tipe_tanah" name="tipe_tanah" class="mt-1 block w-full border-gray-300 shadow-sm focus:border-[#4aa87a] focus:ring-[#4aa87a] px-3 py-2">
                        <option value="" selected>-- Pilih Tipe Tanah --</option>
                        <option value="Andosol">Andosol</option>
                        <option value="Latosol">Latosol</option>
                        <option value="Podsolik">Podsolik</option>
                        <option value="Regosol">Regosol</option>
                        <option value="Alluvial">Alluvial</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="mt-5 sm:mt-6 flex justify-end space-x-2">
                    <button type="button" @click="showPlantationModal = false" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded mr-2">
                        Batal
                    </button>
                    <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Pemilihan Form -->
<div x-data="{ showFormSelectorModal: false }"
     x-cloak
     @open-form-selector-modal.window="showFormSelectorModal = true"
     @close-form-selector-modal.window="showFormSelectorModal = false"
     class="fixed inset-0 z-50 overflow-y-auto form-selector-modal"
     :class="{'modal-hidden': !showFormSelectorModal}"
     id="formSelectorModalContainer">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-center mb-6 text-green-700">Pilih Jenis Data</h1>
                <p class="text-center text-gray-600 mb-6">Silakan pilih jenis data yang ingin Anda tambahkan</p>

                <div class="grid grid-cols-2 gap-4">
                    <button id="select-plantation" onclick="window.selectPlantationForm()" class="flex flex-col items-center justify-center p-4 border-2 border-green-500 rounded-lg hover:bg-green-50 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-green-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        <span class="font-medium text-green-700">Blok Kebun</span>
                    </button>

                    <button id="select-tree" onclick="window.selectTreeForm()" class="flex flex-col items-center justify-center p-4 border-2 border-green-500 rounded-lg hover:bg-green-50 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-green-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                        <span class="font-medium text-green-700">Pohon</span>
                    </button>
                </div>

                <div class="mt-6 flex justify-end">
                    <button id="cancel-form-selection" onclick="window.cancelFormSelection()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
    <!-- Proj4js for coordinate transformation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.9.0/proj4.js"></script>
    <script src="https://unpkg.com/proj4leaflet@1.0.2/src/proj4leaflet.js"></script>

    <!-- Turf.js for geometric calculations -->
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="{{ url('static/L.Control.Sidebar.js') }}"></script>
    <script src="{{ url('static/L.Control.MousePosition.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet-easybutton@2/src/easy-button.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/js/all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.14.1/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@geoman-io/leaflet-geoman-free@latest/dist/leaflet-geoman.min.js"></script>

    <script src="{{ url('static/leaflet.ajax.js') }}"></script>
    <script src="{{ url('assets/js/scripts.js') }}"></script>
    <script src="{{ url('assets/js/tree-focus.js') }}"></script>

    <!-- Wicket.js for WKT parsing -->
    <script src="https://cdn.jsdelivr.net/npm/wicket@1.3.8/wicket.min.js"></script>

    <!-- Shapefile Layer Script -->
    <script src="{{ asset('assets/js/shapefile-layer.js') }}"></script>

    <script>
        // Script untuk memastikan Geoman dimuat dengan benar
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, checking Geoman status...');

            // Pasang observer untuk modal
            setupModalObserver();

            // Cek apakah perangkat mobile dan atur posisi kontrol Leaflet
            checkMobileAndFixControls();

            // Debug dan pastikan tombol-tombol pemilihan data berfungsi
            console.log('Setting up form selection buttons...');

            // Tombol pilih pohon
            const selectTreeBtn = document.getElementById('select-tree');
            if (selectTreeBtn) {
                console.log('Tree button found, attaching event listener');
                selectTreeBtn.addEventListener('click', function(e) {
                    console.log('Tree button clicked directly via event listener');

                    if (typeof selectTreeForm === 'function') {
                        selectTreeForm();
                    } else {
                        console.error('selectTreeForm function not found!');
                        alert('Error: Fungsi selectTreeForm tidak ditemukan!');
                    }
                });
            } else {
                console.error('Tree button not found in DOM!');
            }

            // Tombol pilih blok kebun
            const selectPlantationBtn = document.getElementById('select-plantation');
            if (selectPlantationBtn) {
                console.log('Plantation button found, attaching event listener');
                selectPlantationBtn.addEventListener('click', function(e) {
                    console.log('Plantation button clicked directly via event listener');

                    if (typeof selectPlantationForm === 'function') {
                        selectPlantationForm();
                    } else {
                        console.error('selectPlantationForm function not found!');
                        alert('Error: Fungsi selectPlantationForm tidak ditemukan!');
                    }
                });
            } else {
                console.error('Plantation button not found in DOM!');
            }

            // Tombol batal pemilihan
            const cancelFormBtn = document.getElementById('cancel-form-selection');
            if (cancelFormBtn) {
                console.log('Cancel button found, attaching event listener');
                cancelFormBtn.addEventListener('click', function(e) {
                    console.log('Cancel button clicked directly via event listener');

                    if (typeof cancelFormSelection === 'function') {
                        cancelFormSelection();
                    } else {
                        console.error('cancelFormSelection function not found!');
                        alert('Error: Fungsi cancelFormSelection tidak ditemukan!');
                    }
                });
            } else {
                console.error('Cancel button not found in DOM!');
            }

            // Log untuk Alpine.js
            console.log('Alpine.js modal states:');
            console.log('- showFormSelectorModal defined:', typeof document.querySelector('[x-data*="showFormSelectorModal"]') !== 'undefined');
            console.log('- showTreeModal defined:', typeof document.querySelector('[x-data*="showTreeModal"]') !== 'undefined');
            console.log('- showPlantationModal defined:', typeof document.querySelector('[x-data*="showPlantationModal"]') !== 'undefined');

            // Periksa status Leaflet dan Geoman setelah halaman dimuat
            // Kurangi waktu tunggu untuk mempercepat inisialisasi
            setTimeout(function() {
                if (typeof L !== 'undefined' && typeof L.PM !== 'undefined') {
                    console.log('Leaflet dan Geoman sudah dimuat, menginisialisasi kontrol...');
                    initGeomanDirectly();
                } else {
                    console.warn('Geoman belum dimuat, mencoba memuat secara manual...');
                    if (typeof loadGeomanManually === 'function') {
                        loadGeomanManually();
                    }
                }

                // Muat data pohon dan blok kebun yang ada
                loadLayersWithRetry();
            }, 300); // Kurangi waktu tunggu dari 1000ms menjadi 300ms

            // Tambahkan event listener untuk tombol Batal pada form
            document.querySelectorAll('button[type="button"]').forEach(button => {
                if (button.textContent.trim() === 'Batal') {
                    button.addEventListener('click', function() {
                        console.log('Cancel button clicked, checking for open modals');

                        // Check if we're in a modal
                        const modal = this.closest('.fixed');
                        if (modal) {
                            const modalId = modal.id;
                            console.log('Found modal:', modalId);

                            if (modalId === 'formSelectorModalContainer') {
                                // Jika modal pemilihan form, jalankan cancelFormSelection
                                console.log('Calling cancelFormSelection');
                                if (typeof cancelFormSelection === 'function') {
                                    cancelFormSelection();
                                }
                            } else if (modalId === 'plantationModalContainer' || modalId === 'treeModalContainer') {
                                // Batalkan bentuk jika masih dalam proses pembuatan
                                if (typeof cancelShape === 'function' && typeof currentShapeData !== 'undefined' && currentShapeData && currentShapeData.layer && !currentShapeData.layer.isSaved) {
                                    console.log('Calling cancelShape for unsaved shape');
                                    cancelShape();
                                }
                            }
                        }
                    });
                }
            });

            // Tambahkan event listener untuk resize window
            window.addEventListener('resize', function() {
                checkMobileAndFixControls();
            });
        });

        // Fungsi untuk mengecek apakah perangkat mobile dan menyesuaikan posisi kontrol
        function checkMobileAndFixControls() {
            const isMobile = window.innerWidth <= 768;

            if (isMobile) {
                // Jalankan setelah peta dimuat untuk memastikan kontrol sudah ada
                if (typeof map !== 'undefined') {
                    // Tunggu sebentar untuk memastikan kontrol Leaflet sudah dirender
                    setTimeout(() => {
                        // Atur posisi kontrol atas
                        const leafletTopElements = document.querySelectorAll('.leaflet-top');
                        leafletTopElements.forEach(el => {
                            // Periksa apakah ini kontrol di kiri atas atau kanan atas
                            if (el.classList.contains('leaflet-left')) {
                                // Penyesuaian untuk kontrol di kiri atas (Geoman, zoom)
                                el.style.top = '15px';
                            } else if (el.classList.contains('leaflet-right')) {
                                // Penyesuaian untuk kontrol di kanan atas (layer control)
                                el.style.top = '15px';
                            }
                        });

                        // Atur posisi kontrol bawah (scale bar dan mouse position)
                        const leafletBottomElements = document.querySelectorAll('.leaflet-bottom');
                        leafletBottomElements.forEach(el => {
                            el.style.bottom = '60px';

                            // Juga atur margin untuk mouse position control jika ada
                            const mousePositionControl = el.querySelector('.leaflet-control-mouseposition');
                            if (mousePositionControl) {
                                mousePositionControl.style.marginBottom = '35px';
                            }
                        });

                        console.log('Mobile controls position adjusted (top and bottom)');
                    }, 500);
                }
            }
        }

        // Fungsi untuk memuat pohon dan blok kebun dengan retry
        function loadLayersWithRetry(retryCount = 0, maxRetries = 5) {
            console.log(`Attempting to load map layers (attempt ${retryCount + 1}/${maxRetries})`);

            // Periksa apakah map dan modul yang diperlukan sudah siap
            if (typeof map !== 'undefined' && typeof map.addLayer === 'function') {
                try {
                    if (typeof loadExistingTrees === 'function') {
                        console.log('Loading existing trees...');
                        loadExistingTrees();
                    } else {
                        console.warn('loadExistingTrees function not available yet');
                    }

                    if (typeof loadExistingPlantations === 'function') {
                        console.log('Loading existing plantations...');
                        loadExistingPlantations();
                    } else {
                        console.warn('loadExistingPlantations function not available yet');
                    }

                    // Dispatch event untuk memberitahu bahwa layer berhasil dimuat
                    console.log('Map layers loaded successfully');
                    document.dispatchEvent(new CustomEvent('map-layers-loaded'));
                    return true;
                } catch (error) {
                    console.error('Error loading map layers:', error);
                }
            } else {
                console.warn('Map or required modules not ready yet');
            }

            // Retry logic
            if (retryCount < maxRetries) {
                console.log(`Retrying in ${500 * (retryCount + 1)}ms...`);
                setTimeout(() => {
                    loadLayersWithRetry(retryCount + 1, maxRetries);
                }, 500 * (retryCount + 1)); // Progressive backoff
            } else {
                console.error('Failed to load map layers after maximum retry attempts');
            }

            return false;
        }

        // Fungsi untuk memastikan hanya satu modal yang terbuka pada satu waktu
        function ensureSingleModalOpen() {
            // Dapatkan semua modal
            const modals = [
                { id: 'formSelectorModalContainer', xData: 'showFormSelectorModal' },
                { id: 'treeModalContainer', xData: 'showTreeModal' },
                { id: 'plantationModalContainer', xData: 'showPlantationModal' }
            ];

            // Periksa jika ada lebih dari satu modal yang terbuka
            let openModals = modals.filter(modal => {
                const el = document.getElementById(modal.id);
                if (!el) return false;

                // Periksa status modal dengan Alpine.js
                if (window.Alpine) {
                    const alpineData = window.Alpine.getElementBoundAlpineData(el);
                    if (alpineData && alpineData[modal.xData] === true) {
                        return true;
                    }
                }

                // Fallback: periksa secara visual
                return !el.classList.contains('modal-hidden') &&
                       getComputedStyle(el).display !== 'none';
            });

            if (openModals.length > 1) {
                console.warn('Multiple modals detected open:', openModals.map(m => m.id).join(', '));

                // Hanya biarkan modal terakhir tetap terbuka
                for (let i = 0; i < openModals.length - 1; i++) {
                    console.log('Forcing close of modal:', openModals[i].id);
                    forceCloseModal(openModals[i].id);
                }
            }
        }

        // Fungsi global untuk menutup modal secara paksa
        function forceCloseModal(modalId) {
            console.log('Force closing modal:', modalId);

            const modalElement = document.getElementById(modalId);
            if (!modalElement) {
                console.error('Modal element not found:', modalId);
                return false;
            }

            try {
                // Cobalah menggunakan Alpine.js terlebih dahulu jika tersedia
                if (window.Alpine) {
                    const alpineData = window.Alpine.getElementBoundAlpineData(modalElement);
                    if (alpineData) {
                        // Temukan variabel Alpine yang mengontrol visibilitas modal
                        const modalVarName = Object.keys(alpineData).find(
                            key => key.toLowerCase().includes('modal') && typeof alpineData[key] === 'boolean'
                        );

                        if (modalVarName) {
                            alpineData[modalVarName] = false;
                            console.log('Closed modal via Alpine.js:', modalId, '(variable:', modalVarName, ')');
                            return true;
                        }
                    }
                }

                // Fallback: tambahkan class modal-hidden dan hide secara langsung
                modalElement.classList.add('modal-hidden');
                modalElement.style.display = 'none';

                // Dispatch event close sesuai dengan jenis modal
                if (modalId === 'formSelectorModalContainer') {
                    window.dispatchEvent(new CustomEvent('close-form-selector-modal'));
                } else if (modalId === 'treeModalContainer') {
                    window.dispatchEvent(new CustomEvent('close-tree-modal'));
                } else if (modalId === 'plantationModalContainer') {
                    window.dispatchEvent(new CustomEvent('close-plantation-modal'));
                }

                console.log('Closed modal via DOM manipulation:', modalId);
                return true;
            } catch (error) {
                console.error('Error closing modal:', modalId, error);
                return false;
            }
        }

        // Tambahkan MutationObserver untuk memantau perubahan DOM dan mendeteksi modal yang terbuka
        function setupModalObserver() {
            // Pilih node yang akan dipantau
            const modalContainers = [
                document.getElementById('formSelectorModalContainer'),
                document.getElementById('treeModalContainer'),
                document.getElementById('plantationModalContainer')
            ].filter(el => el !== null);

            if (modalContainers.length === 0) {
                console.warn('No modal containers found to observe');
                return;
            }

            // Konfigurasi pengamat
            const config = { attributes: true, attributeFilter: ['class', 'style'] };

            // Buat instance MutationObserver
            const observer = new MutationObserver((mutationsList) => {
                for (const mutation of mutationsList) {
                    if (mutation.type === 'attributes') {
                        // Periksa apakah ada perubahan kelas atau style yang menunjukkan modal dibuka
                        const target = mutation.target;
                        const isHidden = target.classList.contains('modal-hidden') ||
                                         getComputedStyle(target).display === 'none';

                        if (!isHidden) {
                            console.log('Modal opened detected:', target.id);
                            // Panggil ensureSingleModalOpen dengan delay kecil
                            setTimeout(ensureSingleModalOpen, 50);
                        }
                    }
                }
            });

            // Mulai mengamati setiap container modal
            modalContainers.forEach(container => {
                observer.observe(container, config);
                console.log('Observing modal container:', container.id);
            });
        }

        // Init shapefile layers setelah map dimuat
        document.addEventListener('map-layers-loaded', function() {
            if (typeof initShapefileLayers === 'function' && typeof map !== 'undefined') {
                // Menyediakan akses map ke window untuk shapefile-layer.js
                window.map = map;

                // Inisialisasi shapefile layers
                initShapefileLayers(map);
                console.log('Shapefile layer initialization triggered');

                // Tambahkan kontrol layer untuk shapefile
                if (typeof shapefileLayers !== 'undefined') {
                    const overlays = {
                        "Blok Kebun (Shapefile)": shapefileLayers.plantation,
                        "Pohon (Shapefile)": shapefileLayers.tree
                    };

                    // Tambahkan layer control baru
                    L.control.layers(null, overlays, {
                        position: 'topright',
                        collapsed: false
                    }).addTo(map);
                }

                // Periksa apakah ada parameter shapefile di URL
                const urlParams = new URLSearchParams(window.location.search);
                const shapefileId = urlParams.get('shapefile');

                if (shapefileId && typeof loadShapefileById === 'function') {
                    // Load shapefile spesifik dan zoom ke shapefile tersebut
                    console.log('Loading specific shapefile ID:', shapefileId);
                    loadShapefileById(shapefileId);
                }
            } else {
                console.warn('initShapefileLayers function or map not available');
            }
        });
    </script>
@endsection

@push('styles')
@endpush
