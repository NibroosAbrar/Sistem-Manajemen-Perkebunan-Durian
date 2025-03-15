@extends('layouts.app')

@section('title', 'Detail Pohon #' . $tree->id)

@push('styles')
<style>
    .chart-box {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        position: relative;
        z-index: 1;
    }

    .chart-container {
        height: 250px;
        width: 100%;
        position: relative;
        z-index: 2;
    }

    .chart-wrapper {
        position: relative;
        height: 100%;
        width: 100%;
        z-index: 3;
    }

    .chart-header {
        margin-bottom: 1rem;
        font-size: 1.125rem;
        font-weight: 600;
        color: #374151;
        position: relative;
        z-index: 2;
    }

    canvas {
        position: relative;
        z-index: 3;
    }

    .badge-health {
        padding: 0.5rem 1rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-block;
        text-align: center;
        min-width: 100px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .badge-health.sehat {
        background-color: #4CAF50;
        color: white;
    }

    .badge-health.stres {
        background-color: #FFC107;
        color: black;
    }

    .badge-health.terinfeksi {
        background-color: #df721ff3;
        color: white;
    }

    .badge-health.sakit {
        background-color: #cffe43;
        color: white;
    }

    .badge-health.default {
        background-color: #6B7280;
        color: white;
    }

    .badge-health.mati {
        background-color: #ff1100;
        color: white;
    }
</style>
@endpush

@section('content')
<div id="loading-screen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
    <div class="bg-white p-4 rounded-lg shadow-lg text-center">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-green-700 mx-auto mb-2"></div>
        <p id="loadingMessage" class="text-gray-700">Loading...</p>
    </div>
</div>

<!-- Modal Pemupukan -->
<div x-data="{ showFertilizationModal: false }"
     @open-fertilization-modal.window="showFertilizationModal = true"
     x-show="showFertilizationModal"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="fertilizationForm" action="{{ route('trees.fertilization.store') }}" method="POST" class="p-6">
                @csrf
                <input type="hidden" name="tree_id" value="{{ $tree->id }}">
                <h3 class="text-lg font-bold mb-4" id="fertilizationModalTitle">Tambah Data Pemupukan</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Pemupukan</label>
                    <input type="date" name="tanggal_pemupukan" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Pupuk</label>
                    <input type="text" name="nama_pupuk" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Pupuk</label>
                    <select name="jenis_pupuk" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Pilih Jenis Pupuk</option>
                        <option value="Organik">Organik</option>
                        <option value="Anorganik">Anorganik</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bentuk Pupuk</label>
                    <input type="text" name="bentuk_pupuk" required class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Contoh: Granul, Cair, dll">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dosis Pupuk</label>
                    <div class="flex space-x-2">
                        <input type="number" name="dosis_pupuk" required class="w-2/3 px-3 py-2 border border-gray-300 rounded-md" step="0.01">
                        <select name="unit" required class="w-1/3 px-3 py-2 border border-gray-300 rounded-md">
                            <option value="kg">kg</option>
                            <option value="g">g</option>
                            <option value="ml">ml</option>
                            <option value="l">l</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" @click="showFertilizationModal = false; resetFertilizationForm()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md">Batal</button>
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Pestisida -->
<div x-data="{ showPesticideModal: false }"
     @open-pesticide-modal.window="showPesticideModal = true"
     x-show="showPesticideModal"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="pesticideForm" action="{{ route('trees.pesticide.store') }}" method="POST" class="p-6" onsubmit="event.preventDefault(); submitPesticideForm(this);">
                @csrf
                <input type="hidden" name="tree_id" value="{{ $tree->id }}">
                <h3 class="text-lg font-bold mb-4">Tambah Data Pestisida</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Pestisida <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal_pestisida" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Pestisida <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_pestisida" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Pestisida <span class="text-red-500">*</span></label>
                    <select name="jenis_pestisida" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Pilih Jenis Pestisida</option>
                        <option value="Insektisida">Insektisida</option>
                        <option value="Fungisida">Fungisida</option>
                        <option value="Herbisida">Herbisida</option>
                        <option value="Bakterisida">Bakterisida</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dosis <span class="text-red-500">*</span></label>
                    <div class="flex space-x-2">
                        <input type="number" name="dosis" required class="w-2/3 px-3 py-2 border border-gray-300 rounded-md" step="0.01">
                        <select name="unit" required class="w-1/3 px-3 py-2 border border-gray-300 rounded-md">
                            <option value="ml">ml</option>
                            <option value="l">l</option>
                            <option value="g">g</option>
                            <option value="kg">kg</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" @click="showPesticideModal = false; resetPesticideForm()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md">Batal</button>
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Panen -->
<div x-data="{ showHarvestModal: false }"
     @open-harvest-modal.window="showHarvestModal = true"
     x-show="showHarvestModal"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="{{ route('trees.harvest.store') }}" method="POST" class="p-6" id="harvestForm" onsubmit="event.preventDefault(); submitHarvestForm(this);">
                @csrf
                <input type="hidden" name="tree_id" value="{{ $tree->id }}">
                <h3 class="text-lg font-bold mb-4">Tambah Data Panen</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Panen <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal_panen" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah Buah <span class="text-red-500">*</span></label>
                    <input type="number" name="fruit_count" id="fruit_count" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Total Berat (kg) <span class="text-red-500">*</span></label>
                    <input type="number" name="total_weight" id="total_weight" required class="w-full px-3 py-2 border border-gray-300 rounded-md" step="0.01" placeholder="Masukkan berat dalam kg">
                    <input type="hidden" name="unit" value="kg">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rata-rata Berat per Buah</label>
                    <input type="number" name="average_weight_per_fruit" id="average_weight_per_fruit" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" step="0.01">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kondisi Buah <span class="text-red-500">*</span></label>
                    <select name="fruit_condition" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Pilih Kondisi</option>
                        <option value="Baik">Baik</option>
                        <option value="Cukup">Cukup</option>
                        <option value="Kurang">Kurang</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" @click="showHarvestModal = false; resetHarvestForm()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md">Batal</button>
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Riwayat Kesehatan -->
<div x-data="{ showHealthModal: false }"
     @open-health-modal.window="showHealthModal = true"
     x-show="showHealthModal"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="healthProfileForm" action="{{ route('tree-health-profiles.store') }}" method="POST" class="p-6" enctype="multipart/form-data" onsubmit="event.preventDefault(); submitHealthProfileForm(this);">
                @csrf
                <input type="hidden" name="tree_id" value="{{ $tree->id }}">
                <h3 class="text-lg font-bold mb-4" id="healthProfileModalTitle">Tambah Riwayat Kesehatan</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Pemeriksaan <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal_pemeriksaan" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status Kesehatan <span class="text-red-500">*</span></label>
                    <select name="status_kesehatan" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Pilih Status</option>
                        <option value="Sehat">Sehat</option>
                        <option value="Stres">Stres</option>
                        <option value="Terinfeksi">Terinfeksi</option>
                        <option value="Mati">Mati</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gejala</label>
                    <textarea name="gejala" class="w-full px-3 py-2 border border-gray-300 rounded-md" rows="2" placeholder="Deskripsikan gejala yang terlihat"></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Diagnosis</label>
                    <textarea name="diagnosis" class="w-full px-3 py-2 border border-gray-300 rounded-md" rows="2" placeholder="Diagnosis masalah kesehatan"></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tindakan Penanganan</label>
                    <textarea name="tindakan_penanganan" class="w-full px-3 py-2 border border-gray-300 rounded-md" rows="2" placeholder="Tindakan yang dilakukan"></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Catatan Tambahan</label>
                    <textarea name="catatan_tambahan" class="w-full px-3 py-2 border border-gray-300 rounded-md" rows="2" placeholder="Catatan tambahan"></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Foto Kondisi</label>
                    <input type="file" name="foto_kondisi" class="w-full px-3 py-2 border border-gray-300 rounded-md" accept="image/*">
                    <div id="current-photo" class="mt-2 hidden">
                        <p class="text-sm text-gray-600">Foto saat ini:</p>
                        <img id="current-photo-img" src="" alt="Foto kondisi" class="mt-1 max-h-32">
                    </div>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" @click="showHealthModal = false; resetHealthProfileForm()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md">Batal</button>
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="w-full flex flex-col h-screen overflow-y-hidden">
    <!-- Header -->
    <header class="bg-header text-black flex items-center justify-center py-4 px-6 relative">
        <h1 class="text-xl font-semibold">Detail Pohon #{{ $tree->id }}</h1>
        <div class="absolute right-6">
            <a href="{{ route('webgis') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded mr-2">
                <i class="fas fa-arrow-left mr-1"></i> Kembali
            </a>
            <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                <i class="fas fa-print mr-1"></i> Cetak
            </button>
        </div>
    </header>

    <!-- Main Content -->
    <div class="w-full overflow-x-hidden border-t flex flex-col">
        <main class="w-full flex-grow p-6">
            <!-- Timestamp Card -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-4">
                <h3 class="text-lg font-semibold text-gray-700">Informasi Pohon</h3>
                <p class="text-xl font-bold text-gray-800">{{ $tree->varietas ?? 'Tidak Ada' }} - Ditanam Tahun {{ $tree->tahun_tanam ?? '-' }}</p>
            </div>

            <!-- Scoreboard Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <!-- Varietas Card -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700">Varietas</h3>
                            <p class="text-3xl font-bold text-gray-800">{{ $tree->varietas ?? 'Tidak Ada' }}</p>
                        </div>
                        <div class="text-4xl text-green-500">
                            <i class="fas fa-seedling"></i>
                        </div>
                    </div>
                </div>

                <!-- Umur Card -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700">Umur</h3>
                            <p class="text-3xl font-bold text-gray-800">{{ $tree->tahun_tanam ? now()->year - $tree->tahun_tanam : '0' }} tahun</p>
                        </div>
                        <div class="text-4xl text-blue-500">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>

                <!-- Status Kesehatan Card -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div class="w-full">
                            <h3 class="text-lg font-semibold text-gray-700 mb-2">Status Kesehatan</h3>
                            <div class="flex justify-center">
                                <span class="badge-health
                                    @if($tree->health_status == 'Sehat') sehat
                                    @elseif($tree->health_status == 'Stres') stres
                                    @elseif($tree->health_status == 'Terinfeksi') terinfeksi
                                    @elseif($tree->health_status == 'Sakit') sakit
                                    @elseif($tree->health_status == 'Mati') mati
                                    @else default
                                    @endif">
                                    {{ $tree->health_status ?? 'Tidak Ada' }}
                                </span>
                            </div>
                        </div>
                        <div class="text-4xl text-yellow-500">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                    </div>
                </div>

                <!-- Produksi Card -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700">Produksi</h3>
                            <p class="text-3xl font-bold text-gray-800">{{ number_format($totalHarvest, 2) }} kg</p>
                        </div>
                        <div class="text-4xl text-red-500">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Fertilization Chart -->
                <div class="chart-box">
                    <h3 class="chart-header">Riwayat Pemupukan</h3>
                    <div class="chart-container">
                        <div class="chart-wrapper">
                            <canvas id="fertilizationChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Pesticide Chart -->
                <div class="chart-box">
                    <h3 class="chart-header">Riwayat Pestisida</h3>
                    <div class="chart-container">
                        <div class="chart-wrapper">
                            <canvas id="pesticideChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Harvest Chart -->
                <div class="chart-box">
                    <h3 class="chart-header">Hasil Panen</h3>
                    <div class="chart-container">
                        <div class="chart-wrapper">
                            <canvas id="harvestChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Tree Info Card -->
                <div class="chart-box">
                    <h3 class="chart-header">Informasi Detail</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center pb-2 border-b">
                            <span class="text-gray-600">Varietas</span>
                            <span class="font-semibold">{{ $tree->varietas ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b">
                            <span class="text-gray-600">Tahun Tanam</span>
                            <span class="font-semibold">{{ $tree->tahun_tanam ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b">
                            <span class="text-gray-600">Umur</span>
                            <span class="font-semibold">{{ $tree->tahun_tanam ? now()->year - $tree->tahun_tanam : '-' }} tahun</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b">
                            <span class="text-gray-600">Status Kesehatan</span>
                            <span class="font-semibold">{{ $tree->health_status ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b">
                            <span class="text-gray-600">Total Produksi</span>
                            <span class="font-semibold">{{ number_format($totalHarvest, 2) }} kg</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Sumber Bibit</span>
                            <span class="font-semibold">{{ $tree->sumber_bibit ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigasi Tab -->
            <div class="mb-6" x-data="{ activeTab: 'fertilization' }">
                <div class="flex border-b border-gray-200">
                    <button @click="activeTab = 'fertilization'"
                        :class="{ 'border-b-2 border-green-600 font-bold text-green-600': activeTab === 'fertilization' }"
                        class="py-2 px-4 text-sm font-semibold text-gray-600 hover:text-gray-800 focus:outline-none">
                        <i class="fas fa-seedling mr-1"></i> Riwayat Pemupukan
                    </button>
                    <button @click="activeTab = 'pesticide'"
                        :class="{ 'border-b-2 border-green-600 font-bold text-green-600': activeTab === 'pesticide' }"
                        class="py-2 px-4 text-sm font-semibold text-gray-600 hover:text-gray-800 focus:outline-none">
                        <i class="fas fa-spray-can mr-1"></i> Riwayat Pestisida
                    </button>
                    <button @click="activeTab = 'harvest'"
                        :class="{ 'border-b-2 border-green-600 font-bold text-green-600': activeTab === 'harvest' }"
                        class="py-2 px-4 text-sm font-semibold text-gray-600 hover:text-gray-800 focus:outline-none">
                        <i class="fas fa-leaf mr-1"></i> Riwayat Panen
                    </button>
                    <button @click="activeTab = 'health'; loadHealthProfiles();"
                        :class="{ 'border-b-2 border-green-600 font-bold text-green-600': activeTab === 'health' }"
                        class="py-2 px-4 text-sm font-semibold text-gray-600 hover:text-gray-800 focus:outline-none">
                        <i class="fas fa-heartbeat mr-1"></i> Riwayat Kesehatan
                    </button>
                </div>

                <!-- Tab Pemupukan -->
                <div x-show="activeTab === 'fertilization'" class="mt-6">
                    <div class="bg-white p-6 shadow-md rounded-lg">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold">Riwayat Pemupukan</h2>
                            <button @click="$dispatch('open-fertilization-modal')" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg">
                                <i class="fas fa-plus mr-2"></i>Tambah Data
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table id="fertilization-table" class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Tanggal</th>
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Nama Pupuk</th>
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Jenis Pupuk</th>
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Bentuk Pupuk</th>
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Dosis</th>
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($fertilizations as $fertilization)
                                    <tr class="border-t hover:bg-gray-50">
                                        <td class="p-4 border border-gray-300 text-center">{{ \Carbon\Carbon::parse($fertilization->tanggal_pemupukan)->format('d/m/Y') }}</td>
                                        <td class="p-4 border border-gray-300 text-center">{{ $fertilization->nama_pupuk }}</td>
                                        <td class="p-4 border border-gray-300 text-center">{{ $fertilization->jenis_pupuk }}</td>
                                        <td class="p-4 border border-gray-300 text-center">{{ $fertilization->bentuk_pupuk }}</td>
                                        <td class="p-4 border border-gray-300 text-center">{{ $fertilization->dosis_pupuk }} {{ $fertilization->unit }}</td>
                                        <td class="p-4 border border-gray-300 text-center">
                                            <button onclick="editFertilization({{ $fertilization->id }})"
                                                    class="bg-yellow-500 text-white px-3 py-1 rounded mr-1">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteFertilization({{ $fertilization->id }})"
                                                    class="bg-red-500 text-white px-3 py-1 rounded">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="p-4 border border-gray-300 text-center">
                                            <div class="text-gray-500">
                                                <i class="fas fa-info-circle mr-1"></i>Belum ada data pemupukan
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab Pestisida -->
                <div x-show="activeTab === 'pesticide'" class="mt-6">
                    <div class="bg-white p-6 shadow-md rounded-lg">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold">Riwayat Pestisida</h2>
                            <button @click="$dispatch('open-pesticide-modal')" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg">
                                <i class="fas fa-plus mr-2"></i>Tambah Data
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table id="pesticide-table" class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Tanggal</th>
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Nama Pestisida</th>
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Jenis Pestisida</th>
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Dosis</th>
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pesticides as $pesticide)
                                    <tr class="border-t hover:bg-gray-50">
                                        <td class="p-4 border border-gray-300 text-center">{{ \Carbon\Carbon::parse($pesticide->tanggal_pestisida)->format('d/m/Y') }}</td>
                                        <td class="p-4 border border-gray-300 text-center">{{ $pesticide->nama_pestisida }}</td>
                                        <td class="p-4 border border-gray-300 text-center">{{ $pesticide->jenis_pestisida }}</td>
                                        <td class="p-4 border border-gray-300 text-center">{{ $pesticide->dosis }} {{ $pesticide->unit }}</td>
                                        <td class="p-4 border border-gray-300 text-center">
                                            <button onclick="editPesticide({{ $pesticide->id }})"
                                                    class="bg-yellow-500 text-white px-3 py-1 rounded mr-1">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deletePesticide({{ $pesticide->id }})"
                                                    class="bg-red-500 text-white px-3 py-1 rounded">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="p-4 border border-gray-300 text-center">
                                            <div class="text-gray-500">
                                                <i class="fas fa-info-circle mr-1"></i>Belum ada data pestisida
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab Panen -->
                <div x-show="activeTab === 'harvest'" class="mt-6">
                    <div class="bg-white p-6 shadow-md rounded-lg">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold">Riwayat Panen</h2>
                            <button @click="$dispatch('open-harvest-modal')" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg">
                                <i class="fas fa-plus mr-2"></i>Tambah Data
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table id="harvest-table" class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Tanggal</th>
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Total Berat</th>
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Jumlah Buah</th>
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Rata-rata/Buah</th>
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Kualitas</th>
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($harvests as $harvest)
                                    <tr class="border-t hover:bg-gray-50">
                                        <td class="p-4 border border-gray-300 text-center">{{ \Carbon\Carbon::parse($harvest->tanggal_panen)->format('d/m/Y') }}</td>
                                        <td class="p-4 border border-gray-300 text-center">{{ $harvest->total_weight }} {{ $harvest->unit }}</td>
                                        <td class="p-4 border border-gray-300 text-center">{{ $harvest->fruit_count }}</td>
                                        <td class="p-4 border border-gray-300 text-center">{{ number_format($harvest->average_weight_per_fruit, 2) }} {{ $harvest->unit }}</td>
                                        <td class="p-4 border border-gray-300 text-center">{{ $harvest->fruit_condition }}</td>
                                        <td class="p-4 border border-gray-300 text-center">
                                            <button onclick="editHarvest({{ $harvest->id }})"
                                                    class="bg-yellow-500 text-white px-3 py-1 rounded mr-1">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteHarvest({{ $harvest->id }})"
                                                    class="bg-red-500 text-white px-3 py-1 rounded">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="p-4 border border-gray-300 text-center">
                                            <div class="text-gray-500">
                                                <i class="fas fa-info-circle mr-1"></i>Belum ada data panen
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Tab Riwayat Kesehatan -->
                <div x-show="activeTab === 'health'" class="mt-6">
                    <div class="bg-white p-6 shadow-md rounded-lg">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold">Riwayat Kesehatan</h2>
                            <button @click="$dispatch('open-health-modal')" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg">
                                <i class="fas fa-plus mr-2"></i>Tambah Data
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table id="health-table" class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Tanggal Pemeriksaan</th>
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Status Kesehatan</th>
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Gejala</th>
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Diagnosis</th>
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Tindakan</th>
                                        <th class="p-4 text-center border border-gray-300 font-semibold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="health-profiles-container">
                                    <tr>
                                        <td colspan="6" class="p-4 border border-gray-300 text-center">
                                            <div class="text-gray-500">
                                                <i class="fas fa-info-circle mr-1"></i>Belum ada data riwayat kesehatan
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('assets/js/tree-dashboard.js') }}"></script>
<script>
    // Inisialisasi grafik pemupukan
    const fertilizationCtx = document.getElementById('fertilizationChart');
    if (fertilizationCtx) {
        const fertilizationData = {!! json_encode($fertilizations->map(function($item) {
            return [
                'tanggal' => Carbon\Carbon::parse($item->tanggal_pemupukan)->format('d/m/Y'),
                'tanggal_sort' => $item->tanggal_pemupukan,
                'dosis' => $item->dosis_pupuk,
                'jenis' => $item->jenis_pupuk
            ];
        })->sortBy('tanggal_sort')->values()) !!};

        new Chart(fertilizationCtx, {
            type: 'bar',
            data: {
                labels: fertilizationData.map(item => item.tanggal),
                datasets: [{
                    label: 'Dosis Pupuk (kg)',
                    data: fertilizationData.map(item => item.dosis),
                    backgroundColor: fertilizationData.map(item => {
                        switch(item.jenis.toLowerCase()) {
                            case 'organik': return 'rgba(75, 192, 192, 0.5)';
                            case 'anorganik': return 'rgba(255, 99, 132, 0.5)';
                            default: return 'rgba(201, 203, 207, 0.5)';
                        }
                    }),
                    borderColor: fertilizationData.map(item => {
                        switch(item.jenis.toLowerCase()) {
                            case 'organik': return 'rgb(75, 192, 192)';
                            case 'anorganik': return 'rgb(255, 99, 132)';
                            default: return 'rgb(201, 203, 207)';
                        }
                    }),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Dosis (kg)'
                        }
                    }
                }
            }
        });
    }

    // Inisialisasi grafik pestisida
    const pesticideCtx = document.getElementById('pesticideChart');
    if (pesticideCtx) {
        const pesticideData = {!! json_encode($pesticides->map(function($item) {
            return [
                'tanggal' => Carbon\Carbon::parse($item->tanggal_pestisida)->format('d/m/Y'),
                'tanggal_sort' => $item->tanggal_pestisida,
                'dosis' => $item->dosis,
                'jenis' => $item->jenis_pestisida
            ];
        })->sortBy('tanggal_sort')->values()) !!};

        new Chart(pesticideCtx, {
            type: 'bar',
            data: {
                labels: pesticideData.map(item => item.tanggal),
                datasets: [{
                    label: 'Dosis Pestisida (ml)',
                    data: pesticideData.map(item => item.dosis),
                    backgroundColor: pesticideData.map(item => {
                        switch(item.jenis.toLowerCase()) {
                            case 'insektisida': return 'rgba(255, 99, 132, 0.5)';
                            case 'fungisida': return 'rgba(75, 192, 192, 0.5)';
                            case 'herbisida': return 'rgba(255, 205, 86, 0.5)';
                            default: return 'rgba(201, 203, 207, 0.5)';
                        }
                    }),
                    borderColor: pesticideData.map(item => {
                        switch(item.jenis.toLowerCase()) {
                            case 'insektisida': return 'rgb(255, 99, 132)';
                            case 'fungisida': return 'rgb(75, 192, 192)';
                            case 'herbisida': return 'rgb(255, 205, 86)';
                            default: return 'rgb(201, 203, 207)';
                        }
                    }),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Dosis (ml)'
                        }
                    }
                }
            }
        });
    }

    // Inisialisasi grafik panen
    const harvestCtx = document.getElementById('harvestChart');
    if (harvestCtx) {
        const harvestData = {!! json_encode($harvests->map(function($item) {
            return [
                'tanggal' => Carbon\Carbon::parse($item->tanggal_panen)->format('d/m/Y'),
                'tanggal_sort' => $item->tanggal_panen,
                'berat' => $item->total_weight,
                'jumlah' => $item->fruit_count
            ];
        })->sortBy('tanggal_sort')->values()) !!};

        new Chart(harvestCtx, {
            type: 'line',
            data: {
                labels: harvestData.map(item => item.tanggal),
                datasets: [
                    {
                        label: 'Total Berat (kg)',
                        data: harvestData.map(item => item.berat),
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Jumlah Buah',
                        data: harvestData.map(item => item.jumlah),
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Total Berat (kg)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Jumlah Buah'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }

    // Load health profiles saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        loadHealthProfiles();
    });
</script>
@endpush
