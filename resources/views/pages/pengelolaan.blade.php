@extends('layouts.app')

@section('title', 'Pengelolaan - Symadu')
@section('header-title', 'Kegiatan Pengelolaan')

@section('content')
<div id="loading-screen">
</div>

<script>
// Menyimpan data kegiatan dalam bentuk JavaScript
const kegiatanData = {
    @foreach($kegiatan as $item)
        "{{ $item->id }}": {
            id: {{ $item->id }},
            nama_kegiatan: "{{ addslashes($item->nama_kegiatan ?? 'N/A') }}",
            jenis_kegiatan: "{{ addslashes($item->jenis_kegiatan) }}",
            deskripsi_kegiatan: "{{ addslashes($item->deskripsi_kegiatan ?? $item->deskripsi ?? '') }}",
            tanggal_mulai: "{{ $item->tanggal_mulai ?? $item->tanggal ?? '' }}",
            tanggal_selesai: "{{ $item->tanggal_selesai ?? '' }}",
            status: "{{ $item->status ?? ($item->selesai ? 'Selesai' : 'Belum Berjalan') }}"
        },
    @endforeach
};
</script>

<div class="w-full flex flex-col h-screen overflow-y-auto" x-data="{
    showAddModal: false,
    showDeleteModal: false,
    deleteId: null,
    editKegiatanId: null,
    showEditForm: false,
    showEditConfirmModal: false,
    showChangeStatusModal: false,
    changeStatusId: null,
    currentStatus: null,
    newKegiatanStatus: 'Belum Berjalan',
    activeTab: (new URLSearchParams(window.location.search).get('tab')) || ({{ (Auth::check() && Auth::user()->role === 'Guest') ? "'trees'" : "'kegiatan'" }}),
    activeSubTab: 'all',
    resetEditFormData() {
        this.editKegiatanId = null;
        this.showEditForm = false;
    }
}" x-init="$watch('showAddModal', value => {
    if (value) {
        document.body.classList.add('modal-open');
    } else {
        document.body.classList.remove('modal-open');
    }
})
$watch('showDeleteModal', value => {
    if (value) {
        document.body.classList.add('modal-open');
    } else {
        document.body.classList.remove('modal-open');
    }
})
$watch('showChangeStatusModal', value => {
    if (value) {
        document.body.classList.add('modal-open');
    } else {
        document.body.classList.remove('modal-open');
    }
})
$watch('showEditForm', value => {
    if (value) {
        document.body.classList.add('modal-open');
    } else {
        document.body.classList.remove('modal-open');
        setTimeout(() => resetEditFormData(), 300);
    }
})
$watch('showEditConfirmModal', value => {
    if (value) {
        document.body.classList.add('modal-open');
    } else {
        document.body.classList.remove('modal-open');
    }
})">
    <!-- Header dengan Tab dan Tombol Tambah dalam satu baris -->
    <div class="flex flex-col md:flex-row justify-between items-center px-6 mt-6 mb-6 gap-4">
        <!-- Navigasi Tab dengan desain yang lebih profesional -->
        <div class="flex bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            @if(Auth::check() && Auth::user()->role !== 'Guest')
            <button @click="activeTab = 'kegiatan'"
                :class="{ 'bg-emerald-700 text-white': activeTab === 'kegiatan', 'hover:bg-gray-100': activeTab !== 'kegiatan' }"
                class="py-2 px-4 text-sm font-medium transition-colors duration-200 focus:outline-none">
                Kegiatan
            </button>
            @endif
            <button @click="activeTab = 'trees'"
                :class="{ 'bg-emerald-700 text-white': activeTab === 'trees', 'hover:bg-gray-100': activeTab !== 'trees' }"
                class="py-2 px-4 text-sm font-medium transition-colors duration-200 focus:outline-none">
                Data Pohon
            </button>
            <button @click="activeTab = 'blok'"
                :class="{ 'bg-emerald-700 text-white': activeTab === 'blok', 'hover:bg-gray-100': activeTab !== 'blok' }"
                class="py-2 px-4 text-sm font-medium transition-colors duration-200 focus:outline-none">
                Data Blok Kebun
            </button>
            @if(Auth::check() && Auth::user()->role !== 'Guest')
            <button @click="activeTab = 'riwayat'"
                :class="{ 'bg-emerald-700 text-white': activeTab === 'riwayat', 'hover:bg-gray-100': activeTab !== 'riwayat' }"
                class="py-2 px-4 text-sm font-medium transition-colors duration-200 focus:outline-none">
                Data Riwayat Kegiatan
            </button>
            @endif
        </div>

        <!-- Tombol Tambah Kegiatan dan Ekspor Data -->
        <div class="flex gap-2">
            <div class="relative" x-data="{ isOpen: false }">
                <button @click="isOpen = !isOpen" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded-lg shadow-sm flex items-center transition-all duration-200 transform hover:scale-105">
                    <i class="fas fa-file-excel mr-2"></i>
                    Ekspor Data
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="isOpen" @click.away="isOpen = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50">
                    <div class="py-1">
                        @if(Auth::check() && Auth::user()->role !== 'Guest')
                        <a href="{{ route('export.kegiatan') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-tasks mr-2"></i> Ekspor Kegiatan
                        </a>
                        @endif
                        <a href="{{ route('export.trees') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-tree mr-2"></i> Ekspor Data Pohon
                        </a>
                        <a href="{{ route('export.plantations') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-map mr-2"></i> Ekspor Data Blok Kebun
                        </a>
                    </div>
                </div>
            </div>

            @if(Auth::check() && in_array(Auth::user()->role_id, [1, 2])) <!-- role_id 1=Superadmin, 2=Manajer -->
            <button class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded-lg shadow-sm flex items-center transition-all duration-200 transform hover:scale-105" @click="showAddModal = true; newKegiatanStatus = 'Belum Berjalan'">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Tambah Kegiatan
            </button>
            @endif
        </div>
    </div>

    <!-- Konten Tab dengan efek transisi -->
    <div class="px-6">
        <!-- Tab Kegiatan -->
        @if(Auth::check() && Auth::user()->role !== 'Guest')
        <div x-show="activeTab === 'kegiatan'"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-4"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6" x-data="{ showTodo: false, searchQuery: '', statusFilter: '' }">
                <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-3">
                    <h2 class="text-xl font-bold text-gray-800">Daftar Kegiatan</h2>
                    <div class="flex gap-2">
                        <!-- Filter Select -->
                        <select id="live-filter-status" x-model="statusFilter" class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">Semua Status</option>
                            <option value="belum_berjalan">Belum Berjalan</option>
                            <option value="sedang_berjalan">Sedang Berjalan</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="relative mb-4">
                    <input
                        type="text"
                        x-model="searchQuery"
                        placeholder="Cari berdasarkan nama kegiatan..."
                        class="w-full p-3 pl-10 pr-4 rounded-lg border border-gray-300 shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                    >
                    <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Sub-tab navigation untuk jenis kegiatan -->
                <div class="mb-6 border-b border-gray-200">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
                        <li class="mr-2">
                            <button @click="activeSubTab = 'all'"
                                :class="{ 'inline-block py-2 px-4 text-emerald-600 border-b-2 border-emerald-600 rounded-t-lg': activeSubTab === 'all', 'inline-block py-2 px-4 text-gray-500 hover:text-gray-600 hover:border-gray-300 border-b-2 border-transparent rounded-t-lg': activeSubTab !== 'all' }">
                                Semua
                            </button>
                        </li>
                        <li class="mr-2">
                            <button @click="activeSubTab = 'Penanaman'"
                                :class="{ 'inline-block py-2 px-4 text-emerald-600 border-b-2 border-emerald-600 rounded-t-lg': activeSubTab === 'Penanaman', 'inline-block py-2 px-4 text-gray-500 hover:text-gray-600 hover:border-gray-300 border-b-2 border-transparent rounded-t-lg': activeSubTab !== 'Penanaman' }">
                                Penanaman
                            </button>
                        </li>
                        <li class="mr-2">
                            <button @click="activeSubTab = 'Pemupukan'"
                                :class="{ 'inline-block py-2 px-4 text-emerald-600 border-b-2 border-emerald-600 rounded-t-lg': activeSubTab === 'Pemupukan', 'inline-block py-2 px-4 text-gray-500 hover:text-gray-600 hover:border-gray-300 border-b-2 border-transparent rounded-t-lg': activeSubTab !== 'Pemupukan' }">
                                Pemupukan
                            </button>
                        </li>
                        <li class="mr-2">
                            <button @click="activeSubTab = 'Pengendalian OPT'"
                                :class="{ 'inline-block py-2 px-4 text-emerald-600 border-b-2 border-emerald-600 rounded-t-lg': activeSubTab === 'Pengendalian OPT', 'inline-block py-2 px-4 text-gray-500 hover:text-gray-600 hover:border-gray-300 border-b-2 border-transparent rounded-t-lg': activeSubTab !== 'Pengendalian OPT' }">
                                Pengendalian OPT
                            </button>
                        </li>
                        <li class="mr-2">
                            <button @click="activeSubTab = 'Pengatur Tumbuh'"
                                :class="{ 'inline-block py-2 px-4 text-emerald-600 border-b-2 border-emerald-600 rounded-t-lg': activeSubTab === 'Pengatur Tumbuh', 'inline-block py-2 px-4 text-gray-500 hover:text-gray-600 hover:border-gray-300 border-b-2 border-transparent rounded-t-lg': activeSubTab !== 'Pengatur Tumbuh' }">
                                Pengatur Tumbuh
                            </button>
                        </li>
                        <li>
                            <button @click="activeSubTab = 'Panen'"
                                :class="{ 'inline-block py-2 px-4 text-emerald-600 border-b-2 border-emerald-600 rounded-t-lg': activeSubTab === 'Panen', 'inline-block py-2 px-4 text-gray-500 hover:text-gray-600 hover:border-gray-300 border-b-2 border-transparent rounded-t-lg': activeSubTab !== 'Panen' }">
                                Panen
                            </button>
                        </li>
                    </ul>
                </div>

                @if(count($kegiatan) > 0)
                    <div class="mb-2 text-sm text-gray-600">
                        Total kegiatan: <b>{{ count($kegiatan) }}</b> |
                        Selesai: <b>{{ $kegiatan->where('status', 'Selesai')->count() }}</b> |
                        Belum selesai: <b>{{ $kegiatan->whereIn('status', ['Belum Berjalan', 'Sedang Berjalan'])->count() }}</b>
                    </div>
                    @php
                    // Mengurutkan kegiatan berdasarkan tanggal mulai terbaru, jika sama urutkan berdasarkan tanggal selesai terbaru
                    $kegiatan = $kegiatan->sort(function($a, $b) {
                        $tanggal_mulai_a = $a->tanggal_mulai ?? $a->tanggal ?? null;
                        $tanggal_mulai_b = $b->tanggal_mulai ?? $b->tanggal ?? null;

                        // Jika tanggal mulai sama
                        if ($tanggal_mulai_a == $tanggal_mulai_b) {
                            // Ambil tanggal selesai
                            $tanggal_selesai_a = $a->tanggal_selesai ?? null;
                            $tanggal_selesai_b = $b->tanggal_selesai ?? null;

                            // Urutkan berdasarkan tanggal selesai terbaru
                            if ($tanggal_selesai_a == $tanggal_selesai_b) {
                                return 0;
                            }
                            return ($tanggal_selesai_a > $tanggal_selesai_b) ? -1 : 1;
                        }

                        // Urutkan berdasarkan tanggal mulai terbaru
                        return ($tanggal_mulai_a > $tanggal_mulai_b) ? -1 : 1;
                    });
                    // Konversi hasil sorting menjadi array untuk menghindari masalah indeks
                    $kegiatan = $kegiatan->values();
                    @endphp
                    <div class="overflow-x-auto rounded-lg shadow-sm">
                        <table class="w-full border-collapse bg-white" style="table-layout: fixed;" id="tabel-kegiatan">
                            <thead>
                                <tr class="bg-emerald-800 text-white">
                                    <th style="width: 100px;" class="p-3 text-center text-base">Nama Kegiatan</th>
                                    <th style="width: 100px;" class="p-3 text-center text-base">Jenis</th>
                                    <th style="width: 440px;" class="p-3 text-center text-base">Deskripsi</th>
                                    <th style="width: 80px;" class="p-3 text-center text-base">Tgl Mulai</th>
                                    <th style="width: 80px;" class="p-3 text-center text-base">Tgl Selesai</th>
                                    <th style="width: 80px;" class="p-3 text-center text-base">Status</th>
                                    <th style="width: 80px;" class="p-3 text-center text-base">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($kegiatan as $item)
                                <tr class="hover:bg-gray-50 transition-colors duration-150"
                                    id="kegiatan-row-{{ $item->id }}"
                                    x-show="(activeSubTab === 'all' || activeSubTab === '{{ $item->jenis_kegiatan }}') &&
                                           (!searchQuery || '{{ strtolower($item->nama_kegiatan ?? 'N/A') }}'.includes(searchQuery.toLowerCase())) &&
                                           (!statusFilter ||
                                            (statusFilter === 'belum_berjalan' && '{{ $item->status }}' === 'Belum Berjalan') ||
                                            (statusFilter === 'sedang_berjalan' && '{{ $item->status }}' === 'Sedang Berjalan') ||
                                            (statusFilter === 'selesai' && '{{ $item->status }}' === 'Selesai'))">
                                    <td style="width: 100px;" class="p-3 text-base text-gray-700">{{ $item->nama_kegiatan ?? 'N/A' }}</td>
                                    <td style="width: 100px;" class="p-3 text-base font-medium text-gray-700">{{ $item->jenis_kegiatan }}</td>
                                    <td style="width: 440px;" class="p-3 text-base text-gray-600 whitespace-normal break-words">{{ $item->deskripsi_kegiatan ?? $item->deskripsi }}</td>
                                    <td style="width: 80px;" class="p-3 text-base text-gray-700 text-center">{{ $item->tanggal_mulai ? \Carbon\Carbon::parse($item->tanggal_mulai)->format('d/m/Y') : 'N/A' }}</td>
                                    <td style="width: 80px;" class="p-3 text-base text-gray-700 text-center">
                                        @if($item->status == 'Selesai' || $item->selesai)
                                            {{ $item->tanggal_selesai ? \Carbon\Carbon::parse($item->tanggal_selesai)->format('d/m/Y') : ($item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') : 'N/A') }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td style="width: 80px;" class="p-3 text-base text-center">
                                        @if($item->status == 'Selesai' || $item->selesai)
                                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full font-medium">Selesai</span>
                                        @elseif($item->status == 'Sedang Berjalan')
                                            <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full font-medium">Sedang Berjalan</span>
                                        @elseif($item->status == 'Belum Berjalan')
                                            <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full font-medium">Belum Berjalan</span>
                                        @else
                                            <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded-full font-medium">Belum Ada</span>
                                        @endif
                                    </td>
                                    <td style="width: 80px;">
                                        <div class="flex justify-center space-x-1">
                                            <button
                                                @click="showChangeStatusModal = true; changeStatusId = {{ $item->id }}; currentStatus = '{{ $item->status ?? ($item->selesai ? 'Selesai' : 'Belum Berjalan') }}'"
                                                class="bg-blue-500 hover:bg-blue-600 text-white rounded-full p-2 transition-all duration-200 flex items-center justify-center"
                                                title="Ubah Status">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                </svg>
                                            </button>

                                            @if(Auth::check() && in_array(Auth::user()->role_id, [1, 2])) <!-- role_id 1=Superadmin, 2=Manajer -->
                                            <button @click="editKegiatanId = {{ $item->id }}; showEditForm = true;"
                                                class="bg-yellow-500 hover:bg-yellow-600 text-white rounded-full p-2 transition-all duration-200 flex items-center justify-center" title="Edit Kegiatan">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button @click="showDeleteModal = true; deleteId = {{ $item->id }}"
                                                class="bg-red-500 hover:bg-red-600 text-white rounded-full p-2 transition-all duration-200 flex items-center justify-center" title="Hapus Kegiatan">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Pesan tidak ada hasil pencarian -->
                        <div x-show="searchQuery && !document.querySelectorAll('#tabel-kegiatan tr[id^=kegiatan-row]:not([style*=\'display: none\'])').length"
                             class="p-4 text-center text-gray-500 text-base">
                            Tidak ada hasil pencarian untuk "<span x-text="searchQuery"></span>"
                        </div>
                    </div>
                @else
                    <div class="text-center text-gray-500 py-8">
                        Tidak ada data kegiatan yang tersedia.<br>
                        <a href="#" class="text-emerald-600 underline" @click.prevent="showAddModal = true">Tambah Kegiatan Baru</a>
                    </div>
                @endif
            </div>
        </div>
        @endif
        <!-- END Tab Kegiatan -->

        <!-- Tab Pohon -->
        <div x-show="activeTab === 'trees'"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-4"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-3">
                    <h2 class="text-xl font-bold text-gray-800">Data Pohon</h2>
                    <div class="flex gap-2">
                        <form method="GET" action="{{ route('pengelolaan') }}" class="flex gap-2 items-center flex-wrap">
                            <input type="hidden" name="tab" value="trees">
                            <input type="text" name="search_tree_id" value="{{ request('search_tree_id') }}" placeholder="Cari ID Pohon..."
                                   class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <select name="varietas" class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="">Semua Varietas</option>
                                @foreach($allTreeVarietas as $varietasOption)
                                <option value="{{ $varietasOption }}" {{ request('varietas') == $varietasOption ? 'selected' : '' }}>{{ $varietasOption }}</option>
                                @endforeach
                            </select>
                            <select name="tahun_tanam" class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="">Semua Tahun Tanam</option>
                                @php
                                    $currentYear = date('Y');
                                    $startYear = 2000; // Tahun awal yang ingin ditampilkan
                                @endphp
                                @for($year = $currentYear; $year >= $startYear; $year--)
                                <option value="{{ $year }}" {{ request('tahun_tanam') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endfor
                            </select>
                            <select name="health_status" class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="">Semua Status Kesehatan</option>
                                <option value="Sehat" {{ request('health_status') == 'Sehat' ? 'selected' : '' }}>Sehat</option>
                                <option value="Stres" {{ request('health_status') == 'Stres' ? 'selected' : '' }}>Stres</option>
                                <option value="Sakit" {{ request('health_status') == 'Sakit' ? 'selected' : '' }}>Sakit</option>
                                <option value="Mati" {{ request('health_status') == 'Mati' ? 'selected' : '' }}>Mati</option>
                            </select>
                            <select name="fase" class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="">Semua Fase</option>
                                @foreach($allTreeFases ?? [] as $faseOption)
                                <option value="{{ $faseOption }}" {{ request('fase') == $faseOption ? 'selected' : '' }}>{{ ucfirst($faseOption) }}</option>
                                @endforeach
                            </select>
                            <select name="blok" class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="">Semua Blok Kebun</option>
                                @foreach($allPlantationNames as $blokNameOption)
                                <option value="{{ $blokNameOption }}" {{ request('blok') == $blokNameOption ? 'selected' : '' }}>{{ ucwords(strtolower($blokNameOption)) }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-3 rounded-md shadow-sm text-sm">Filter</button>
                        </form>
                    </div>
                </div>
                <div class="flex gap-2 mb-2 items-center">
                    <form method="GET" action="" class="flex items-center gap-2">
                        <label for="per_page" class="text-sm text-gray-700">Tampilkan</label>
                        <select name="per_page" id="per_page" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="50" {{ request('per_page', 50) == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                            <option value="200" {{ request('per_page') == 200 ? 'selected' : '' }}>200</option>
                            <option value="500" {{ request('per_page') == 500 ? 'selected' : '' }}>500</option>
                            <option value="1000" {{ request('per_page') == 1000 ? 'selected' : '' }}>1000</option>
                            <option value="all" {{ request('per_page') == 'all' ? 'selected' : '' }}>Semua</option>
                        </select>
                        <span class="text-sm text-gray-700">baris</span>
                        <input type="hidden" name="tab" value="trees">
                        @if(request('varietas'))
                        <input type="hidden" name="varietas" value="{{ request('varietas') }}">
                        @endif
                        @if(request('tahun_tanam'))
                        <input type="hidden" name="tahun_tanam" value="{{ request('tahun_tanam') }}">
                        @endif
                        @if(request('health_status'))
                        <input type="hidden" name="health_status" value="{{ request('health_status') }}">
                        @endif
                        @if(request('fase'))
                        <input type="hidden" name="fase" value="{{ request('fase') }}">
                        @endif
                        @if(request('blok'))
                        <input type="hidden" name="blok" value="{{ request('blok') }}">
                        @endif
                    </form>
                </div>
                <div class="mt-4">
                    @if(request('per_page') != 'all')
                        {{ $trees->appends(request()->except('page'))->links() }}
                    @endif
                </div>
                <div class="overflow-x-auto rounded-lg shadow">
                    <table class="w-full border-collapse bg-white">
                        <thead>
                            <tr class="bg-emerald-800 text-white">
                                <th class="p-4 text-center font-semibold text-base capitalize border-b">No.</th>
                                <th class="p-4 text-center font-semibold text-base capitalize border-b">Id Pohon</th>
                                <th class="p-4 text-center font-semibold text-base capitalize border-b">Varietas</th>
                                <th class="p-4 text-center font-semibold text-base capitalize border-b">Tahun Tanam</th>
                                <th class="p-4 text-center font-semibold text-base capitalize border-b">Fase</th>
                                <th class="p-4 text-center font-semibold text-base capitalize border-b">Status Kesehatan</th>
                                <th class="p-4 text-center font-semibold text-base capitalize border-b">Blok Kebun</th>
                                <th class="p-4 text-center font-semibold text-base capitalize border-b">Latitude</th>
                                <th class="p-4 text-center font-semibold text-base capitalize border-b">Longitude</th>
                                <th class="p-4 text-center font-semibold text-base capitalize border-b">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
@php
    $sortedTreesThisPage = [];
    if ($trees instanceof \Illuminate\Pagination\LengthAwarePaginator || $trees instanceof \Illuminate\Pagination\Paginator) {
        $sortedTreesThisPage = $trees->items();
    } elseif ($trees instanceof \Illuminate\Support\Collection) {
        $sortedTreesThisPage = $trees->all();
    }

    if (!function_exists('customTreeSortPengelolaan')) {
        function customTreeSortPengelolaan($a, $b) {
            $a_id_str = (string) (isset($a->id) ? $a->id : '');
            $b_id_str = (string) (isset($b->id) ? $b->id : '');

            preg_match('/^(\d+)(.*)$/', $a_id_str, $a_parts);
            preg_match('/^(\d+)(.*)$/', $b_id_str, $b_parts);

            $a_num = isset($a_parts[1]) ? (int)$a_parts[1] : null;
            $a_suffix = isset($a_parts[2]) ? $a_parts[2] : ($a_num === null ? $a_id_str : '');

            $b_num = isset($b_parts[1]) ? (int)$b_parts[1] : null;
            $b_suffix = isset($b_parts[2]) ? $b_parts[2] : ($b_num === null ? $b_id_str : '');

            if ($a_num !== null && $b_num === null) return -1; // Angka selalu di depan non-angka
            if ($a_num === null && $b_num !== null) return 1;  // Non-angka selalu setelah angka
            if ($a_num === null && $b_num === null) return strcmp($a_suffix, $b_suffix); // Keduanya non-angka, urutkan string

            // Keduanya punya angka
            if ($a_num !== $b_num) {
                return $a_num <=> $b_num; // Urutkan berdasarkan angka
            }

            // Angka sama, urutkan berdasarkan suffix
            return strcmp($a_suffix, $b_suffix);
        }
    }
    if (!empty($sortedTreesThisPage)) {
        usort($sortedTreesThisPage, 'customTreeSortPengelolaan');
    }
@endphp
                            @foreach($sortedTreesThisPage as $key => $tree)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="p-4 text-center text-gray-700 text-base">
                                    @if ($trees instanceof \Illuminate\Contracts\Pagination\Paginator)
                                        {{ ($trees->currentPage() - 1) * $trees->perPage() + $loop->iteration }}
                                    @else
                                        {{ $loop->iteration }}
                                    @endif
                                </td>
                                <td class="p-4 text-center text-gray-700 text-base">{{ $tree->id }}</td>
                                <td class="p-4 text-center font-medium text-gray-700 text-base">{{ $tree->varietas }}</td>
                                <td class="p-4 text-center text-gray-700 text-base">{{ $tree->tahun_tanam }}</td>
                                <td class="p-4 text-center text-gray-700 text-base">{{ $tree->fase ?? 'N/A' }}</td>
                                <td class="p-4 text-center text-base">
                                    <span class="px-2 py-1 rounded-full text-sm
                                        @if($tree->health_status == 'Sehat') bg-green-200 text-green-800
                                        @elseif($tree->health_status == 'Stres') bg-yellow-200 text-black
                                        @elseif($tree->health_status == 'Sakit') bg-yellow-600 text-white
                                        @elseif($tree->health_status == 'Mati') bg-red-600 text-white
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ $tree->health_status }}
                                    </span>
                                </td>
                                <td class="p-4 text-center text-gray-700 text-base">
                                    @php
                                        $plantation = isset($plantations) ? $plantations->where('id', $tree->plantation_id)->first() : null;
                                    @endphp
                                    {{ $plantation ? ucwords(strtolower($plantation->name)) : 'Tidak ada' }}
                                </td>
                                <td class="p-4 text-center text-gray-700 text-base">{{ number_format($tree->latitude, 6) }}</td>
                                <td class="p-4 text-center text-gray-700 text-base">{{ number_format($tree->longitude, 6) }}</td>
                                <td class="p-4 text-center">
                                    <a href="{{ route('webgis') }}?id={{ $tree->id }}&lat={{ $tree->latitude }}&lng={{ $tree->longitude }}&zoom_to_tree=true"
                                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-3 rounded-md shadow-sm transition-all duration-200 transform hover:scale-105 inline-flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                            @if(count($sortedTreesThisPage) == 0)
                            <tr>
                                <td colspan="10" class="p-4 text-center text-gray-500 text-base">Tidak ada data pohon yang sesuai dengan filter</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab Blok Kebun -->
        <div x-show="activeTab === 'blok'"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-4"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
@php
    $grandTotalLuasAreaBlok = 0;
    $grandTotalPohonBlok = 0;
    // Asumsikan $plantations adalah koleksi semua data blok kebun yang dikirim dari controller
    if (isset($plantations) && $plantations instanceof \Illuminate\Support\Collection && $plantations->isNotEmpty()) {
        $grandTotalLuasAreaBlok = $plantations->sum('luas_area');
        $grandTotalPohonBlok = $plantations->sum('trees_count');
    }
@endphp
                <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-3">
                    <h2 class="text-xl font-bold text-gray-800">Data Blok Kebun</h2>
                    <div class="flex gap-2">
                        <form method="GET" action="{{ route('pengelolaan') }}" class="flex gap-2 items-center">
                            <input type="hidden" name="tab" value="blok">
                            <input type="text" name="search_blok_id" value="{{ request('search_blok_id') }}" placeholder="Cari ID Blok..."
                                   class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-3 rounded-md shadow-sm text-sm">Cari</button>
                        </form>
                    </div>
                </div>
                <div class="flex gap-2 mb-2 items-center">
                    <form method="GET" action="" class="flex items-center gap-2">
                        <label for="per_page_blok" class="text-sm text-gray-700">Tampilkan</label>
                        <select name="per_page_blok" id="per_page_blok" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="50" {{ request('per_page_blok', 50) == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page_blok') == 100 ? 'selected' : '' }}>100</option>
                            <option value="200" {{ request('per_page_blok') == 200 ? 'selected' : '' }}>200</option>
                        </select>
                        <span class="text-sm text-gray-700">baris</span>
                        <input type="hidden" name="tab" value="blok">
                    </form>
                </div>
                <div class="mb-4">
                    {{ $plantations_paged->appends(request()->except('page') + ['tab' => 'blok', 'per_page_blok' => request('per_page_blok', 50)])->links() }}
                </div>
                <div class="overflow-x-auto rounded-lg shadow">
                    <table class="w-full border-collapse bg-white">
                        <thead>
                            <tr class="bg-emerald-800 text-white">
                                <th class="p-4 text-center font-semibold text-base capitalize border-b">Id Blok</th>
                                <th class="p-4 text-center font-semibold text-base capitalize border-b">Nama Blok</th>
                                <th class="p-4 text-center font-semibold text-base capitalize border-b">Luas Area (Ha)</th>
                                <th class="p-4 text-center font-semibold text-base capitalize border-b">Jumlah Pohon</th>
                                <th class="p-4 text-center font-semibold text-base capitalize border-b">Latitude</th>
                                <th class="p-4 text-center font-semibold text-base capitalize border-b">Longitude</th>
                                <th class="p-4 text-center font-semibold text-base capitalize border-b">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @if(isset($plantations_paged) && $plantations_paged->count() > 0)
                                @foreach($plantations_paged as $key => $plantation)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="p-4 text-center text-gray-700 text-base">{{ $plantation->id }}</td>
                                    <td class="p-4 text-center font-medium text-gray-700 text-base">{{ ucwords(strtolower($plantation->name)) }}</td>
                                    <td class="p-4 text-center text-gray-700 text-base">{{ number_format($plantation->luas_area, 2, ',', '.') }}</td>
                                    <td class="p-4 text-center text-base">
                                        <span class="px-2 py-1 rounded-full bg-emerald-100 text-emerald-800 text-sm">
                                            {{ number_format($plantation->trees_count, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-center text-gray-700 text-base">{{ number_format($plantation->latitude, 6) }}</td>
                                    <td class="p-4 text-center text-gray-700 text-base">{{ number_format($plantation->longitude, 6) }}</td>
                                    <td class="p-4 text-center">
                                        <a href="{{ route('webgis') }}?plantation_id={{ $plantation->id }}"
                                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-3 rounded-md shadow-sm transition-all duration-200 transform hover:scale-105 inline-flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7" class="p-4 text-center text-gray-500 text-base">Tidak ada data blok kebun yang tersedia</td>
                                </tr>
                            @endif
                        </tbody>
                        @if(isset($plantations) && $plantations instanceof \Illuminate\Support\Collection && $plantations->isNotEmpty())
                        <tfoot class="bg-gray-100 border-t-2 border-gray-300">
                            <tr class="font-semibold text-gray-700">
                                <td colspan="2" class="p-4 text-right text-base">Total Keseluruhan:</td>
                                <td class="p-4 text-center text-base">{{ number_format($grandTotalLuasAreaBlok, 2, ',', '.') }} Ha</td>
                                <td class="p-4 text-center text-base">
                                    <span class="px-3 py-1 rounded-full bg-emerald-200 text-emerald-800 font-bold">
                                        {{ number_format($grandTotalPohonBlok, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td colspan="2" class="p-4"></td> {{-- Kolom kosong untuk Latitude, Longitude, Aksi --}}
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab Riwayat Kegiatan Pohon -->
        @if(Auth::check() && Auth::user()->role !== 'Guest')
        <div x-show="activeTab === 'riwayat'"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-4"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             class="bg-white rounded-lg shadow-md overflow-hidden"
             x-data="{ activeRiwayatTab: 'pertumbuhan' }">
            <div class="p-6">
                <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-3">
                    <h2 class="text-xl font-bold text-gray-800">Data Riwayat Kegiatan Pohon</h2>
                </div>

                <!-- Sub-tab navigation untuk jenis riwayat -->
                <div class="mb-6 border-b border-gray-200">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
                        <li class="mr-2">
                            <button @click="activeRiwayatTab = 'pertumbuhan'"
                                :class="{ 'inline-block py-2 px-4 text-emerald-600 border-b-2 border-emerald-600 rounded-t-lg': activeRiwayatTab === 'pertumbuhan', 'inline-block py-2 px-4 text-gray-500 hover:text-gray-600 hover:border-gray-300 border-b-2 border-transparent rounded-t-lg': activeRiwayatTab !== 'pertumbuhan' }">
                                Riwayat Pertumbuhan
                            </button>
                        </li>
                        <li class="mr-2">
                            <button @click="activeRiwayatTab = 'kesehatan'"
                                :class="{ 'inline-block py-2 px-4 text-emerald-600 border-b-2 border-emerald-600 rounded-t-lg': activeRiwayatTab === 'kesehatan', 'inline-block py-2 px-4 text-gray-500 hover:text-gray-600 hover:border-gray-300 border-b-2 border-transparent rounded-t-lg': activeRiwayatTab !== 'kesehatan' }">
                                Riwayat Kesehatan
                            </button>
                        </li>
                        <li class="mr-2">
                            <button @click="activeRiwayatTab = 'pemupukan'"
                                :class="{ 'inline-block py-2 px-4 text-emerald-600 border-b-2 border-emerald-600 rounded-t-lg': activeRiwayatTab === 'pemupukan', 'inline-block py-2 px-4 text-gray-500 hover:text-gray-600 hover:border-gray-300 border-b-2 border-transparent rounded-t-lg': activeRiwayatTab !== 'pemupukan' }">
                                Riwayat Pemupukan
                            </button>
                        </li>
                        <li class="mr-2">
                            <button @click="activeRiwayatTab = 'pengendalian_opt'"
                                :class="{ 'inline-block py-2 px-4 text-emerald-600 border-b-2 border-emerald-600 rounded-t-lg': activeRiwayatTab === 'pengendalian_opt', 'inline-block py-2 px-4 text-gray-500 hover:text-gray-600 hover:border-gray-300 border-b-2 border-transparent rounded-t-lg': activeRiwayatTab !== 'pengendalian_opt' }">
                                Riwayat Pengendalian OPT
                            </button>
                        </li>
                        <li class="mr-2">
                            <button @click="activeRiwayatTab = 'zpt'"
                                :class="{ 'inline-block py-2 px-4 text-emerald-600 border-b-2 border-emerald-600 rounded-t-lg': activeRiwayatTab === 'zpt', 'inline-block py-2 px-4 text-gray-500 hover:text-gray-600 hover:border-gray-300 border-b-2 border-transparent rounded-t-lg': activeRiwayatTab !== 'zpt' }">
                                Riwayat ZPT
                            </button>
                        </li>
                        <li>
                            <button @click="activeRiwayatTab = 'panen'"
                                :class="{ 'inline-block py-2 px-4 text-emerald-600 border-b-2 border-emerald-600 rounded-t-lg': activeRiwayatTab === 'panen', 'inline-block py-2 px-4 text-gray-500 hover:text-gray-600 hover:border-gray-300 border-b-2 border-transparent rounded-t-lg': activeRiwayatTab !== 'panen' }">
                                Riwayat Panen
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Filter dan pencarian untuk semua riwayat -->
                <div class="mb-4 flex flex-wrap gap-2">
                    <form method="GET" action="{{ route('pengelolaan') }}" class="flex flex-wrap gap-2 items-center">
                        <input type="hidden" name="tab" value="riwayat">
                        <input type="hidden" name="subtab" x-bind:value="activeRiwayatTab">

                        <input type="text" name="search_tree_id" value="{{ request('search_tree_id') }}" placeholder="Cari ID Pohon..."
                            class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">

                        <select name="varietas" class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">Semua Varietas</option>
                            @foreach($allTreeVarietas as $varietasOption)
                                <option value="{{ $varietasOption }}" {{ request('varietas') == $varietasOption ? 'selected' : '' }}>
                                    {{ $varietasOption }}
                                </option>
                            @endforeach
                        </select>

                        <select name="tahun_tanam" class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">Semua Tahun Tanam</option>
                            @php
                                $currentYear = date('Y');
                                $startYear = 2000; // Tahun awal yang ingin ditampilkan
                            @endphp
                            @for($year = $currentYear; $year >= $startYear; $year--)
                            <option value="{{ $year }}" {{ request('tahun_tanam') == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endfor
                        </select>

                        <select name="blok" class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">Semua Blok Kebun</option>
                            @foreach($allPlantationNames as $blokNameOption)
                                <option value="{{ $blokNameOption }}" {{ request('blok') == $blokNameOption ? 'selected' : '' }}>
                                    {{ ucwords(strtolower($blokNameOption)) }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-3 rounded-md shadow-sm text-sm">
                            Filter
                        </button>
                    </form>
                </div>

                <!-- Content Riwayat Pertumbuhan -->
                <div x-show="activeRiwayatTab === 'pertumbuhan'" class="overflow-x-auto rounded-lg shadow">
                    <table class="w-full border-collapse bg-white">
                        <thead>
                            <tr class="bg-emerald-800 text-white">
                                <th class="p-3 text-left text-base">ID Pohon</th>
                                <th class="p-3 text-left text-base">Varietas</th>
                                <th class="p-3 text-left text-base">Blok Kebun</th>
                                <th class="p-3 text-left text-base">Tanggal</th>
                                <th class="p-3 text-left text-base">Fase</th>
                                <th class="p-3 text-left text-base">Tinggi (cm)</th>
                                <th class="p-3 text-left text-base">Diameter (cm)</th>
                                <th class="p-3 text-center text-base">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @if(isset($treeGrowthRecords) && count($treeGrowthRecords) > 0)
                                @foreach($treeGrowthRecords as $record)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="p-3 text-base text-gray-700">{{ $record->tree->id ?? 'N/A' }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->tree->varietas ?? 'N/A' }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->tree->plantation->name ?? 'N/A' }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ \Carbon\Carbon::parse($record->tanggal)->format('d/m/Y') }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->fase }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->tinggi }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->diameter }}</td>
                                    <td class="p-3 text-center">
                                        <a href="{{ route('tree.dashboard') }}?id={{ $record->tree->id ?? '' }}"
                                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-1 px-3 rounded-md shadow-sm text-sm">
                                            Detail Pohon
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="8" class="p-3 text-center text-gray-500">Tidak ada data riwayat pertumbuhan</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                    @if(isset($treeGrowthRecords) && method_exists($treeGrowthRecords, 'links'))
                        <div class="mt-4">
                            {{ $treeGrowthRecords->appends(request()->except('page'))->links() }}
                        </div>
                    @endif
                </div>

                <!-- Content Riwayat Kesehatan -->
                <div x-show="activeRiwayatTab === 'kesehatan'" class="overflow-x-auto rounded-lg shadow">
                    <table class="w-full border-collapse bg-white">
                        <thead>
                            <tr class="bg-emerald-800 text-white">
                                <th class="p-3 text-left text-base">ID Pohon</th>
                                <th class="p-3 text-left text-base">Varietas</th>
                                <th class="p-3 text-left text-base">Blok Kebun</th>
                                <th class="p-3 text-left text-base">Tanggal Pemeriksaan</th>
                                <th class="p-3 text-left text-base">Status Kesehatan</th>
                                <th class="p-3 text-left text-base">Gejala</th>
                                <th class="p-3 text-left text-base">Tindakan Penanganan</th>
                                <th class="p-3 text-center text-base">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @if(isset($treeHealthRecords) && count($treeHealthRecords) > 0)
                                @foreach($treeHealthRecords as $record)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="p-3 text-base text-gray-700">{{ $record->tree->id ?? 'N/A' }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->tree->varietas ?? 'N/A' }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->tree->plantation->name ?? 'N/A' }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ \Carbon\Carbon::parse($record->tanggal_pemeriksaan)->format('d/m/Y') }}</td>
                                    <td class="p-3 text-base">
                                        <span class="px-2 py-1 rounded-full text-sm
                                            @if($record->status_kesehatan == 'Sehat') bg-green-200 text-green-800
                                            @elseif($record->status_kesehatan == 'Stres') bg-yellow-200 text-black
                                            @elseif($record->status_kesehatan == 'Sakit') bg-yellow-600 text-white
                                            @elseif($record->status_kesehatan == 'Mati') bg-red-600 text-white
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ $record->status_kesehatan }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->gejala }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->tindakan_penanganan }}</td>
                                    <td class="p-3 text-center">
                                        <a href="{{ route('tree.dashboard') }}?id={{ $record->tree->id ?? '' }}"
                                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-1 px-3 rounded-md shadow-sm text-sm">
                                            Detail Pohon
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="8" class="p-3 text-center text-gray-500">Tidak ada data riwayat kesehatan</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                    @if(isset($treeHealthRecords) && method_exists($treeHealthRecords, 'links'))
                        <div class="mt-4">
                            {{ $treeHealthRecords->appends(request()->except('page'))->links() }}
                        </div>
                    @endif
                </div>

                <!-- Content Riwayat Pemupukan -->
                <div x-show="activeRiwayatTab === 'pemupukan'" class="overflow-x-auto rounded-lg shadow">
                    <table class="w-full border-collapse bg-white">
                        <thead>
                            <tr class="bg-emerald-800 text-white">
                                <th class="p-3 text-left text-base">ID Pohon</th>
                                <th class="p-3 text-left text-base">Varietas</th>
                                <th class="p-3 text-left text-base">Blok Kebun</th>
                                <th class="p-3 text-left text-base">Tanggal Pemupukan</th>
                                <th class="p-3 text-left text-base">Nama Pupuk</th>
                                <th class="p-3 text-left text-base">Jenis Pupuk</th>
                                <th class="p-3 text-left text-base">Bentuk Pupuk</th>
                                <th class="p-3 text-left text-base">Dosis</th>
                                <th class="p-3 text-left text-base">Unit</th>
                                <th class="p-3 text-center text-base">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @if(isset($treeFertilizationRecords) && count($treeFertilizationRecords) > 0)
                                @foreach($treeFertilizationRecords as $record)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="p-3 text-base text-gray-700">{{ $record->tree->id ?? 'N/A' }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->tree->varietas ?? 'N/A' }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->tree->plantation->name ?? 'N/A' }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ \Carbon\Carbon::parse($record->tanggal_pemupukan)->format('d/m/Y') }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->nama_pupuk }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->jenis_pupuk }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->bentuk_pupuk }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->dosis_pupuk }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->unit }}</td>
                                    <td class="p-3 text-center">
                                        <a href="{{ route('tree.dashboard') }}?id={{ $record->tree->id ?? '' }}"
                                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-1 px-3 rounded-md shadow-sm text-sm">
                                            Detail Pohon
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="10" class="p-3 text-center text-gray-500">Tidak ada data riwayat pemupukan</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                    @if(isset($treeFertilizationRecords) && method_exists($treeFertilizationRecords, 'links'))
                        <div class="mt-4">
                            {{ $treeFertilizationRecords->appends(request()->except('page'))->links() }}
                        </div>
                    @endif
                </div>

                <!-- Content Riwayat Pengendalian OPT -->
                <div x-show="activeRiwayatTab === 'pengendalian_opt'" class="overflow-x-auto rounded-lg shadow">
                    <table class="w-full border-collapse bg-white">
                        <thead>
                            <tr class="bg-emerald-800 text-white">
                                <th class="p-3 text-left text-base">ID Pohon</th>
                                <th class="p-3 text-left text-base">Varietas</th>
                                <th class="p-3 text-left text-base">Blok Kebun</th>
                                <th class="p-3 text-left text-base">Tanggal Aplikasi</th>
                                <th class="p-3 text-left text-base">Nama Pengendalian OPT</th>
                                <th class="p-3 text-left text-base">Jenis Pengendalian OPT</th>
                                <th class="p-3 text-left text-base">Dosis</th>
                                <th class="p-3 text-left text-base">Unit</th>
                                <th class="p-3 text-center text-base">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @if(isset($treePesticideRecords) && count($treePesticideRecords) > 0)
                                @foreach($treePesticideRecords as $record)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="p-3 text-base text-gray-700">{{ $record->tree->id ?? 'N/A' }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->tree->varietas ?? 'N/A' }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->tree->plantation->name ?? 'N/A' }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ \Carbon\Carbon::parse($record->tanggal_pestisida)->format('d/m/Y') }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->nama_pestisida }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->jenis_pestisida }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->dosis }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->unit }}</td>
                                    <td class="p-3 text-center">
                                        <a href="{{ route('tree.dashboard') }}?id={{ $record->tree->id ?? '' }}"
                                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-1 px-3 rounded-md shadow-sm text-sm">
                                            Detail Pohon
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9" class="p-3 text-center text-gray-500">Tidak ada data riwayat pengendalian OPT</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                    @if(isset($treePesticideRecords) && method_exists($treePesticideRecords, 'links'))
                        <div class="mt-4">
                            {{ $treePesticideRecords->appends(request()->except('page'))->links() }}
                        </div>
                    @endif
                </div>

                <!-- Content Riwayat ZPT -->
                <div x-show="activeRiwayatTab === 'zpt'" class="overflow-x-auto rounded-lg shadow">
                    <table class="w-full border-collapse bg-white">
                        <thead>
                            <tr class="bg-emerald-800 text-white">
                                <th class="p-3 text-left text-base">ID Pohon</th>
                                <th class="p-3 text-left text-base">Varietas</th>
                                <th class="p-3 text-left text-base">Blok Kebun</th>
                                <th class="p-3 text-left text-base">Tanggal Aplikasi</th>
                                <th class="p-3 text-left text-base">Nama ZPT</th>
                                <th class="p-3 text-left text-base">Merek</th>
                                <th class="p-3 text-left text-base">Jenis Senyawa</th>
                                <th class="p-3 text-left text-base">Fase Pertumbuhan</th>
                                <th class="p-3 text-center text-base">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @if(isset($treeZptRecords) && count($treeZptRecords) > 0)
                                @foreach($treeZptRecords as $record)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="p-3 text-base text-gray-700">{{ $record->tree->id ?? 'N/A' }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->tree->varietas ?? 'N/A' }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->tree->plantation->name ?? 'N/A' }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ \Carbon\Carbon::parse($record->tanggal_aplikasi)->format('d/m/Y') }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->nama_zpt }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->merek }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->jenis_senyawa }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->fase_pertumbuhan }}</td>
                                    <td class="p-3 text-center">
                                        <a href="{{ route('tree.dashboard') }}?id={{ $record->tree->id ?? '' }}"
                                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-1 px-3 rounded-md shadow-sm text-sm">
                                            Detail Pohon
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9" class="p-3 text-center text-gray-500">Tidak ada data riwayat ZPT</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                    @if(isset($treeZptRecords) && method_exists($treeZptRecords, 'links'))
                        <div class="mt-4">
                            {{ $treeZptRecords->appends(request()->except('page'))->links() }}
                        </div>
                    @endif
                </div>

                <!-- Content Riwayat Panen -->
                <div x-show="activeRiwayatTab === 'panen'" class="overflow-x-auto rounded-lg shadow">
                    <table class="w-full border-collapse bg-white">
                        <thead>
                            <tr class="bg-emerald-800 text-white">
                                <th class="p-3 text-left text-base">ID Pohon</th>
                                <th class="p-3 text-left text-base">Varietas</th>
                                <th class="p-3 text-left text-base">Blok Kebun</th>
                                <th class="p-3 text-left text-base">Tanggal Panen</th>
                                <th class="p-3 text-left text-base">Jumlah Buah</th>
                                <th class="p-3 text-left text-base">Total Berat</th>
                                <th class="p-3 text-left text-base">Rata-rata per Buah</th>
                                <th class="p-3 text-left text-base">Kondisi Buah</th>
                                <th class="p-3 text-left text-base">Unit</th>
                                <th class="p-3 text-center text-base">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @if(isset($treeHarvestRecords) && count($treeHarvestRecords) > 0)
                                @foreach($treeHarvestRecords as $record)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="p-3 text-base text-gray-700">{{ $record->tree->id ?? 'N/A' }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->tree->varietas ?? 'N/A' }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->tree->plantation->name ?? 'N/A' }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ \Carbon\Carbon::parse($record->tanggal_panen)->format('d/m/Y') }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->fruit_count }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->total_weight }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->average_weight_per_fruit }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->fruit_condition }}</td>
                                    <td class="p-3 text-base text-gray-700">{{ $record->unit }}</td>
                                    <td class="p-3 text-center">
                                        <a href="{{ route('tree.dashboard') }}?id={{ $record->tree->id ?? '' }}"
                                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-1 px-3 rounded-md shadow-sm text-sm">
                                            Detail Pohon
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="10" class="p-3 text-center text-gray-500">Tidak ada data riwayat panen</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                    @if(isset($treeHarvestRecords) && method_exists($treeHarvestRecords, 'links'))
                        <div class="mt-4">
                            {{ $treeHarvestRecords->appends(request()->except('page'))->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
    <!-- END Tab Riwayat Kegiatan Pohon -->

    <!-- Modal Tambah Kegiatan -->
    <div x-show="showAddModal" class="fixed inset-0 flex items-center justify-center z-[9999]"
         x-init="$watch('showAddModal', value => {
            if (value && document.getElementById('tanggal_mulai') && document.getElementById('tanggal_selesai_tambah')) {
                // Reset form saat modal dibuka dan atur validasi tanggal
                const tanggalMulai = document.getElementById('tanggal_mulai');
                const tanggalSelesai = document.getElementById('tanggal_selesai_tambah');

                tanggalMulai.addEventListener('change', function() {
                    tanggalSelesai.min = this.value;
                });
            }
         })">
        <div class="modal-overlay absolute inset-0 bg-black opacity-70"></div>

        <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded shadow-lg relative z-[10000] overflow-y-auto">
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
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="nama_kegiatan">
                            Nama Kegiatan
                        </label>
                        <input type="text" name="nama_kegiatan" id="nama_kegiatan" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="jenis_kegiatan">
                            Jenis Kegiatan
                        </label>
                        <select name="jenis_kegiatan" id="jenis_kegiatan" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            <option value="">Pilih Jenis Kegiatan</option>
                            <option value="Penanaman">Penanaman</option>
                            <option value="Pemupukan">Pemupukan</option>
                            <option value="Pengendalian OPT">Pengendalian OPT</option>
                            <option value="Pengatur Tumbuh">Pengatur Tumbuh</option>
                            <option value="Panen">Panen</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="deskripsi_kegiatan">
                            Deskripsi Kegiatan
                        </label>
                        <textarea name="deskripsi_kegiatan" id="deskripsi_kegiatan" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            rows="3"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="tanggal_mulai">
                                Tanggal Mulai
                            </label>
                            <input type="date" name="tanggal_mulai" id="tanggal_mulai" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>
                        <div x-show="newKegiatanStatus === 'Selesai'">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="tanggal_selesai_tambah">
                                Tanggal Selesai
                            </label>
                            <input type="date" name="tanggal_selesai" id="tanggal_selesai_tambah"
                                   x-bind:required="newKegiatanStatus === 'Selesai'"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="status_tambah">
                            Status
                        </label>
                        <select name="status" id="status_tambah" required x-model="newKegiatanStatus"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            <option value="Belum Berjalan">Belum Berjalan</option>
                            <option value="Sedang Berjalan">Sedang Berjalan</option>
                            <option value="Selesai">Selesai</option>
                        </select>
                    </div>

                    <!-- Menampilkan pesan error jika ada validasi yang gagal -->
                    @if ($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <strong>Oops! Ada kesalahan:</strong>
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="flex justify-end pt-2">
                        <button type="button" class="px-4 bg-transparent p-3 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-400 mr-2" @click="showAddModal = false">Batal</button>
                        <button type="submit" class="px-4 bg-emerald-600 p-3 rounded-lg text-white hover:bg-emerald-700 shadow-sm transition-all duration-200">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div x-show="showDeleteModal"
         class="fixed inset-0 flex items-center justify-center z-[9999]"
         x-cloak>
        <div class="fixed inset-0 bg-black opacity-70"></div>

        <div class="relative bg-white w-96 rounded-lg shadow-lg z-[10000]">
            <div class="p-6">
                <h2 class="text-xl font-bold mb-4">Konfirmasi Hapus</h2>
                <p class="mb-6">Apakah Anda yakin ingin menghapus kegiatan ini?</p>

                <div class="flex justify-end space-x-2">
                    <button @click="showDeleteModal = false"
                        class="px-6 py-2 bg-gray-200 hover:bg-gray-300 rounded-md font-medium transition-all duration-200">
                        Batal
                    </button>
                    <form :action="'/pengelolaan/' + deleteId" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="px-6 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md font-medium shadow-sm transition-all duration-200">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ubah Status -->
    <div x-show="showChangeStatusModal"
         class="fixed inset-0 flex items-center justify-center z-[9999]"
         x-cloak>
        <div class="fixed inset-0 bg-black opacity-70" @click="showChangeStatusModal = false"></div>
        <div class="relative bg-white w-11/12 md:max-w-md mx-auto rounded-lg shadow-lg z-[10000]">
            <div class="p-6">
                <div class="flex justify-between items-center pb-3">
                    <p class="text-2xl font-bold">Ubah Status Kegiatan</p>
                    <button @click="showChangeStatusModal = false" class="cursor-pointer z-50">
                        <span class="text-3xl">&times;</span>
                    </button>
                </div>
                <form :action="'/pengelolaan/update-status/' + changeStatusId" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="new_status">
                            Status Baru
                        </label>
                        <select name="status" id="new_status" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            x-on:change="currentStatus = $event.target.value">
                            <option value="Belum Berjalan" :selected="currentStatus === 'Belum Berjalan'">Belum Berjalan</option>
                            <option value="Sedang Berjalan" :selected="currentStatus === 'Sedang Berjalan'">Sedang Berjalan</option>
                            <option value="Selesai" :selected="currentStatus === 'Selesai'">Selesai</option>
                        </select>
                    </div>
                    <div class="mb-4" x-show="currentStatus === 'Selesai'">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="tanggal_selesai_update">
                            Tanggal Selesai
                        </label>
                        <input type="date" name="tanggal_selesai" id="tanggal_selesai_update"
                            x-bind:required="currentStatus === 'Selesai'"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="button" class="px-4 bg-transparent p-3 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-400 mr-2" @click="showChangeStatusModal = false">Batal</button>
                        <button type="submit" class="px-4 bg-emerald-600 p-3 rounded-lg text-white hover:bg-emerald-700 shadow-sm transition-all duration-200">Simpan Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Edit -->
    <div x-show="showEditConfirmModal"
         class="fixed inset-0 flex items-center justify-center z-[9999]"
         x-cloak>
        <div class="fixed inset-0 bg-black opacity-70" @click="showEditConfirmModal = false"></div>

        <div class="relative bg-white w-96 rounded-lg shadow-lg z-[10000]">
            <div class="p-6">
                <h2 class="text-xl font-bold mb-4">Konfirmasi Perubahan</h2>
                <p class="mb-6">Apakah Anda yakin ingin menyimpan perubahan ini?</p>

                <div class="flex justify-end space-x-2">
                    <button type="button" @click="showEditConfirmModal = false"
                        class="px-6 py-2 bg-gray-200 hover:bg-gray-300 rounded-md font-medium">
                        Batal
                    </button>
                    <button type="button" @click="document.getElementById('edit-form-' + editKegiatanId).submit(); showEditConfirmModal = false;"
                        class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md font-medium">
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Kegiatan -->
    <div x-show="showEditForm" class="fixed inset-0 flex items-center justify-center z-[9999]"
         x-cloak>
        <div class="modal-overlay absolute inset-0 bg-black opacity-70"></div>

        <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded shadow-lg relative z-[10000] overflow-y-auto">
            <div class="modal-content py-4 text-left px-6">
                <!-- Modal Header -->
                <div class="flex justify-between items-center pb-3">
                    <p class="text-2xl font-bold">Edit Kegiatan</p>
                    <button @click="showEditForm = false" class="modal-close cursor-pointer z-70">
                        <span class="text-3xl">&times;</span>
                    </button>
                </div>

                <!-- Modal Body -->
                <div x-data="{
                    currentKegiatan: null,
                    kegiatanStatus: '',
                    loadKegiatanData() {
                        // Reset form terlebih dahulu
                        document.getElementById('edit_nama_kegiatan').value = '';
                        document.getElementById('edit_jenis_kegiatan').value = '';
                        document.getElementById('edit_deskripsi_kegiatan').value = '';
                        document.getElementById('edit_tanggal_mulai').value = '';
                        document.getElementById('edit_tanggal_selesai').value = '';
                        document.getElementById('edit_status').value = '';

                        // Menggunakan data dari variabel JavaScript yang telah didefinisikan
                        const data = kegiatanData[editKegiatanId];
                        if (data) {
                            this.currentKegiatan = data;
                            this.kegiatanStatus = data.status;

                            // Mengisi form dengan data yang ada
                            document.getElementById('edit_nama_kegiatan').value = data.nama_kegiatan;
                            document.getElementById('edit_jenis_kegiatan').value = data.jenis_kegiatan;
                            document.getElementById('edit_deskripsi_kegiatan').value = data.deskripsi_kegiatan;

                            // Format tanggal untuk input date (YYYY-MM-DD)
                            if (data.tanggal_mulai) {
                                // Pastikan format tanggal benar YYYY-MM-DD
                                let dateStr = data.tanggal_mulai;
                                if (dateStr.includes(' ')) {
                                    dateStr = dateStr.split(' ')[0]; // Ambil hanya bagian tanggal jika ada timestamp
                                }
                                // Pastikan format tanggal sudah yyyy-mm-dd
                                if (dateStr.includes('/')) {
                                    const parts = dateStr.split('/');
                                    if (parts.length === 3) {
                                        dateStr = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
                                    }
                                }
                                document.getElementById('edit_tanggal_mulai').value = dateStr;
                            }

                            if (data.tanggal_selesai) {
                                // Pastikan format tanggal benar YYYY-MM-DD
                                let dateStr = data.tanggal_selesai;
                                if (dateStr.includes(' ')) {
                                    dateStr = dateStr.split(' ')[0]; // Ambil hanya bagian tanggal jika ada timestamp
                                }
                                // Pastikan format tanggal sudah yyyy-mm-dd
                                if (dateStr.includes('/')) {
                                    const parts = dateStr.split('/');
                                    if (parts.length === 3) {
                                        dateStr = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
                                    }
                                }
                                document.getElementById('edit_tanggal_selesai').value = dateStr;
                            }

                            document.getElementById('edit_status').value = data.status;
                            this.kegiatanStatus = data.status;
                        }
                    }
                }" x-init="$watch('editKegiatanId', value => {
                    if (value) {
                        setTimeout(() => loadKegiatanData(), 100);
                    }
                })">
                    <form method="POST" :id="'edit-form-' + editKegiatanId" :action="`/pengelolaan/${editKegiatanId}`">
                        @csrf
                        @method('PUT')
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_nama_kegiatan">
                                Nama Kegiatan
                            </label>
                            <input type="text" name="nama_kegiatan" id="edit_nama_kegiatan" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_jenis_kegiatan">
                                Jenis Kegiatan
                            </label>
                            <select name="jenis_kegiatan" id="edit_jenis_kegiatan" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                <option value="">Pilih Jenis Kegiatan</option>
                                <option value="Penanaman">Penanaman</option>
                                <option value="Pemupukan">Pemupukan</option>
                                <option value="Pengendalian OPT">Pengendalian OPT</option>
                                <option value="Pengatur Tumbuh">Pengatur Tumbuh</option>
                                <option value="Panen">Panen</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_deskripsi_kegiatan">
                                Deskripsi Kegiatan
                            </label>
                            <textarea name="deskripsi_kegiatan" id="edit_deskripsi_kegiatan" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                rows="3"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_tanggal_mulai">
                                    Tanggal Mulai
                                </label>
                                <input type="date" name="tanggal_mulai" id="edit_tanggal_mulai" required
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            </div>
                            <div x-show="kegiatanStatus === 'Selesai'">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_tanggal_selesai">
                                    Tanggal Selesai
                                </label>
                                <input type="date" name="tanggal_selesai" id="edit_tanggal_selesai"
                                    x-bind:required="kegiatanStatus === 'Selesai'"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_status">
                                Status
                            </label>
                            <select name="status" id="edit_status" required x-model="kegiatanStatus"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                <option value="">Pilih Status</option>
                                <option value="Belum Berjalan">Belum Berjalan</option>
                                <option value="Sedang Berjalan">Sedang Berjalan</option>
                                <option value="Selesai">Selesai</option>
                            </select>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="button" class="px-4 bg-transparent p-3 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-400 mr-2" @click="showEditForm = false">Batal</button>
                            <button type="submit" class="px-4 bg-emerald-600 p-3 rounded-lg text-white hover:bg-emerald-700 shadow-sm transition-all duration-200">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
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

    // Event listener untuk filter status kegiatan
    document.addEventListener('DOMContentLoaded', function() {
        const filterStatus = document.getElementById('live-filter-status');
        if (filterStatus) {
            filterStatus.addEventListener('change', function() {
                // Alpine akan menghandle tampilan baris berdasarkan nilai filterStatus
                // karena kita sudah menambahkan kondisi di x-show

                // Memicu ulang evaluasi x-show pada tabel
                document.querySelectorAll('#tabel-kegiatan tr[id^=kegiatan-row]').forEach(row => {
                    // Trigger Alpine bisa dilakukan dengan custom event, tetapi
                    // kita bisa mengandalkan Alpine untuk mengevaluasi ulang x-show
                });
            });
        }
    });
</script>
@endsection
