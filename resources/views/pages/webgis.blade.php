{{-- filepath: /c:/laragon/www/laravel11/resources/views/pages/webgis.blade.php --}}
@extends('layouts.app')

@section('title', 'Beranda - DuriGeo')

@section('content')

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
    <!-- Dropdown Menu -->
    <div x-show="isDropdownOpen" class="sidebar-dropdown py-2 mt-16 absolute left-6">
        <h1 class="sidebar-header text-2xl font-bold text-center mb-4" style="color: #4aa87a;">DuriGeo</h1>
        <a href="{{ route('webgis') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Beranda</a>
        <a href="dashboard.html" @click="isDropdownOpen = false" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Dashboard Kebun</a>
        <a href="pengelolaan.html" @click="isDropdownOpen = false" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Kegiatan Pengelolaan Kebun</a>
        <a href="produksi.html" @click="isDropdownOpen = false" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Kegiatan Panen dan Produksi</a>
        {{-- <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Dashboard Kebun</a>
        <a href="{{ route('pengelolaan') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Kegiatan Pengelolaan Kebun</a>
        <a href="{{ route('produksi') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Kegiatan Panen dan Produksi</a> --}}

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

            <!-- Sidebar -->
            <div id="sidebar" class="col-md-3">
                <div id="info">
                    <h1 class="text-center font-bold uppercase">Tentang Kami</h1>
                    <br>
                    <h2 class="font-bold">Sejarah Penanaman</h2>
                    <p class="text-justify">Bibit pertama kali ditanam pada tahun 2012...</p>
                    <br>
                    <h2 class="font-bold">Lokasi</h2>
                    <p class="text-justify">Kebun kami terletak di Kabupaten Sukabumi, Jawa Barat.</p>
                    <br>
                    <h2 class="font-bold">Informasi Kebun</h2>
                    <p class="text-justify">Kebun ini mencakup area seluas 60 hektar...</p>
                </div>

                <!-- Informasi Kamera -->
                <div id="camera">
                    <div id="camera-header" class="text-center font-bold uppercase">Informasi Citra</div>
                    <br>
                    <div class="card">
                        <h4>Resolusi Pengambilan Citra</h4>
                        <p>1 cm</p>
                    </div>
                    <div class="card">
                        <h4>Waktu dan Tanggal</h4>
                        <p>09.30 WIB, 20 April 2024</p>
                    </div>
                    <div class="card">
                        <h4>Tipe Drone</h4>
                        <p>DJI Mavic 3 Multispectral</p>
                    </div>
                    <div class="card">
                        <h4>Ketinggian</h4>
                        <p>75 meter</p>
                    </div>
                    <div class="card">
                        <h4>Overlap</h4>
                        <p>85%</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

@endsection

@section('scripts')
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="{{ url('static/L.Control.Sidebar.js') }}"></script>
    <script src="{{ url('static/L.Control.MousePosition.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet-easybutton@2/src/easy-button.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/js/all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.14.1/jquery-ui.min.js"></script>
    <script src="https://unpkg.com/{{ '@' }}geoman-io/leaflet-geoman-free@latest/dist/leaflet-geoman.js"></script>


    <script src="{{ url('static/leaflet.ajax.js') }}"></script>
    <script src="{{ url('assets/js/scripts.js') }}"></script>
@endsection
