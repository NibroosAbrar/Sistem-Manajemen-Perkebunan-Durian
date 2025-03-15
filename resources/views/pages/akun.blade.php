{{-- filepath: /c:/laragon/www/laravel11/resources/views/pages/webgis.blade.php --}}
@extends('layouts.app')

@section('title', 'Pengguna - Symadu')

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
        <h1 class="text-xl font-semibold">Manajemen Pengguna</h1>
        <div x-data="{ isOpen: false }" class="absolute right-6 flex justify-end">
            <button @click="isOpen = !isOpen" class="relative w-12 h-12 rounded-full overflow-hidden border-4 border-gray-400 hover:border-gray-300 focus:border-gray-300 focus:outline-none">
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



    <!-- Konten -->
    <div class="container mx-auto p-6" x-data="{ showDeleteModal: false, userIdToDelete: null }">
        <h1 class="text-2xl font-bold mb-4">Daftar Pengguna</h1>
        <div class="bg-white p-6 shadow-md rounded-lg">
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-4 text-left border border-gray-300">Nama</th>
                        <th class="p-4 text-left border border-gray-300">Email</th>
                        <th class="p-4 text-center border border-gray-300">Role</th>
                        <th class="p-4 text-center border border-gray-300">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr class="border-t hover:bg-gray-100">
                            <td class="p-4 border border-gray-300">{{ $user->name }}</td>
                            <td class="p-4 border border-gray-300">{{ $user->email }}</td>
                            <td class="p-4 text-center border border-gray-300">
                                <form action="{{ route('akun.update', $user->id) }}" method="POST" x-data="{ showEditConfirmModal: false }">
                                    @csrf
                                    @method('PUT')
                                    <select name="role_id"
                                    class="border p-2 rounded
                                    {{ $user->role_id == 1 ? 'bg-green-500 text-white font-bold' : 'bg-white text-black' }}">

                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>
                                                {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" @click="showEditConfirmModal = true" class="ml-2 bg-blue-500 hover:bg-blue-600 text-white font-bold py-1 px-3 rounded">
                                        Simpan
                                    </button>

                                    <!-- Modal Konfirmasi Edit -->
                                    <div x-show="showEditConfirmModal" class="fixed inset-0 flex items-center justify-center z-[60]">
                                        <div class="modal-overlay absolute inset-0 bg-black opacity-50"></div>

                                        <div class="bg-white p-6 rounded-lg shadow-lg relative z-[70]">
                                            <h2 class="text-xl font-semibold mb-4">Konfirmasi Perubahan</h2>
                                            <p class="mb-4">Apakah Anda yakin ingin menyimpan perubahan ini?</p>
                                            <div class="flex justify-end">
                                                <button type="button" @click="showEditConfirmModal = false"
                                                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded mr-2">
                                                    Batal
                                                </button>
                                                <button type="submit"
                                                    class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                                                    Simpan
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </td>
                            <td class="p-4 text-center border border-gray-300">
                                <button @click="showDeleteModal = true; userIdToDelete = {{ $user->id }}"
                                        class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Modal Konfirmasi Hapus -->
        <div x-show="showDeleteModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h2 class="text-xl font-semibold mb-4">Konfirmasi Hapus</h2>
                <p class="mb-4">Apakah Anda yakin ingin menghapus pengguna ini?</p>
                <div class="flex justify-end">
                    <button @click="showDeleteModal = false"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded mr-2">
                        Batal
                    </button>
                    <form :action="'/akun/' + userIdToDelete" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
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
