{{-- filepath: /c:/laragon/www/laravel11/resources/views/pages/webgis.blade.php --}}
@extends('layouts.app')

@section('title', 'Beranda - Symadu')

@section('content')

<!-- Add CSRF Token Meta -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
[x-cloak] {
    display: none !important;
}
</style>

<div class="w-full flex flex-col h-screen overflow-y-hidden"
     x-data="{
         isDropdownOpen: false,
         showPhotoModal: false,
         openPhotoModal() {
             this.showPhotoModal = true;
         }
     }">

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
        <!-- Header -->
        <header class="bg-header text-black flex items-center justify-center py-4 px-6 relative">
            <!-- Toggle Dropdown -->
            <button @click="isDropdownOpen = !isDropdownOpen" class="absolute left-6 text-3xl focus:outline-none">
                <i x-show="!isDropdownOpen" class="fas fa-bars"></i>
                <i x-show="isDropdownOpen" class="fas fa-times"></i>
            </button>
            <h1 class="text-xl font-semibold">Beranda</h1>
            <div x-data="{ isOpen: false }" class="absolute right-6 flex justify-end">
                <button @click="isOpen = !isOpen" class="relative z-10 w-12 h-12 rounded-full overflow-hidden border-4 border-gray-400 hover:border-gray-300 focus:border-gray-300 focus:outline-none">
                    <img src="static/profile.png">
                </button>
                <button x-show="isOpen" @click="isOpen = false" class="h-full w-full fixed inset-0 cursor-default"></button>
                <div x-show="isOpen" class="account-dropdown py-2 mt-16">
                    <a href="{{ route('akun.profil') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Akun</a>
                    <a href="#" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Support</a>
                    <div x-data="{ showModal: false }">
                        <a href="#" @click.prevent="showModal = true" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Keluar</a>

                        <!-- Modal -->
                        <div x-show="showModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
                            <div class="bg-white p-6 rounded-lg shadow-lg">
                                <h2 class="text-xl font-semibold mb-4">Konfirmasi Keluar</h2>
                                <p class="mb-4">Apakah Anda yakin ingin keluar?</p>
                                <div class="flex justify-end">
                                    <button @click="showModal = false" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded mr-2">Batal</button>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                                        @csrf
                                    </form>

                                    <button @click="document.getElementById('logout-form').submit()" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded">Keluar</button>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dropdown Menu -->
        <div x-show="isDropdownOpen" class="sidebar-dropdown py-2 mt-16 absolute left-6">
            <h1 class="sidebar-header text-2xl font-bold text-center mb-4" style="color: #4aa87a;">Symadu</h1>
            <a href="{{ route('webgis') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Beranda</a>
            {{-- <a href="dashboard.html" @click="isDropdownOpen = false" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Dashboard Kebun</a> --}}
            <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Dashboard Kebun</a>
            <a href="{{ route('pengelolaan') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Kegiatan Pengelolaan Kebun</a>

            @if(Auth::user()->role_id == 1) {{-- Superadmin --}}
                <a href="{{ route('stok') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Manajemen Stok Kebun</a>
                <a href="{{ route('akun') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Manajemen Pengguna</a>
            @elseif(Auth::user()->role_id == 2) {{-- Manajer --}}
                <a href="{{ route('stok') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Manajemen Stok Kebun</a>
            @elseif(Auth::user()->role_id == 3) {{-- Operasional --}}
                {{-- Tidak menampilkan Manajemen Stok dan Manajemen Pengguna --}}
            @elseif(Auth::user()->role_id == 4) {{-- Guest --}}
                {{-- Sama seperti operasional, hanya bisa melihat (tambah edit dibatasi di controller) --}}
            @endif
        </div>

        <!-- Konten utama -->
        <div class="w-full h-screen overflow-x-hidden border-t flex flex-col">
            <main>
                <!-- Leaflet Map Container -->
                <div id="map" class="col-md-12"></div>

                <!-- Info Sidebar -->
                <div id="info" class="sidebar">
                    <div class="sidebar-header">
                        <h1 class="text-center font-bold uppercase">Tentang Kami</h1>
                        <span class="sidebar-close"><i class="fas fa-times"></i></span>
                    </div>
                    <div class="sidebar-content">
                        <h2 class="font-bold">Sejarah Penanaman</h2>
                        <p class="text-justify">Bibit pertama kali ditanam pada tahun 2012...</p>
                        <br>
                        <h2 class="font-bold">Lokasi</h2>
                        <p class="text-justify">Kebun kami terletak di Kabupaten Sukabumi, Jawa Barat.</p>
                        <br>
                        <h2 class="font-bold">Informasi Kebun</h2>
                        <p class="text-justify">Kebun ini mencakup area seluas 60 hektar...</p>
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
                            <button @click="openPhotoModal()"
                                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                                Masukkan Foto Udara
                            </button>
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

                            <a href="{{ route('aerial-photo.edit') }}"
                               class="update-button block text-center mt-6">
                                Update Foto Udara
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<!-- Modal Form untuk Input Data Pohon -->
<div x-data="{ showShapeTypeModal: false, selectedLayer: null, selectedWkt: null, selectedShapeType: null }"
     x-show="showShapeTypeModal"
     @open-shape-type-modal.window="
        showShapeTypeModal = true;
        selectedLayer = $event.detail.layer;
        selectedWkt = $event.detail.geometryWkt;
        selectedShapeType = $event.detail.shapeType;
     "
     @close-shape-type-modal.window="showShapeTypeModal = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-center mb-6 text-green-700">Pilih Jenis Data</h1>

                <p class="text-center mb-6">Anda telah membuat bentuk pada peta. Pilih jenis data yang ingin ditambahkan:</p>

                <div class="flex justify-center space-x-4">
                    <button
                        @click="
                            showShapeTypeModal = false;
                            // Hapus layer dari peta karena akan ditambahkan ke plantationLayers oleh showPlantationModal
                            if (selectedLayer && selectedLayer._map) {
                                selectedLayer._map.removeLayer(selectedLayer);
                            }
                            $dispatch('open-plantation-modal', { geometryWkt: selectedWkt, shapeType: selectedShapeType });
                        "
                        class="bg-blue-600 text-white py-3 px-6 rounded-md hover:bg-blue-700 transition flex-1 max-w-xs">
                        <i class="fas fa-map-marked-alt mr-2"></i> Blok Kebun
                    </button>

                    <button
                        @click="
                            showShapeTypeModal = false;
                            // Hapus layer dari peta karena akan ditambahkan ke drawnItems oleh showTreeModal
                            if (selectedLayer && selectedLayer._map) {
                                selectedLayer._map.removeLayer(selectedLayer);
                            }
                            showTreeModal(false, null, selectedWkt, selectedShapeType);
                        "
                        class="bg-green-600 text-white py-3 px-6 rounded-md hover:bg-green-700 transition flex-1 max-w-xs">
                        <i class="fas fa-tree mr-2"></i> Pohon
                    </button>
                </div>

                <div class="mt-5 text-center">
                    <button
                        @click="
                            showShapeTypeModal = false;
                            // Hapus layer dari peta jika batal
                            if (selectedLayer && selectedLayer._map) {
                                selectedLayer._map.removeLayer(selectedLayer);
                            }
                        "
                        class="text-gray-600 hover:text-gray-800">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form untuk Input Data Blok Kebun -->
<div x-data="{ showPlantationModal: false, isEditMode: false }"
     x-show="showPlantationModal"
     @open-plantation-modal.window="
        showPlantationModal = true;
        isEditMode = $event.detail.isEdit || false;
        $nextTick(() => {
            document.getElementById('plantation_geometry').value = $event.detail.geometryWkt || '';
        });
     "
     @close-plantation-modal.window="showPlantationModal = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
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
                <input type="hidden" id="plantation_geometry" name="geometry">
                <input type="hidden" id="plantation_shape_type" name="shape_type" value="Polygon">

                <h1 class="text-2xl font-bold text-center mb-6 text-blue-700" x-text="isEditMode ? 'Edit Blok Kebun' : 'Data Blok Kebun'"></h1>

                <!-- ID Blok field - only shown in edit mode -->
                <div x-show="isEditMode" class="mb-4">
                    <label for="display_plantation_id" class="block text-sm font-medium text-gray-700 mb-1">ID Blok</label>
                    <input type="text" id="display_plantation_id" class="mt-1 block w-full bg-gray-100 border-gray-300 shadow-sm focus:border-[#4aa87a] focus:ring-[#4aa87a] px-3 py-2 rounded-md" readonly>
                </div>

                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Blok <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" required class="mt-1 block w-full border-gray-300 shadow-sm focus:border-[#4aa87a] focus:ring-[#4aa87a] px-3 py-2 rounded-md">
                </div>

                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 shadow-sm focus:border-[#4aa87a] focus:ring-[#4aa87a] px-3 py-2 rounded-md"></textarea>
                </div>

                <div class="mt-5 sm:mt-6 flex justify-end space-x-2">
                    <button type="button" @click="showPlantationModal = false" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded mr-2">
                        Batal
                    </button>
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div x-data="{ showTreeModal: false, isEditMode: false }"
     x-show="showTreeModal"
     @open-tree-modal.window="showTreeModal = true; isEditMode = document.getElementById('form_mode').value === 'update'"
     @close-tree-modal.window="showTreeModal = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
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
                <input type="hidden" id="tree_id" name="id">
                <input type="hidden" id="form_mode" value="create">
                <input type="hidden" id="canopy_geometry" name="canopy_geometry">
                <h1 class="text-2xl font-bold text-center mb-6 text-green-700">Data Pohon</h1>

                <!-- ID Pohon field - only shown in edit mode -->
                <div x-show="isEditMode" class="mb-4">
                    <label for="display_id" class="block text-sm font-medium text-gray-700 mb-1">ID Pohon</label>
                    <input type="text" id="display_id" class="mt-1 block w-full bg-gray-100 border-gray-300 shadow-sm focus:border-[#4aa87a] focus:ring-[#4aa87a] px-3 py-2" readonly>
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
                        <option value="Terinfeksi">Terinfeksi</option>
                        <option value="Mati">Mati</option>
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
    <script src="https://unpkg.com/@geoman-io/leaflet-geoman-free@latest/dist/leaflet-geoman.js"></script>

    <script src="{{ url('static/leaflet.ajax.js') }}"></script>
    <script src="{{ url('assets/js/scripts.js') }}"></script>
    <script src="{{ url('assets/js/tree-focus.js') }}"></script>

    <!-- Event listener untuk event open-tree-modal -->
    <script>
        window.addEventListener('open-tree-modal', function(event) {
            console.log('open-tree-modal event received:', event);

            // Reset form terlebih dahulu jika bukan mode edit
            const form = document.getElementById('treeForm');
            const isEditMode = event.detail?.isEdit || false;
            const treeData = event.detail?.treeData || null;
            const geometryWkt = event.detail?.geometryWkt || null;
            const shapeType = event.detail?.shapeType || 'Polygon';

            console.log('Modal data:', { isEditMode, treeData, geometryWkt, shapeType });

            if (form) {
                if (!isEditMode) {
                    form.reset();
                }
            }

            // Set nilai geometri pada input hidden
            const geometryInput = document.getElementById('canopy_geometry');
            if (geometryInput) {
                if (isEditMode && treeData && (treeData.canopy_geometry_wkt || treeData.canopy_geometry) && !geometryWkt) {
                    // Jika mode edit, gunakan WKT dari treeData
                    geometryInput.value = treeData.canopy_geometry_wkt || treeData.canopy_geometry;
                    console.log('canopy_geometry set to (from treeData):', geometryInput.value);
                } else if (geometryWkt) {
                    // Gunakan geometri dari bentuk yang baru dibuat
                    geometryInput.value = geometryWkt;
                    console.log('canopy_geometry set to (from geometryWkt):', geometryWkt);
                }
            }

            // Set tipe bentuk pada input hidden
            const shapeTypeInput = document.getElementById('shape_type');
            if (shapeTypeInput) {
                shapeTypeInput.value = shapeType;
                console.log('shape_type set to:', shapeType);
            } else if (form) {
                // Buat input hidden untuk shape_type jika belum ada
                const input = document.createElement('input');
                input.type = 'hidden';
                input.id = 'shape_type';
                input.name = 'shape_type';
                input.value = shapeType;
                form.appendChild(input);
                console.log('shape_type input created with value:', shapeType);
            }

            // Aktifkan tab pertama
            setTimeout(() => {
                switchTab(1);
                console.log('Activated first tab');
            }, 100);
        });
    </script>
@endsection

@push('styles')
@endpush
