{{-- filepath: /c:/laragon/www/laravel11/resources/views/pages/webgis.blade.php --}}
@extends('layouts.app')

@section('title', 'Stok - Symadu')

@section('content')

<div id="loading-screen">i
</div>

<div class="w-full flex flex-col h-screen overflow-y-auto">
    <!-- Header -->
    <header class="bg-header text-black flex items-center justify-center py-4 px-6 relative">
        <!-- Toggle Dropdown -->
        <button @click="isDropdownOpen = !isDropdownOpen" class="absolute left-6 text-3xl focus:outline-none">
            <i x-show="!isDropdownOpen" class="fas fa-bars"></i>
            <i x-show="isDropdownOpen" class="fas fa-times"></i>
        </button>
        <h1 class="text-xl font-semibold">Manajemen Stok</h1>
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
    <div class="container mx-auto p-6" x-data="{
        showAddForm: false,
        editStockId: null,
        showEditForm: false,
        showDeleteModal: false,
        deleteStockId: null,
        showEditConfirmModal: false,

        // Fungsi untuk mengatur ID yang akan dihapus dan menampilkan modal
        setDeleteStockId(id) {
            this.deleteStockId = id;
            this.showDeleteModal = true;
        },

        // Fungsi untuk mengonfirmasi penghapusan
        confirmDeleteStock() {
            if (this.deleteStockId) {
                document.getElementById(`delete-form-${this.deleteStockId}`).submit();
            }
        }
    }">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">Daftar Stok</h1>
            <button @click="showAddForm = !showAddForm" class="bg-green-500 text-white p-2 rounded">
                Tambah Stok
            </button>
        </div>

        <!-- Form Tambah Stok (Tersembunyi Secara Default) -->
        <div x-show="showAddForm" x-transition x-cloak class="mb-6 p-4 bg-white shadow-md rounded w-full lg:w-3/4 mx-auto">
            <h2 class="text-xl font-bold mb-4">Tambah Stok</h2>
            <form action="{{ route('stok.store') }}" method="POST">
                @csrf
                <input type="text" name="name" placeholder="Nama Barang" required class="border p-2 rounded w-full mb-2">
                <select name="category" class="border p-2 rounded w-full mb-2">
                    <option value="bibit_pohon">Bibit & Pohon</option>
                    <option value="pupuk">Pupuk</option>
                    <option value="pestisida_fungisida">Pestisida & Fungisida</option>
                    <option value="alat_perlengkapan">Alat & Perlengkapan</option>
                </select>
                <input type="number" name="quantity" placeholder="Jumlah" required class="border p-2 rounded w-full mb-2">
                <input type="text" name="unit" placeholder="Satuan" class="border p-2 rounded w-full mb-2">
                <input type="date" name="date_added" required class="border p-2 rounded w-full mb-2">
                <div class="flex justify-between">
                    <button type="button" @click="showAddForm = false" class="bg-gray-500 text-white p-2 rounded">
                        Batal
                    </button>
                    <button type="submit" class="bg-blue-500 text-white p-2 rounded">
                        Simpan Stok
                    </button>
                </div>
            </form>
        </div>

        <!-- Daftar Stok -->
        <div class="w-full overflow-x-auto border-t flex flex-col">
            <main class="w-full flex-grow p-6">
                @foreach ($stocks as $category => $items)
                    <h2 class="text-xl font-bold mt-6">{{ ucfirst(str_replace('_', ' ', $category)) }}</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-full border-collapse border border-gray-300 bg-white shadow-md table-fixed">

                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="p-3 text-left border border-gray-300">Nama</th>
                                    <th class="p-3 text-left border border-gray-300">Jumlah</th>
                                    <th class="p-3 text-left border border-gray-300">Satuan</th>
                                    <th class="p-3 text-left border border-gray-300">Tanggal Masuk</th>
                                    <th class="p-3 text-center border border-gray-300">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $stock)
                                    <tr class="border-t hover:bg-gray-100" id="stok-row-{{ $stock->id }}">
                                        <td class="p-3 border border-gray-300 break-words whitespace-normal">{{ $stock->name }}</td>
                                        <td class="p-3 border border-gray-300">{{ $stock->quantity }}</td>
                                        <td class="p-3 border border-gray-300">{{ $stock->unit }}</td>
                                        <td class="p-3 border border-gray-300">{{ $stock->date_added }}</td>
                                        <td class="p-3 text-center border border-gray-300">
                                            <!-- Tombol Edit -->
                                            <button @click="editStockId = {{ $stock->id }}; showEditForm = !showEditForm" class="bg-yellow-500 text-white p-2 rounded">
                                                Edit
                                            </button>

                                            <!-- Tombol Hapus -->
                                            <button @click="deleteStockId = {{ $stock->id }}; showDeleteModal = true" class="bg-red-500 text-white p-2 rounded">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Form Edit Stok -->
                                    <tr x-show="editStockId == {{ $stock->id }} && showEditForm" x-transition x-cloak class="mb-6 p-4 bg-white shadow-md rounded w-full lg:w-3/4 mx-auto">
                                        <td colspan="5" class="p-3 bg-gray-100">
                                            <form action="{{ route('stok.update', $stock->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="flex flex-col space-y-2">
                                                    <input type="text" name="name" value="{{ $stock->name }}" required class="border p-2 rounded w-full">
                                                    <input type="number" name="quantity" value="{{ $stock->quantity }}" required class="border p-2 rounded w-full">
                                                    <input type="text" name="unit" value="{{ $stock->unit }}" class="border p-2 rounded w-full">
                                                    <input type="date" name="date_added" value="{{ $stock->date_added }}" required class="border p-2 rounded w-full">
                                                    <div class="flex justify-between">
                                                        <button type="button" @click="editStockId = null; showEditForm = false" class="bg-gray-500 text-white p-2 rounded">
                                                            Batal
                                                        </button>
                                                        <button type="button" @click="showEditConfirmModal = true" class="bg-blue-500 text-white p-2 rounded">
                                                            Simpan Perubahan
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Modal Konfirmasi Edit -->
                                                <div x-show="showEditConfirmModal" class="fixed inset-0 flex items-center justify-center z-50">
                                                    <div class="modal-overlay absolute inset-0 bg-black opacity-50"></div>

                                                    <div class="bg-white w-96 rounded-lg shadow-lg z-50 overflow-hidden">
                                                        <div class="p-6">
                                                            <h2 class="text-xl font-bold mb-4">Konfirmasi Perubahan</h2>
                                                            <p class="mb-6">Apakah Anda yakin ingin menyimpan perubahan ini?</p>

                                                            <div class="flex justify-end space-x-2">
                                                                <button type="button" @click="showEditConfirmModal = false; editStockId = null; showEditForm = false"
                                                                    class="px-6 py-2 bg-gray-200 hover:bg-gray-300 rounded-md font-medium">
                                                                    Batal
                                                                </button>
                                                                <button type="submit"
                                                                    class="px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md font-medium">
                                                                    Simpan
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                            <form id="delete-form-{{ $stock->id }}" action="{{ route('stok.destroy', $stock->id) }}" method="POST" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </main>
        </div>

        <!-- Modal Konfirmasi Hapus -->
        <div x-show="showDeleteModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h2 class="text-xl font-semibold mb-4">Konfirmasi Hapus</h2>
                <p class="mb-4">Apakah Anda yakin ingin menghapus stok ini?</p>
                <div class="flex justify-end">
                    <button @click="showDeleteModal = false" class="bg-gray-500 text-white p-2 rounded mr-2">
                        Batal
                    </button>
                    <button @click="confirmDeleteStock()" class="bg-red-500 text-white p-2 rounded">
                        Hapus
                    </button>
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

