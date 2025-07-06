{{-- filepath: /c:/laragon/www/laravel11/resources/views/pages/webgis.blade.php --}}
@extends('layouts.app')

@section('title', 'Stok - Symadu')
@section('header-title', 'Manajemen Stok')

@section('content')

<div id="loading-screen">
</div>

<div class="w-full flex flex-col h-screen overflow-y-auto" x-data="{
    showAddModal: false,
    showOutModal: false,
    showExportModal: false,
    editStockId: null,
    showEditForm: false,
    showDeleteModal: false,
    deleteStockId: null,
    activeTab: 'bibit_pohon',
    activeSubTab: 'total',
    searchQuery: ''
}">
    <!-- Header dengan Tab, Search Bar dan Tombol Tambah dalam satu baris -->
    <div class="flex flex-col md:flex-row justify-between items-center px-6 mt-6 mb-6 gap-4">
        <!-- Navigasi Tab dengan desain yang lebih profesional -->
        <div class="flex bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <button @click="activeTab = 'bibit_pohon'"
                :class="{ 'bg-emerald-700 text-white': activeTab === 'bibit_pohon', 'hover:bg-gray-100': activeTab !== 'bibit_pohon' }"
                class="py-2 px-4 text-sm font-medium transition-colors duration-200 focus:outline-none">
                Bibit & Pohon
            </button>
            <button @click="activeTab = 'pupuk'"
                :class="{ 'bg-emerald-700 text-white': activeTab === 'pupuk', 'hover:bg-gray-100': activeTab !== 'pupuk' }"
                class="py-2 px-4 text-sm font-medium transition-colors duration-200 focus:outline-none">
                Pupuk
            </button>
            <button @click="activeTab = 'pestisida_fungisida'"
                :class="{ 'bg-emerald-700 text-white': activeTab === 'pestisida_fungisida', 'hover:bg-gray-100': activeTab !== 'pestisida_fungisida' }"
                class="py-2 px-4 text-sm font-medium transition-colors duration-200 focus:outline-none">
                Pestisida
            </button>
            <button @click="activeTab = 'alat_perlengkapan'"
                :class="{ 'bg-emerald-700 text-white': activeTab === 'alat_perlengkapan', 'hover:bg-gray-100': activeTab !== 'alat_perlengkapan' }"
                class="py-2 px-4 text-sm font-medium transition-colors duration-200 focus:outline-none">
                Alat & Perlengkapan
            </button>
            <button @click="activeTab = 'zat_pengatur_tumbuh'"
                :class="{ 'bg-emerald-700 text-white': activeTab === 'zat_pengatur_tumbuh', 'hover:bg-gray-100': activeTab !== 'zat_pengatur_tumbuh' }"
                class="py-2 px-4 text-sm font-medium transition-colors duration-200 focus:outline-none">
                Zat Pengatur Tumbuh
            </button>
        </div>

        <!-- Tombol Tambah dan Keluar Stok -->
        <div class="flex gap-2">
            <button class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded-lg shadow-sm flex items-center transition-all duration-200 transform hover:scale-105" @click="showAddModal = true">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Stok Masuk
            </button>
            <button class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded-lg shadow-sm flex items-center transition-all duration-200 transform hover:scale-105" @click="showOutModal = true">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5 10a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1z" clip-rule="evenodd" />
                </svg>
                Stok Keluar
            </button>
            <button class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded-lg shadow-sm flex items-center transition-all duration-200 transform hover:scale-105" @click="showExportModal = true">
                <i class="fas fa-file-excel mr-2"></i>
                Ekspor Data
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="px-6 mb-4">
        <div class="relative">
            <input
                type="text"
                x-model="searchQuery"
                placeholder="Cari stok berdasarkan nama..."
                class="w-full p-3 pl-10 pr-4 rounded-lg border border-gray-300 shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            >
            <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Konten utama -->
    <div class="px-6">
        <!-- Tab Content -->
        @foreach ($stocks as $category => $items)
        <div x-show="activeTab === '{{ $category }}'"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-4"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-bold mb-4 text-gray-800">{{ ucwords(str_replace('_', ' ', $category)) }}</h2>

                <!-- Sub Tabs untuk Total/Masuk/Keluar -->
                <div class="flex mb-4 border-b border-gray-200">
                    <button @click="activeSubTab = 'total'"
                        :class="{ 'border-b-2 border-emerald-600 text-emerald-600': activeSubTab === 'total', 'text-gray-500 hover:text-emerald-600': activeSubTab !== 'total' }"
                        class="py-2 px-4 font-medium focus:outline-none">
                        Total
                    </button>
                    <button @click="activeSubTab = 'masuk'"
                        :class="{ 'border-b-2 border-emerald-600 text-emerald-600': activeSubTab === 'masuk', 'text-gray-500 hover:text-emerald-600': activeSubTab !== 'masuk' }"
                        class="py-2 px-4 font-medium focus:outline-none">
                        Masuk
                    </button>
                    <button @click="activeSubTab = 'keluar'"
                        :class="{ 'border-b-2 border-emerald-600 text-emerald-600': activeSubTab === 'keluar', 'text-gray-500 hover:text-emerald-600': activeSubTab !== 'keluar' }"
                        class="py-2 px-4 font-medium focus:outline-none">
                        Keluar
                    </button>
                </div>

                <!-- Tab Total -->
                <div x-show="activeSubTab === 'total'" class="overflow-x-auto rounded-lg shadow">
                    @php
                        $satuanPerItem = [];

                        foreach ($items as $stock) {
                            $quantity = $stock->quantity;
                            if (isset($stock->type) && $stock->type === 'out') {
                                $quantity = -$quantity;
                            }

                            // Kelompokkan berdasarkan nama barang dan satuan (case-insensitive)
                            $normalizedName = strtolower($stock->name);
                            $key = $normalizedName . '|' . $stock->unit;

                            if (!isset($satuanPerItem[$key])) {
                                // Gunakan name asli untuk tampilan, normalizedName untuk pengelompokan
                                $satuanPerItem[$key] = ['name' => $stock->name, 'unit' => $stock->unit, 'total' => 0];
                            }
                            $satuanPerItem[$key]['total'] += $quantity;
                        }
                    @endphp

                    <table class="w-full border-collapse bg-white">
                        <thead>
                            <tr class="bg-emerald-800 text-white">
                                <th class="p-4 text-center font-semibold text-base capitalize">Nama</th>
                                <th class="p-4 text-center font-semibold text-base capitalize">Jumlah</th>
                                <th class="p-4 text-center font-semibold text-base capitalize">Satuan</th>
                                <th class="p-4 text-center font-semibold text-base capitalize">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @if(count($satuanPerItem) > 0)
                                @foreach($satuanPerItem as $item)
                                <tr class="hover:bg-gray-50 transition-colors duration-150" x-show="!searchQuery || '{{ strtolower($item['name']) }}'.includes(searchQuery.toLowerCase())">
                                    <td class="p-4 text-center text-gray-700 text-base break-words whitespace-normal">{{ $item['name'] }}</td>
                                    <td class="p-4 text-center text-gray-700 text-base">
                                        <span class="{{ $item['total'] < 0 ? 'text-red-600 font-bold' : 'text-emerald-600 font-bold' }}">
                                            {{ $item['total'] }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-center text-gray-700 text-base">{{ $item['unit'] }}</td>
                                    <td class="p-4 text-center">
                                        @if($item['total'] <= 0)
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">Habis/Kurang</span>
                                        @elseif($item['total'] < 5)
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">Stok Rendah</span>
                                        @else
                                            <span class="px-2 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-medium">Stok Cukup</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            @else
                            <tr>
                                <td colspan="4" class="p-4 text-center text-gray-500 text-base">Tidak ada data yang tersedia</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Tab Stok Masuk -->
                <div x-show="activeSubTab === 'masuk'" class="overflow-x-auto rounded-lg shadow">
                    <table class="w-full border-collapse bg-white" id="stok-masuk">
                        <thead>
                            <tr class="bg-emerald-800 text-white">
                                <th class="p-4 text-center font-semibold text-base capitalize">Nama</th>
                                <th class="p-4 text-center font-semibold text-base capitalize">Jumlah</th>
                                <th class="p-4 text-center font-semibold text-base capitalize">Satuan</th>
                                <th class="p-4 text-center font-semibold text-base capitalize">Tanggal</th>
                                <th class="p-4 text-center font-semibold text-base capitalize w-48">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @php
                                $inItems = $items->filter(function($stock) {
                                    return !isset($stock->type) || $stock->type === 'in';
                                })->sortByDesc('date_added');
                            @endphp

                            @foreach ($inItems as $stock)
                            <tr class="hover:bg-gray-50 transition-colors duration-150" id="stok-row-{{ $stock->id }}" x-show="!searchQuery || '{{ strtolower($stock->name) }}'.includes(searchQuery.toLowerCase())">
                                <td class="p-4 text-center text-gray-700 text-base break-words whitespace-normal">{{ $stock->name }}</td>
                                <td class="p-4 text-center text-gray-700 text-base">{{ $stock->quantity }}</td>
                                <td class="p-4 text-center text-gray-700 text-base">{{ $stock->unit }}</td>
                                <td class="p-4 text-center text-gray-700 text-base">{{ $stock->date_added }}</td>
                                <td class="p-4">
                                    <div class="flex space-x-2 justify-center">
                                        <button @click="editStockId = {{ $stock->id }}; showEditForm = true; console.log('Edit ID:', {{ $stock->id }})"
                                            class="bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded-md shadow-sm transition-all duration-200 transform hover:scale-105 flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                            </svg>
                                            Edit
                                        </button>
                                        <button @click="deleteStockId = {{ $stock->id }}; showDeleteModal = true"
                                            class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-md shadow-sm transition-all duration-200 transform hover:scale-105 flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach

                            @if(count($inItems) == 0)
                            <tr>
                                <td colspan="5" class="p-4 text-center text-gray-500 text-base">Tidak ada data stok masuk</td>
                            </tr>
                            @endif

                            <tr x-show="searchQuery && !document.querySelectorAll('#stok-masuk tr[id^=stok-row]:not(.hidden)').length">
                                <td colspan="5" class="p-4 text-center text-gray-500 text-base">Tidak ada hasil pencarian untuk "<span x-text="searchQuery"></span>"</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tab Stok Keluar -->
                <div x-show="activeSubTab === 'keluar'" class="overflow-x-auto rounded-lg shadow">
                    <table class="w-full border-collapse bg-white" id="stok-keluar">
                        <thead>
                            <tr class="bg-emerald-800 text-white">
                                <th class="p-4 text-center font-semibold text-base capitalize">Nama</th>
                                <th class="p-4 text-center font-semibold text-base capitalize">Jumlah</th>
                                <th class="p-4 text-center font-semibold text-base capitalize">Satuan</th>
                                <th class="p-4 text-center font-semibold text-base capitalize">Tanggal</th>
                                <th class="p-4 text-center font-semibold text-base capitalize w-48">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @php
                                $outItems = $items->filter(function($stock) {
                                    return isset($stock->type) && $stock->type === 'out';
                                })->sortByDesc('date_added');
                            @endphp

                            @foreach ($outItems as $stock)
                            <tr class="hover:bg-gray-50 transition-colors duration-150" id="stok-row-{{ $stock->id }}" x-show="!searchQuery || '{{ strtolower($stock->name) }}'.includes(searchQuery.toLowerCase())">
                                <td class="p-4 text-center text-gray-700 text-base break-words whitespace-normal">{{ $stock->name }}</td>
                                <td class="p-4 text-center text-gray-700 text-base">{{ $stock->quantity }}</td>
                                <td class="p-4 text-center text-gray-700 text-base">{{ $stock->unit }}</td>
                                <td class="p-4 text-center text-gray-700 text-base">{{ $stock->date_added }}</td>
                                <td class="p-4">
                                    <div class="flex space-x-2 justify-center">
                                        <button @click="editStockId = {{ $stock->id }}; showEditForm = true; console.log('Edit ID:', {{ $stock->id }})"
                                            class="bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded-md shadow-sm transition-all duration-200 transform hover:scale-105 flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                            </svg>
                                            Edit
                                        </button>
                                        <button @click="deleteStockId = {{ $stock->id }}; showDeleteModal = true"
                                            class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-md shadow-sm transition-all duration-200 transform hover:scale-105 flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach

                            @if(count($outItems) == 0)
                            <tr>
                                <td colspan="5" class="p-4 text-center text-gray-500 text-base">Tidak ada data stok keluar</td>
                            </tr>
                            @endif

                            <tr x-show="searchQuery && !document.querySelectorAll('#stok-keluar tr[id^=stok-row]:not(.hidden)').length">
                                <td colspan="5" class="p-4 text-center text-gray-500 text-base">Tidak ada hasil pencarian untuk "<span x-text="searchQuery"></span>"</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div x-show="showDeleteModal"
         class="fixed inset-0 flex items-center justify-center z-[9999]"
         x-cloak>
        <div class="fixed inset-0 bg-black opacity-50" @click="showDeleteModal = false"></div>

        <div class="relative bg-white w-96 rounded-lg shadow-lg z-[10000]">
            <div class="p-6">
                <h2 class="text-xl font-bold mb-4">Konfirmasi Hapus</h2>
                <p class="mb-6">Apakah Anda yakin ingin menghapus stok ini?</p>

                <div class="flex justify-end space-x-2">
                    <button type="button" @click="showDeleteModal = false"
                        class="px-6 py-2 bg-gray-200 hover:bg-gray-300 rounded-md font-medium">
                        Batal
                    </button>
                    <button type="button" @click="
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '{{ url('/stok') }}/' + deleteStockId;
                        form.style.display = 'none';

                        const method = document.createElement('input');
                        method.type = 'hidden';
                        method.name = '_method';
                        method.value = 'DELETE';

                        const token = document.createElement('input');
                        token.type = 'hidden';
                        token.name = '_token';
                        token.value = '{{ csrf_token() }}';

                        form.appendChild(method);
                        form.appendChild(token);
                        document.body.appendChild(form);
                        form.submit();
                    "
                        class="px-6 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md font-medium">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form Tambah Stok -->
    <div x-show="showAddModal"
         class="fixed inset-0 flex items-center justify-center z-[9999]"
         x-cloak>
        <div class="fixed inset-0 bg-black opacity-50" @click="showAddModal = false"></div>

        <div class="relative bg-white w-full max-w-2xl rounded-lg shadow-lg z-[10000] m-4">
            <div class="p-6">
                <h2 class="text-xl font-bold mb-4 text-gray-800">Tambah Stok Masuk</h2>
                <form action="{{ route('stok.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="type" value="in">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Barang</label>
                            <input type="text" name="name" placeholder="Nama Barang" required
                                class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                            <select name="category" class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                <option value="bibit_pohon">Bibit & Pohon</option>
                                <option value="pupuk">Pupuk</option>
                                <option value="pestisida_fungisida">Pestisida</option>
                                <option value="alat_perlengkapan">Alat & Perlengkapan</option>
                                <option value="zat_pengatur_tumbuh">Zat Pengatur Tumbuh</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah</label>
                            <input type="number" name="quantity" placeholder="Jumlah" required
                                class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Satuan</label>
                            <input type="text" name="unit" placeholder="Satuan"
                                class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                            <input type="date" name="date_added" required
                                class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>
                    </div>
                    <div class="flex justify-end space-x-2 pt-2">
                        <button type="button" @click="showAddModal = false"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md shadow-sm transition-all duration-200">
                            Batal
                        </button>
                        <button type="submit"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-md shadow-sm transition-all duration-200">
                            Simpan Stok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Export Excel -->
    <div x-show="showExportModal"
         class="fixed inset-0 flex items-center justify-center z-[9999]"
         x-cloak>
        <div class="fixed inset-0 bg-black opacity-50" @click="showExportModal = false"></div>

        <div class="relative bg-white w-full max-w-md rounded-lg shadow-lg z-[10000] m-4">
            <div class="p-6">
                <h2 class="text-xl font-bold mb-4 text-gray-800">Ekspor Data Stok</h2>
                <form action="{{ route('stok.export-excel') }}" method="GET" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                        <select name="category" class="w-full border rounded-md p-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="all">Semua Kategori</option>
                            <option value="bibit_pohon">Bibit & Pohon</option>
                            <option value="pupuk">Pupuk</option>
                            <option value="pestisida_fungisida">Pestisida</option>
                            <option value="alat_perlengkapan">Alat & Perlengkapan</option>
                            <option value="zat_pengatur_tumbuh">Zat Pengatur Tumbuh</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Stok</label>
                        <select name="export_type" class="w-full border rounded-md p-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="all">Semua</option>
                            <option value="in">Stok Masuk</option>
                            <option value="out">Stok Keluar</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-2 pt-4">
                        <button type="button" @click="showExportModal = false"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md shadow-sm transition-all duration-200">
                            Batal
                        </button>
                        <button type="submit"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-md shadow-sm transition-all duration-200 flex items-center">
                            <i class="fas fa-file-excel mr-2"></i>
                            Ekspor Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Form Tambah Stok Keluar -->
    <div x-show="showOutModal"
         class="fixed inset-0 flex items-center justify-center z-[9999]"
         x-cloak>
        <div class="fixed inset-0 bg-black opacity-50" @click="showOutModal = false"></div>

        <div class="relative bg-white w-full max-w-2xl rounded-lg shadow-lg z-[10000] m-4">
            <div class="p-6">
                <h2 class="text-xl font-bold mb-4 text-gray-800">Tambah Stok Keluar</h2>
                <form action="{{ route('stok.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="type" value="out">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Barang</label>
                            <input type="text" name="name" placeholder="Nama Barang" required
                                class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                            <select name="category" class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                <option value="bibit_pohon">Bibit & Pohon</option>
                                <option value="pupuk">Pupuk</option>
                                <option value="pestisida_fungisida">Pestisida</option>
                                <option value="alat_perlengkapan">Alat & Perlengkapan</option>
                                <option value="zat_pengatur_tumbuh">Zat Pengatur Tumbuh</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah</label>
                            <input type="number" name="quantity" placeholder="Jumlah" required
                                class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Satuan</label>
                            <input type="text" name="unit" placeholder="Satuan"
                                class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                            <input type="date" name="date_added" required
                                class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>
                    </div>
                    <div class="flex justify-end space-x-2 pt-2">
                        <button type="button" @click="showOutModal = false"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md shadow-sm transition-all duration-200">
                            Batal
                        </button>
                        <button type="submit"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-md shadow-sm transition-all duration-200">
                            Simpan Stok Keluar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Form Edit Stok -->
    <div x-show="showEditForm && editStockId !== null"
         class="fixed inset-0 flex items-center justify-center z-[9999]"
         x-cloak>
        <div class="fixed inset-0 bg-black opacity-50" @click="showEditForm = false; editStockId = null;"></div>

        <div class="relative bg-white w-full max-w-2xl rounded-lg shadow-lg z-[10000] m-4">
            <div class="p-6">
                <h2 class="text-xl font-bold mb-4 text-gray-800">Edit Data Stok</h2>
                <form x-data="{ }" :id="'edit-form-' + editStockId" :action="'{{ url('/stok') }}/' + editStockId" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id" :value="editStockId">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Barang</label>
                            <input type="text" name="name" :value="document.querySelector('#stok-row-' + editStockId + ' td:nth-child(1)').textContent.trim()" required
                                class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                            <select name="category" class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                <option value="bibit_pohon" x-bind:selected="activeTab === 'bibit_pohon'">Bibit & Pohon</option>
                                <option value="pupuk" x-bind:selected="activeTab === 'pupuk'">Pupuk</option>
                                <option value="pestisida_fungisida" x-bind:selected="activeTab === 'pestisida_fungisida'">Pestisida</option>
                                <option value="alat_perlengkapan" x-bind:selected="activeTab === 'alat_perlengkapan'">Alat & Perlengkapan</option>
                                <option value="zat_pengatur_tumbuh" x-bind:selected="activeTab === 'zat_pengatur_tumbuh'">Zat Pengatur Tumbuh</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah</label>
                            <input type="number" name="quantity" :value="document.querySelector('#stok-row-' + editStockId + ' td:nth-child(2)').textContent.trim()" required
                                class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Satuan</label>
                            <input type="text" name="unit" :value="document.querySelector('#stok-row-' + editStockId + ' td:nth-child(3)').textContent.trim()"
                                class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipe</label>
                            <select name="type" class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                <option value="in" x-bind:selected="activeSubTab === 'masuk'">Stok Masuk</option>
                                <option value="out" x-bind:selected="activeSubTab === 'keluar'">Stok Keluar</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                        <input type="date" name="date_added" :value="document.querySelector('#stok-row-' + editStockId + ' td:nth-child(4)').textContent.trim()" required
                            class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    </div>
                    <div class="flex justify-end space-x-2 pt-2">
                        <button type="button" @click="showEditForm = false; editStockId = null;"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md shadow-sm transition-all duration-200">
                            Batal
                        </button>
                        <button type="submit"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-md shadow-sm transition-all duration-200">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Whitespace di bawah halaman -->
    <div class="py-16"></div>
</div>
@endsection

@section('scripts')
@parent
<script>
    // Cek apakah ada pesan sukses dari session
    document.addEventListener('DOMContentLoaded', function() {
        // Script tambahan jika diperlukan
    });
</script>
@endsection

