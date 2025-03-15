@extends('layouts.app')

@section('title', 'Pengelolaan - Symadu')
@section('header-title', 'Catatan Kegiatan Pengelolaan')

@section('content')
<div id="loading-screen">
</div>

<div class="w-full flex flex-col h-screen overflow-y-auto" x-data="{ showAddModal: false, showDeleteModal: false, deleteId: null, editKegiatanId: null, showEditForm: false, showEditConfirmModal: false }">
    <!-- Header -->
    <header class="bg-header text-black flex items-center justify-center py-4 px-6 relative">
        <!-- Toggle Dropdown -->
        <button @click="isDropdownOpen = !isDropdownOpen" class="absolute left-6 text-3xl focus:outline-none">
            <i x-show="!isDropdownOpen" class="fas fa-bars"></i>
            <i x-show="isDropdownOpen" class="fas fa-times"></i>
        </button>
        <h1 class="text-xl font-semibold">Kegiatan Pengelolaan Kebun</h1>
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
        <div x-show="isDropdownOpen" class="sidebar-dropdown py-2 mt-16 absolute left-6">
            <h1 class="sidebar-header text-2xl font-bold text-center mb-4" style="color: #4aa87a;">Symadu</h1>
            <a href="{{ route('webgis') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Beranda</a>
            <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Dashboard Kebun</a>
            {{-- <a href="dashboard.html" @click="isDropdownOpen = false" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Dashboard Kebun</a> --}}
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

    <!-- Tombol Tambah Kegiatan -->
    <div class="flex justify-end mb-6 px-6 mt-6">
        <button class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded" @click="showAddModal = true">
            Tambah Kegiatan
        </button>
    </div>

    <!-- Navigasi Tab -->
    <div class="mb-6 px-6" x-data="{ activeTab: 'kegiatan' }">
        <div class="flex border-b border-gray-200">
            <button @click="activeTab = 'kegiatan'"
                :class="{ 'border-b-2 border-green-600 font-bold text-green-600': activeTab === 'kegiatan' }"
                class="py-2 px-4 text-sm font-semibold text-gray-600 hover:text-gray-800 focus:outline-none">
                Kegiatan
            </button>
            <button @click="activeTab = 'trees'"
                :class="{ 'border-b-2 border-green-600 font-bold text-green-600': activeTab === 'trees' }"
                class="py-2 px-4 text-sm font-semibold text-gray-600 hover:text-gray-800 focus:outline-none">
                Data Pohon
            </button>
            <button @click="activeTab = 'health'"
                :class="{ 'border-b-2 border-green-600 font-bold text-green-600': activeTab === 'health' }"
                class="py-2 px-4 text-sm font-semibold text-gray-600 hover:text-gray-800 focus:outline-none">
                Data Kesehatan
            </button>
            <button @click="activeTab = 'fertilization'"
                :class="{ 'border-b-2 border-green-600 font-bold text-green-600': activeTab === 'fertilization' }"
                class="py-2 px-4 text-sm font-semibold text-gray-600 hover:text-gray-800 focus:outline-none">
                Data Pemupukan
            </button>
            <button @click="activeTab = 'pesticides'"
                :class="{ 'border-b-2 border-green-600 font-bold text-green-600': activeTab === 'pesticides' }"
                class="py-2 px-4 text-sm font-semibold text-gray-600 hover:text-gray-800 focus:outline-none">
                Data Pestisida
            </button>
            <button @click="activeTab = 'production'"
                :class="{ 'border-b-2 border-green-600 font-bold text-green-600': activeTab === 'production' }"
                class="py-2 px-4 text-sm font-semibold text-gray-600 hover:text-gray-800 focus:outline-none">
                Data Produksi
            </button>
        </div>

        <!-- Tab Kegiatan -->
        <div x-show="activeTab === 'kegiatan'" class="mt-6">
            <!-- Tabel Daftar Kegiatan yang sudah ada -->
            <div class="bg-white p-6 shadow-md rounded-lg">
                <table class="w-full border-collapse table-fixed">
            <thead>
                        <tr class="bg-gray-100">
                            <th class="p-4 text-center w-32 font-semibold">Tanggal</th>
                            <th class="p-4 text-center w-48 font-semibold">Jenis Kegiatan</th>
                            <th class="p-4 text-center font-semibold">Deskripsi</th>
                            <th class="p-4 text-center w-32 font-semibold">Petugas</th>
                            <th class="p-4 text-center w-40 font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($kegiatan as $item)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-4 text-center">{{ $item->tanggal }}</td>
                            <td class="p-4 text-center">{{ $item->jenis_kegiatan }}</td>
                            <td class="p-4 whitespace-normal break-words text-left">{{ $item->deskripsi }}</td>
                            <td class="p-4 text-center">{{ $item->petugas }}</td>
                            <td class="p-4">
                                <div class="flex space-x-2 justify-center">
                                    <button @click="editKegiatanId = {{ $item->id }}; showEditForm = !showEditForm" class="bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded">Edit</button>
                                    <button @click="showDeleteModal = true; deleteId = {{ $item->id }}" class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded">Hapus</button>
                                </div>
                            </td>
                        </tr>
                        <!-- Form Edit Kegiatan -->
                        <tr x-show="editKegiatanId == {{ $item->id }} && showEditForm" x-transition x-cloak class="bg-gray-50">
                            <td colspan="5" class="p-4">
                                <form action="{{ route('pengelolaan.update', $item->id) }}" method="POST" class="space-y-4">
                            @csrf
                                    @method('PUT')
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                                            <input type="date" name="tanggal" value="{{ $item->tanggal }}" required
                                                class="w-full border rounded-md p-2">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kegiatan</label>
                                            <select name="jenis_kegiatan" required class="w-full border rounded-md p-2">
                                                <option value="Penanaman" {{ $item->jenis_kegiatan == 'Penanaman' ? 'selected' : '' }}>Penanaman</option>
                                                <option value="Pemupukan" {{ $item->jenis_kegiatan == 'Pemupukan' ? 'selected' : '' }}>Pemupukan</option>
                                                <option value="Pengendalian Hama dan Penyakit" {{ $item->jenis_kegiatan == 'Pengendalian Hama dan Penyakit' ? 'selected' : '' }}>Pengendalian Hama dan Penyakit</option>
                                                <option value="Panen" {{ $item->jenis_kegiatan == 'Panen' ? 'selected' : '' }}>Panen</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                                        <textarea name="deskripsi" required rows="3"
                                            class="w-full border rounded-md p-2">{{ $item->deskripsi }}</textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Petugas</label>
                                        <input type="text" name="petugas" value="{{ $item->petugas }}" required
                                            class="w-full border rounded-md p-2">
                                    </div>
                                    <div class="flex justify-end space-x-2">
                                        <button type="button" @click="editKegiatanId = null; showEditForm = false"
                                            class="bg-gray-500 text-white px-4 py-2 rounded-md">Batal</button>
                                        <button type="button" @click="showEditConfirmModal = true"
                                            class="bg-blue-500 text-white px-4 py-2 rounded-md">Simpan Perubahan</button>
                                    </div>

                                    <!-- Modal Konfirmasi Edit -->
                                    <div x-show="showEditConfirmModal" class="fixed inset-0 flex items-center justify-center z-50">
                                        <div class="modal-overlay absolute inset-0 bg-black opacity-50"></div>

                                        <div class="bg-white w-96 rounded-lg shadow-lg z-50 overflow-hidden">
                                            <div class="p-6">
                                                <h2 class="text-xl font-bold mb-4">Konfirmasi Perubahan</h2>
                                                <p class="mb-6">Apakah Anda yakin ingin menyimpan perubahan ini?</p>

                                                <div class="flex justify-end space-x-2">
                                                    <button type="button" @click="showEditConfirmModal = false; editKegiatanId = null; showEditForm = false"
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
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

        <!-- Tab Pohon -->
        <div x-show="activeTab === 'trees'" class="mt-6">
            <div class="bg-white p-6 shadow-md rounded-lg">
                <h2 class="text-xl font-bold mb-4">Data Pohon</h2>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-4 text-center border border-gray-300 font-semibold">ID Pohon</th>
                                <th class="p-4 text-center border border-gray-300 font-semibold">Varietas</th>
                                <th class="p-4 text-center border border-gray-300 font-semibold">Tahun Tanam</th>
                                <th class="p-4 text-center border border-gray-300 font-semibold">Status Kesehatan</th>
                                <th class="p-4 text-center border border-gray-300 font-semibold">Latitude</th>
                                <th class="p-4 text-center border border-gray-300 font-semibold">Longitude</th>
                                <th class="p-4 text-center border border-gray-300 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trees as $tree)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="p-4 border border-gray-300 text-center">{{ $tree->id }}</td>
                                <td class="p-4 border border-gray-300 text-center">{{ $tree->varietas }}</td>
                                <td class="p-4 border border-gray-300 text-center">{{ $tree->tahun_tanam }}</td>
                                <td class="p-4 border border-gray-300 text-center">{{ $tree->health_status }}</td>
                                <td class="p-4 border border-gray-300 text-center">{{ number_format($tree->latitude, 6) }}</td>
                                <td class="p-4 border border-gray-300 text-center">{{ number_format($tree->longitude, 6) }}</td>
                                <td class="p-4 border border-gray-300 text-center">
                                    <a href="{{ route('webgis') }}?id={{ $tree->id }}"
                                        class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded inline-block">
                                        Lihat Lokasi
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab Kesehatan -->
        <div x-show="activeTab === 'health'" class="mt-6">
            <div class="bg-white p-6 shadow-md rounded-lg">
                <h2 class="text-xl font-bold mb-4">Data Kesehatan Pohon</h2>
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-4 text-left border border-gray-300">ID Pohon</th>
                            <th class="p-4 text-left border border-gray-300">Tanggal Pemeriksaan</th>
                            <th class="p-4 text-left border border-gray-300">Status Kesehatan</th>
                            <th class="p-4 text-left border border-gray-300">Gejala</th>
                            <th class="p-4 text-left border border-gray-300">Tindakan</th>
                            <th class="p-4 text-left border border-gray-300">Petugas</th>
                            <th class="p-4 text-left border border-gray-300">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($health_profiles as $profile)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-4 border border-gray-300">{{ $profile->tree_id }}</td>
                            <td class="p-4 border border-gray-300">{{ $profile->check_date }}</td>
                            <td class="p-4 border border-gray-300">{{ $profile->health_status }}</td>
                            <td class="p-4 border border-gray-300">{{ $profile->symptoms }}</td>
                            <td class="p-4 border border-gray-300">{{ $profile->action_taken }}</td>
                            <td class="p-4 border border-gray-300">{{ $profile->officer }}</td>
                            <td class="p-4 border border-gray-300">{{ $profile->notes }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab Pemupukan -->
        <div x-show="activeTab === 'fertilization'" class="mt-6">
            <div class="bg-white p-6 shadow-md rounded-lg">
                <h2 class="text-xl font-bold mb-4">Data Pemupukan</h2>
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-4 text-left border border-gray-300">ID</th>
                            <th class="p-4 text-left border border-gray-300">Tanggal</th>
                            <th class="p-4 text-left border border-gray-300">Jenis Pupuk</th>
                            <th class="p-4 text-left border border-gray-300">Jumlah</th>
                            <th class="p-4 text-left border border-gray-300">Area</th>
                            <th class="p-4 text-left border border-gray-300">Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fertilizations as $fertilization)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-4 border border-gray-300">{{ $fertilization->id }}</td>
                            <td class="p-4 border border-gray-300">{{ $fertilization->date }}</td>
                            <td class="p-4 border border-gray-300">{{ $fertilization->fertilizer_type }}</td>
                            <td class="p-4 border border-gray-300">{{ $fertilization->amount }}</td>
                            <td class="p-4 border border-gray-300">{{ $fertilization->area }}</td>
                            <td class="p-4 border border-gray-300">{{ $fertilization->officer }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab Pestisida -->
        <div x-show="activeTab === 'pesticides'" class="mt-6">
            <div class="bg-white p-6 shadow-md rounded-lg">
                <h2 class="text-xl font-bold mb-4">Data Pestisida</h2>
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-4 text-left border border-gray-300">ID</th>
                            <th class="p-4 text-left border border-gray-300">Tanggal</th>
                            <th class="p-4 text-left border border-gray-300">Jenis Pestisida</th>
                            <th class="p-4 text-left border border-gray-300">Dosis</th>
                            <th class="p-4 text-left border border-gray-300">Area</th>
                            <th class="p-4 text-left border border-gray-300">Target Hama</th>
                            <th class="p-4 text-left border border-gray-300">Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pesticides as $pesticide)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-4 border border-gray-300">{{ $pesticide->id }}</td>
                            <td class="p-4 border border-gray-300">{{ $pesticide->date }}</td>
                            <td class="p-4 border border-gray-300">{{ $pesticide->pesticide_type }}</td>
                            <td class="p-4 border border-gray-300">{{ $pesticide->dosage }}</td>
                            <td class="p-4 border border-gray-300">{{ $pesticide->area }}</td>
                            <td class="p-4 border border-gray-300">{{ $pesticide->target_pest }}</td>
                            <td class="p-4 border border-gray-300">{{ $pesticide->officer }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab Produksi -->
        <div x-show="activeTab === 'production'" class="mt-6">
            <div class="bg-white p-6 shadow-md rounded-lg">
                <h2 class="text-xl font-bold mb-4">Data Produksi</h2>
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-4 text-left border border-gray-300">ID</th>
                            <th class="p-4 text-left border border-gray-300">Tanggal</th>
                            <th class="p-4 text-left border border-gray-300">Jumlah Panen (kg)</th>
                            <th class="p-4 text-left border border-gray-300">Kualitas</th>
                            <th class="p-4 text-left border border-gray-300">Area</th>
                            <th class="p-4 text-left border border-gray-300">Petugas</th>
                            <th class="p-4 text-left border border-gray-300">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productions as $production)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-4 border border-gray-300">{{ $production->id }}</td>
                            <td class="p-4 border border-gray-300">{{ $production->date }}</td>
                            <td class="p-4 border border-gray-300">{{ $production->amount }}</td>
                            <td class="p-4 border border-gray-300">{{ $production->quality }}</td>
                            <td class="p-4 border border-gray-300">{{ $production->area }}</td>
                            <td class="p-4 border border-gray-300">{{ $production->officer }}</td>
                            <td class="p-4 border border-gray-300">{{ $production->notes }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Kegiatan -->
    <div x-show="showAddModal" class="fixed inset-0 flex items-center justify-center z-[70]">
        <div class="modal-overlay absolute inset-0 bg-black opacity-50"></div>

        <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded shadow-lg relative z-[70] overflow-y-auto">
            <div class="modal-content py-4 text-left px-6">
                <!-- Modal Header -->
                <div class="flex justify-between items-center pb-3">
                    <p class="text-2xl font-bold">Tambah Kegiatan Baru</p>
                    <button @click="showAddModal = false" class="modal-close cursor-pointer z-70">
                        <span class="text-3xl">&times;</span>
                    </button>
                </div>

                <!-- Modal Body -->
                <form action="{{ route('pengelolaan.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="tanggal">
                            Tanggal
                        </label>
                        <input type="date" name="tanggal" id="tanggal" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="jenis_kegiatan">
                            Jenis Kegiatan
                        </label>
                        <select name="jenis_kegiatan" id="jenis_kegiatan" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">Pilih Jenis Kegiatan</option>
                            <option value="Penanaman">Penanaman</option>
                            <option value="Pemupukan">Pemupukan</option>
                            <option value="Pengendalian Hama dan Penyakit">Pengendalian Hama dan Penyakit</option>
                            <option value="Panen">Panen</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="deskripsi">
                            Deskripsi
                        </label>
                        <textarea name="deskripsi" id="deskripsi" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            rows="3"></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="petugas">
                            Petugas
                        </label>
                        <input type="text" name="petugas" id="petugas" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="button" class="px-4 bg-transparent p-3 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-400 mr-2" @click="showAddModal = false">Batal</button>
                        <button type="submit" class="px-4 bg-blue-500 p-3 rounded-lg text-white hover:bg-blue-400">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div x-show="showDeleteModal" class="fixed inset-0 flex items-center justify-center z-[9999]">
        <div class="modal-overlay absolute inset-0 bg-black opacity-50"></div>

        <div class="bg-white w-96 rounded-lg shadow-lg relative z-[9999]">
            <div class="p-6">
                <h2 class="text-xl font-bold mb-4">Konfirmasi Hapus</h2>
                <p class="mb-6">Apakah Anda yakin ingin menghapus kegiatan ini?</p>

                <div class="flex justify-end space-x-2">
                    <button @click="showDeleteModal = false"
                        class="px-6 py-2 bg-gray-200 hover:bg-gray-300 rounded-md font-medium">
                        Batal
                    </button>
                    <form :action="'/pengelolaan/' + deleteId" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="px-6 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md font-medium">
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
@parent
<script>
    // Fungsi untuk menampilkan koordinat saat polygon diklik
    function onPolygonClick(e) {
        const latlng = e.latlng;
        const lat = latlng.lat.toFixed(6);
        const lng = latlng.lng.toFixed(6);

        // Tampilkan popup dengan koordinat
        L.popup()
            .setLatLng(latlng)
            .setContent(`<b>Koordinat:</b><br>Latitude: ${lat}<br>Longitude: ${lng}`)
            .openOn(map);
    }

    // Tambahkan event listener untuk setiap polygon yang dibuat
    map.on('pm:create', function(e) {
        const layer = e.layer;
        layer.on('click', onPolygonClick);
    });

    // Tambahkan event listener untuk polygon yang sudah ada
    map.eachLayer(function(layer) {
        if (layer instanceof L.Polygon) {
            layer.on('click', onPolygonClick);
        }
    });
</script>
@endsection
