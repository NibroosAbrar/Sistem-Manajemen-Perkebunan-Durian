@extends('layouts.app')

@section('title', 'Detail Pohon #' . $tree->id)

@section('header-title', '')

@push('styles')
<style>
    /* Menyembunyikan header utama aplikasi */
    header.bg-header {
        display: none !important;
    }

    /* Menyesuaikan margin top content karena header utama disembunyikan */
    main.pt-16 {
        padding-top: 0 !important;
    }

    /* Styling untuk chart boxes */
    .chart-box {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        margin-bottom: 20px;
        position: relative;
        z-index: 1;
        transition: all 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .chart-box::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: linear-gradient(to right, #0ea5e9, #10B981);
        opacity: 0.8;
    }

    .chart-box-fertilization::before {
        background: linear-gradient(to right, #10B981, #34D399);
    }

    .chart-box-pesticide::before {
        background: linear-gradient(to right, #F59E0B, #FBBF24);
    }

    .chart-box-harvest::before {
        background: linear-gradient(to right, #3B82F6, #60A5FA);
    }

    .chart-box-health::before {
        background: linear-gradient(to right, #EC4899, #F472B6);
    }

    .chart-box-info::before {
        background: linear-gradient(to right, #8B5CF6, #A78BFA);
    }

    .chart-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
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
        font-size: 1.2rem;
        font-weight: 600;
        color: #374151;
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
    }

    .chart-header::before {
        content: '';
        display: inline-block;
        width: 4px;
        height: 18px;
        background: linear-gradient(to bottom, #0ea5e9, #10B981);
        margin-right: 8px;
        border-radius: 4px;
    }

    canvas {
        position: relative;
        z-index: 3;
    }

    /* Styling untuk badges */
    .badge-health {
        padding: 0.6rem 1.2rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-block;
        text-align: center;
        min-width: 120px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .badge-health:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .badge-health.sehat {
        background: linear-gradient(135deg, #4CAF50, #2E7D32);
        color: white;
    }

    .badge-health.stres {
        background: linear-gradient(135deg, #FFC107, #FF8F00);
        color: #333;
    }

    .badge-health.Sakit {
        background: linear-gradient(135deg, #FF9800, #F57C00);
        color: white;
    }

    .badge-health.sakit {
        background: linear-gradient(135deg, #FF9800, #F57C00);
        color: white;
    }

    .badge-health.default {
        background: linear-gradient(135deg, #9E9E9E, #616161);
        color: white;
    }

    .badge-health.mati {
        background: linear-gradient(135deg, #F44336, #D32F2F);
        color: white;
    }

    /* Container Dashboard */
    .tree-dashboard-container {
        height: 100vh;
        overflow-y: auto;
        padding-bottom: 100px;
        background-color: #f5f7fa;
        background-image: linear-gradient(to bottom, rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.4)),
            url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%2310b981' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }

    /* Header Dashboard */
    .tree-dashboard-header {
        position: relative;
        background: linear-gradient(to right, #ffffff, #f8fafc);
        z-index: 40;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.07);
        padding: 1.5rem 2.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e5e7eb;
    }

    .tree-dashboard-header h1 {
        font-size: 1.6rem;
        font-weight: 700;
        color: #1f2937;
        display: flex;
        align-items: center;
    }

    .tree-dashboard-header h1:before {
        content: '';
        display: inline-block;
        width: 6px;
        height: 30px;
        background: linear-gradient(to bottom, #0ea5e9, #10B981);
        margin-right: 14px;
        border-radius: 3px;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 0.75rem;
    }

    .action-buttons a,
    .action-buttons button {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.6rem 1.2rem;
        font-size: 0.9rem;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .action-buttons a:hover,
    .action-buttons button:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    }

    .action-buttons .btn-back {
        background: linear-gradient(135deg, #6B7280, #4B5563);
        color: white;
        border: none;
    }

    .action-buttons .btn-back:hover {
        background: linear-gradient(135deg, #4B5563, #374151);
    }

    .action-buttons .btn-print {
        background: linear-gradient(135deg, #3B82F6, #2563EB);
        color: white;
        border: none;
    }

    .action-buttons .btn-print:hover {
        background: linear-gradient(135deg, #2563EB, #1D4ED8);
    }

    .action-buttons .btn-excel {
        background: linear-gradient(135deg, #10B981, #059669);
        color: white;
        border: none;
    }

    .action-buttons .btn-excel:hover {
        background: linear-gradient(135deg, #059669, #047857);
    }

    /* Info Cards Styling */
    .info-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        padding: 1.5rem;
        transition: all 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.05);
        overflow: hidden;
        position: relative;
    }

    .info-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    }

    .info-card::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        height: 100%;
        width: 5px;
        background: linear-gradient(to bottom, #0ea5e9, #10B981);
        opacity: 0.7;
    }

    .info-card-varietas {
        background: linear-gradient(to bottom right, white, #f0fff4);
    }

    .info-card-varietas::after {
        background: linear-gradient(to bottom, #10B981, #34D399);
    }

    .info-card-umur {
        background: linear-gradient(to bottom right, white, #eff6ff);
    }

    .info-card-umur::after {
        background: linear-gradient(to bottom, #3B82F6, #60A5FA);
    }

    .info-card-kesehatan {
        background: linear-gradient(to bottom right, white, #fff1f2);
    }

    .info-card-kesehatan::after {
        background: linear-gradient(to bottom, #EC4899, #F472B6);
    }

    .info-card-produksi {
        background: linear-gradient(to bottom right, white, #fffbeb);
    }

    .info-card-produksi::after {
        background: linear-gradient(to bottom, #F59E0B, #FBBF24);
    }

    .info-card .icon {
        font-size: 2.5rem;
        background: linear-gradient(135deg, #0ea5e9, #10B981);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 0.5rem;
    }

    .info-card-varietas .icon {
        background: linear-gradient(135deg, #10B981, #34D399);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .info-card-umur .icon {
        background: linear-gradient(135deg, #3B82F6, #60A5FA);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .info-card-kesehatan .icon {
        background: linear-gradient(135deg, #EC4899, #F472B6);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .info-card-produksi .icon {
        background: linear-gradient(135deg, #F59E0B, #FBBF24);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .info-card h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #6B7280;
        margin-bottom: 0.5rem;
    }

    .info-card p {
        font-size: 1.75rem;
        font-weight: 700;
        color: #111827;
    }

    /* Table Design */
    .data-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }

    .data-table thead {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .data-table thead tr {
        background: linear-gradient(to right, #f9fafb, #f3f4f6);
    }

    .data-table th {
        padding: 1rem;
        font-weight: 600;
        color: #4B5563;
        border-bottom: 2px solid #e5e7eb;
        text-align: center !important;
    }

    .data-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e5e7eb;
        color: #1F2937;
    }

    .data-table tbody tr {
        transition: all 0.2s ease;
    }

    .data-table tbody tr:hover {
        background-color: #f9fafb;
    }

    .data-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Tab Navigation */
    .tab-navigation {
        display: flex;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 1.5rem;
        background: white;
        border-radius: 8px 8px 0 0;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        position: sticky;
        top: 0;
        z-index: 30;
    }

    .tab-button {
        padding: 1rem 1.5rem;
        font-weight: 600;
        color: #6B7280;
        background: transparent;
        border: none;
        transition: all 0.3s ease;
        position: relative;
        cursor: pointer;
        display: flex;
        align-items: center;
    }

    .tab-button i {
        margin-right: 12px;
        font-size: 1.1rem;
        transition: all 0.3s ease;
    }

    .tab-button:hover {
        color: #111827;
        background: #f9fafb;
    }

    .tab-button:hover i {
        transform: translateY(-2px);
    }

    .tab-button.active {
        color: #10B981;
        background: white;
    }

    .tab-button.active i {
        color: #10B981;
    }

    .tab-button.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(to right, #0ea5e9, #10B981);
        border-radius: 3px 3px 0 0;
    }

    .tab-content {
        background: white;
        border-radius: 0 0 8px 8px;
        padding: 1.5rem;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }

    /* Buttons */
    .btn-primary {
        background: linear-gradient(135deg, #10B981, #059669);
        color: white;
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        border: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        box-shadow: 0 2px 5px rgba(16, 185, 129, 0.2);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
        background: linear-gradient(135deg, #059669, #047857);
    }

    .btn-primary i {
        margin-right: 0.5rem;
    }

    @media (max-width: 639px) { /* Tailwind 'sm' breakpoint */
        .btn-primary {
            padding: 0.5rem 1rem !important;
            font-size: 0.875rem !important;
        }
        .btn-primary i {
            margin-right: 0.25rem !important;
        }
    }

    /* Form Controls */
    .form-control {
        border-radius: 8px;
        border: 1px solid #d1d5db;
        padding: 0.75rem 1rem;
        width: 100%;
        transition: all 0.3s ease;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .form-control:focus {
        border-color: #10B981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        outline: none;
    }

    /* Loading Animation */
    .loading-animation {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }

    /* Print Styles */
    @media print {
        .tree-dashboard-container {
            height: auto;
            overflow: visible;
            padding-bottom: 0;
            background: none;
        }

        .tree-dashboard-header {
            position: static;
            box-shadow: none;
            border-bottom: 1px solid #000;
            margin-bottom: 1rem;
            background: none;
        }

        .action-buttons,
        .btn-primary,
        button[type="button"] {
            display: none;
        }

        .chart-box,
        .info-card {
            break-inside: avoid;
            box-shadow: none;
            border: 1px solid #ccc;
        }

        .tab-navigation {
            display: none;
        }

        .tab-content {
            display: block !important;
            box-shadow: none;
            border: 1px solid #ccc;
        }
    }
</style>
@endpush

@section('content')
<div id="loading-screen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 backdrop-blur-sm" style="display: none;">
    <div class="bg-white p-6 rounded-lg shadow-xl text-center max-w-sm">
        <div class="flex justify-center mb-4">
            <div class="w-16 h-16 rounded-full border-4 border-gray-200 border-t-green-500 animate-spin"></div>
        </div>
        <p id="loadingMessage" class="text-gray-700 text-lg font-medium">Loading...</p>
        <p class="text-gray-500 text-sm mt-2">Mohon tunggu sebentar</p>
    </div>
</div>

<script>
    // Tambahkan user role ke elemen body
    document.addEventListener('DOMContentLoaded', function() {
        const role_id = "{{ Auth::check() ? Auth::user()->role_id : '4' }}";
        document.body.setAttribute('data-user-role', role_id);
    });
</script>

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
                    <div class="flex flex-col gap-2 sm:flex-row sm:space-x-2 sm:gap-0">
                        <input type="number" name="dosis_pupuk" required class="w-full sm:w-2/3 px-3 py-2 border border-gray-300 rounded-md" step="0.01">
                        <select name="unit" required class="w-full sm:w-1/3 px-3 py-2 border border-gray-300 rounded-md">
                            <option value="g/tanaman">g/tanaman</option>
                            <option value="ml/tanaman">ml/tanaman</option>
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bentuk Pestisida</label>
                    <input type="text" name="bentuk_pestisida" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Contoh: Cair, Bubuk, Granul, dll">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dosis <span class="text-red-500">*</span></label>
                    <div class="flex flex-col gap-2 sm:flex-row sm:space-x-2 sm:gap-0">
                        <input type="number" name="dosis" required class="w-full sm:w-2/3 px-3 py-2 border border-gray-300 rounded-md" step="0.01">
                        <select name="unit" required class="w-full sm:w-1/3 px-3 py-2 border border-gray-300 rounded-md">
                            <option value="ml/tanaman">ml/tanaman</option>
                            <option value="g/tanaman">g/tanaman</option>
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kondisi Buah (%) <span class="text-red-500">*</span></label>
                    <input type="number" name="fruit_condition" min="0" max="100" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Masukkan persentase kondisi buah (0-100%)">
                    <p class="text-sm text-gray-500 mt-1">Masukkan nilai 0-100 untuk menunjukkan persentase kondisi buah</p>
                    <p class="text-sm font-medium mt-2" id="fruit-condition-help">
                        0-20%: Sangat Tidak Baik<br>
                        21-40%: Tidak Baik<br>
                        41-60%: Cukup<br>
                        61-80%: Baik<br>
                        81-100%: Sangat Baik
                    </p>
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
     @close-health-modal.window="showHealthModal = false"
     x-show="showHealthModal"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="healthProfileForm" action="{{ route('tree-health-profiles.store') }}" method="POST" class="p-6" enctype="multipart/form-data" onsubmit="event.preventDefault(); submitHealthProfileForm();">
                @csrf
                <input type="hidden" name="tree_id" value="{{ $tree->id }}">
                <input type="hidden" id="health_id" name="health_id" value="">
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
                        <option value="Sakit">Sakit</option>
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

<!-- Modal ZPT -->
<div x-data="{ showZptModal: false }"
     @open-zpt-modal.window="showZptModal = true"
     @close-zpt-modal.window="showZptModal = false"
     x-show="showZptModal"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="zptForm" action="#" method="POST" class="p-6" onsubmit="event.preventDefault(); submitZptForm();">
                @csrf
                <input type="hidden" name="tree_id" value="{{ $tree->id }}">
                <input type="hidden" id="zpt_id" name="zpt_id" value="">
                <h3 class="text-lg font-bold mb-4" id="zptModalTitle">Tambah Data ZPT</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Aplikasi <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal_aplikasi" id="tanggal_aplikasi" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama ZPT <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_zpt" id="nama_zpt" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Merek <span class="text-red-500">*</span></label>
                    <input type="text" name="merek" id="merek" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Senyawa <span class="text-red-500">*</span></label>
                    <select name="jenis_senyawa" id="jenis_senyawa" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Pilih Jenis Senyawa</option>
                        <option value="Alami">Alami</option>
                        <option value="Sintetis">Sintetis</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Konsentrasi <span class="text-red-500">*</span></label>
                    <input type="text" name="konsentrasi" id="konsentrasi" required class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Contoh: 100 ppm">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Volume Larutan <span class="text-red-500">*</span></label>
                    <div class="flex flex-col gap-2 sm:flex-row sm:space-x-2 sm:gap-0">
                        <input type="number" name="volume_larutan" id="volume_larutan" required class="w-full sm:w-2/3 px-3 py-2 border border-gray-300 rounded-md" step="0.01">
                        <select name="unit" id="unit" required class="w-full sm:w-1/3 px-3 py-2 border border-gray-300 rounded-md">
                            <option value="ml">ml</option>
                            <option value="l">l</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fase Pertumbuhan <span class="text-red-500">*</span></label>
                    <input type="text" name="fase_pertumbuhan" id="fase_pertumbuhan" required class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Contoh: Vegetatif, Generatif">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Metode Aplikasi <span class="text-red-500">*</span></label>
                    <input type="text" name="metode_aplikasi" id="metode_aplikasi" required class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Contoh: Semprot, Siram">
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" @click="showZptModal = false; resetZptForm()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md">Batal</button>
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Container scrollable untuk dashboard pohon -->
<div class="tree-dashboard-container">
    <!-- Header dengan tombol navigasi -->
    <div class="tree-dashboard-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 sm:gap-0">
        <h1 class="text-xl font-semibold text-center sm:text-left">Detail Pohon #{{ $tree->id }}</h1>
        <div class="action-buttons">
            <a href="{{ url('/webgis') }}" class="btn-back">
                <i class="fas fa-arrow-left mr-2"></i> Kembali ke Peta
            </a>
            <a href="{{ route('export.tree-history', ['id' => $tree->id]) }}" class="btn-print">
                <i class="fas fa-file-excel mr-2"></i> Ekspor Data
            </a>
        </div>
    </div>

    <!-- Main content dengan padding yang cukup untuk scrolling -->
    <div class="p-6">
            <!-- Timestamp Card -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6 border-l-4 border-green-500 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 -mt-10 -mr-10 rounded-full bg-gradient-to-br from-green-300 to-green-50 opacity-20"></div>
                <h3 class="text-xl font-bold text-gray-700 mb-1 relative z-10">Informasi Pohon</h3>
                <p class="text-2xl font-bold text-gray-800 relative z-10 flex items-center">
                    <i class="fas fa-tree text-green-500 mr-2"></i>
                    <span>{{ $tree->varietas ?? 'Tidak Ada' }} - Ditanam Tahun {{ $tree->tahun_tanam ?? '-' }}</span>
                </p>
            </div>

            <!-- Scoreboard Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Varietas Card -->
                <div class="info-card info-card-varietas">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3>Varietas</h3>
                            <p>{{ $tree->varietas ?? 'Tidak Ada' }}</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-seedling"></i>
                        </div>
                    </div>
                </div>

                <!-- Umur Card -->
                <div class="info-card info-card-umur">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3>Umur</h3>
                            <p>{{ $tree->tahun_tanam ? now()->year - $tree->tahun_tanam : '0' }} tahun</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>

                <!-- Status Kesehatan Card -->
                <div class="info-card info-card-kesehatan">
                    <div class="flex items-center justify-between">
                        <div class="w-full">
                            <h3 class="mb-3">Status Kesehatan</h3>
                            <div class="flex justify-center">
                                <span class="badge-health
                                    @if($tree->health_status == 'Sehat') sehat
                                    @elseif($tree->health_status == 'Stres') stres
                                    @elseif($tree->health_status == 'Sakit') Sakit
                                    @elseif($tree->health_status == 'Sakit') sakit
                                    @elseif($tree->health_status == 'Mati') mati
                                    @else default
                                    @endif">
                                    <i class="fas fa-heartbeat mr-1"></i> {{ $tree->health_status ?? 'Tidak Ada' }}
                                </span>
                            </div>
                        </div>
                        <div class="icon">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                    </div>
                </div>

                <!-- Produksi Card -->
                <div class="info-card info-card-produksi">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3>Total Produksi</h3>
                            <p>{{ number_format($totalHarvest, 2) }} kg</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <!-- Fertilization Chart -->
                <div class="chart-box chart-box-fertilization">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-2 sm:gap-0">
                            <h3 class="chart-header w-full sm:w-auto">Riwayat Pemupukan</h3>
                        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                            <select id="fertilizationUnitFilter" class="border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-green-500 focus:border-green-500 focus:outline-none w-full sm:w-auto">
                                <option value="all">Semua Bentuk</option>
                                <option value="g/tanaman">Non Cair (g/tanaman)</option>
                                <option value="ml/tanaman">Cair (ml/tanaman)</option>
                            </select>
                            <select id="fertilizationTypeFilter" class="border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-green-500 focus:border-green-500 focus:outline-none w-full sm:w-auto">
                                <option value="all">Semua Jenis</option>
                                <option value="Organik">Organik</option>
                                <option value="Anorganik">Anorganik</option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-container bg-gray-50 rounded-lg p-2">
                            <div class="chart-wrapper">
                                <canvas id="fertilizationChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Pesticide Chart -->
                <div class="chart-box chart-box-pesticide">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-2 sm:gap-0">
                            <h3 class="chart-header w-full sm:w-auto">Riwayat Pestisida</h3>
                        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                            <select id="pesticideTypeFilter" class="border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-green-500 focus:border-green-500 focus:outline-none w-full sm:w-auto">
                                <option value="all">Semua Jenis</option>
                                <option value="Insektisida">Insektisida</option>
                                <option value="Fungisida">Fungisida</option>
                                <option value="Herbisida">Herbisida</option>
                                <option value="Bakterisida">Bakterisida</option>
                            </select>
                            <select id="pesticideFormFilter" class="border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-green-500 focus:border-green-500 focus:outline-none w-full sm:w-auto">
                                <option value="all">Semua Bentuk</option>
                                <option value="cair">Cair</option>
                                <option value="non-cair">Non Cair</option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-container bg-gray-50 rounded-lg p-2">
                            <div class="chart-wrapper">
                                <canvas id="pesticideChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Harvest Chart -->
                <div class="chart-box chart-box-harvest">
                    <h3 class="chart-header">Total Berat Panen (kg)</h3>
                    <div class="chart-container bg-gray-50 rounded-lg p-2">
                        <div class="chart-wrapper">
                            <canvas id="harvestWeightChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Fruit Count Chart -->
                <div class="chart-box chart-box-harvest">
                    <h3 class="chart-header">Jumlah Buah Panen</h3>
                    <div class="chart-container bg-gray-50 rounded-lg p-2">
                        <div class="chart-wrapper">
                            <canvas id="harvestCountChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Health History Chart -->
                <div class="chart-box chart-box-health">
                    <h3 class="chart-header">Riwayat Status Kesehatan</h3>
                    <div class="chart-container bg-gray-50 rounded-lg p-2">
                        <div class="chart-wrapper">
                            <canvas id="healthHistoryChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Tree Info Card -->
                <div class="chart-box chart-box-info">
                    <h3 class="chart-header">Informasi Detail</h3>
                    <div class="space-y-4 px-2">
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                            <span class="text-gray-600 font-medium">Varietas</span>
                            <span class="font-semibold text-gray-800 bg-gray-100 px-3 py-1 rounded-full">{{ $tree->varietas ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                            <span class="text-gray-600 font-medium">Tahun Tanam</span>
                            <span class="font-semibold text-gray-800 bg-gray-100 px-3 py-1 rounded-full">{{ $tree->tahun_tanam ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                            <span class="text-gray-600 font-medium">Umur</span>
                            <span class="font-semibold text-gray-800 bg-gray-100 px-3 py-1 rounded-full">{{ $tree->tahun_tanam ? now()->year - $tree->tahun_tanam : '-' }} tahun</span>
                        </div>
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                            <span class="text-gray-600 font-medium">Status Kesehatan</span>
                            <span class="font-semibold text-gray-800 bg-gray-100 px-3 py-1 rounded-full">{{ $tree->health_status ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                            <span class="text-gray-600 font-medium">Total Produksi</span>
                            <span class="font-semibold text-gray-800 bg-gray-100 px-3 py-1 rounded-full">{{ number_format($totalHarvest, 2) }} kg</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Sumber Bibit</span>
                            <span class="font-semibold text-gray-800 bg-gray-100 px-3 py-1 rounded-full">{{ $tree->sumber_bibit ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigasi Tab -->
            <div class="mb-6" x-data="{ activeTab: 'growth' }">
                <div class="tab-navigation flex-nowrap overflow-x-auto whitespace-nowrap">
                    <button @click="activeTab = 'growth'; loadGrowthRecords();"
                        :class="{ 'active': activeTab === 'growth' }"
                        class="tab-button shrink-0">
                        Riwayat Pertumbuhan
                    </button>
                    <button @click="activeTab = 'health'; loadHealthProfiles();"
                        :class="{ 'active': activeTab === 'health' }"
                        class="tab-button shrink-0">
                        Riwayat Kesehatan
                    </button>
                    <button @click="activeTab = 'fertilization'"
                        :class="{ 'active': activeTab === 'fertilization' }"
                        class="tab-button shrink-0">
                        Riwayat Pemupukan
                    </button>
                    <button @click="activeTab = 'pesticide'"
                        :class="{ 'active': activeTab === 'pesticide' }"
                        class="tab-button shrink-0">
                        Riwayat Pestisida
                    </button>
                    <button @click="activeTab = 'zpt'; loadZptRecords();"
                        :class="{ 'active': activeTab === 'zpt' }"
                        class="tab-button shrink-0">
                        Riwayat ZPT
                    </button>
                    <button @click="activeTab = 'harvest'"
                        :class="{ 'active': activeTab === 'harvest' }"
                        class="tab-button shrink-0">
                        Riwayat Panen
                    </button>
                </div>

                <!-- Tab Riwayat Pertumbuhan -->
                <div x-show="activeTab === 'growth'" class="tab-content">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center">
                            <i class="fas fa-ruler text-green-500 mr-2"></i>Riwayat Pertumbuhan
                        </h2>
                        @if(Auth::check() && Auth::user()->role_id != 4)
                        <button @click="openGrowthModal()" class="btn-primary">
                            <i class="fas fa-plus"></i>Tambah Data
                            </button>
                        @endif
                        </div>
                    <div class="overflow-x-auto rounded-lg">
                        <table class="data-table w-full">
                                <thead>
                                <tr>
                                    <th class="text-center">Tanggal</th>
                                    <th class="text-center">Fase</th>
                                    <th class="text-center">Tinggi (cm)</th>
                                    <th class="text-center">Diameter (cm)</th>
                                    @if(Auth::check() && Auth::user()->role_id != 4)
                                    <th class="text-center">Aksi</th>
                                    @endif
                                    </tr>
                                </thead>
                                <tbody id="growthRecordsTable">
                                    @forelse($growths as $growth)
                                <tr>
                                    <td class="text-center">{{ \Carbon\Carbon::parse($growth->tanggal)->format('d/m/Y') }}</td>
                                    <td class="text-center">{{ $growth->fase }}</td>
                                    <td class="text-center">{{ $growth->tinggi }}</td>
                                    <td class="text-center">{{ $growth->diameter }}</td>
                                    @if(Auth::check() && Auth::user()->role_id != 4)
                                    <td class="text-center">
                                        <div class="flex justify-center space-x-1">
                                            <button onclick="editGrowthRecord({{ $growth->id }})"
                                                    class="p-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition-colors">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteGrowthRecord({{ $growth->id }})"
                                                    class="p-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        </td>
                                    @endif
                                    </tr>
                                    @empty
                                    <tr>
                                    <td colspan="{{ Auth::check() && Auth::user()->role_id != 4 ? 5 : 4 }}" class="text-center py-8">
                                        <div class="flex flex-col items-center justify-center text-gray-500">
                                            <i class="fas fa-info-circle text-2xl mb-2"></i>
                                            <p>Belum ada data riwayat pertumbuhan</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                    </div>
                </div>

                <!-- Tab Riwayat Kesehatan -->
                <div x-show="activeTab === 'health'" class="tab-content">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center">
                            <i class="fas fa-heartbeat text-green-500 mr-2"></i>Riwayat Kesehatan
                        </h2>
                        @if(Auth::check() && Auth::user()->role_id != 4)
                        <button @click="$dispatch('open-health-modal')" class="btn-primary">
                            <i class="fas fa-plus"></i>Tambah Data
                            </button>
                        @endif
                        </div>
                    <div class="overflow-x-auto rounded-lg">
                        <table class="data-table w-full">
                                <thead>
                                <tr>
                                    <th class="text-center">Tanggal</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Gejala</th>
                                    <th class="text-center">Diagnosis</th>
                                    <th class="text-center">Tindakan</th>
                                    <th class="text-center">Foto</th>
                                    @if(Auth::check() && Auth::user()->role_id != 4)
                                    <th class="text-center">Aksi</th>
                                    @endif
                                    </tr>
                                </thead>
                                <tbody id="health-profiles-container">
                                    @forelse($healthProfiles ?? [] as $profile)
                                        @php
                                            $statusClass = '';
                                            if ($profile->status_kesehatan == 'Sehat') $statusClass = 'bg-green-100 text-green-800';
                                            elseif ($profile->status_kesehatan == 'Stres') $statusClass = 'bg-yellow-100 text-yellow-800';
                                            elseif ($profile->status_kesehatan == 'Sakit') $statusClass = 'bg-red-100 text-red-800';
                                            elseif ($profile->status_kesehatan == 'Mati') $statusClass = 'bg-gray-700 text-white';
                                            else $statusClass = 'bg-gray-100 text-gray-800';
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($profile->tanggal_pemeriksaan)->format('d/m/Y') }}</td>
                                    <td class="text-center">
                                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                                    {{ $profile->status_kesehatan }}
                                        </span>
                                    </td>
                                            <td class="text-center">{{ $profile->gejala ?? '-' }}</td>
                                            <td class="text-center">{{ $profile->diagnosis ?? '-' }}</td>
                                            <td class="text-center">{{ $profile->tindakan_penanganan ?? '-' }}</td>
                                            <td class="text-center">
                                                @if($profile->foto_kondisi)
                                                    <button onclick="showPhotoModal('{{ asset('storage/' . $profile->foto_kondisi) }}')" class="text-blue-500 hover:underline">Lihat Foto</button>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                    @if(Auth::check() && Auth::user()->role_id != 4)
                                    <td class="text-center">
                                        <div class="flex justify-center space-x-1">
                                                    <button onclick="editHealthProfile({{ $profile->id }})" class="p-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition-colors">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                                    <button onclick="deleteHealthProfile({{ $profile->id }})" class="p-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        </td>
                                    @endif
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="{{ Auth::check() && Auth::user()->role_id != 4 ? 7 : 6 }}" class="text-center py-8">
                                        <div class="flex flex-col items-center justify-center text-gray-500">
                                            <i class="fas fa-info-circle text-2xl mb-2"></i>
                                                <p>Belum ada data riwayat kesehatan</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                    </div>
                </div>

                <!-- Tab Pemupukan -->
                <div x-show="activeTab === 'fertilization'" class="tab-content">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center">
                            <i class="fas fa-seedling text-green-500 mr-2"></i>Riwayat Pemupukan
                        </h2>
                        @if(Auth::check() && Auth::user()->role_id != 4) <!-- role_id 4 = Guest -->
                        <button @click="$dispatch('open-fertilization-modal')" class="btn-primary">
                            <i class="fas fa-plus"></i>Tambah Data
                            </button>
                        @endif
                        </div>
                    <div class="overflow-x-auto rounded-lg">
                        <table class="data-table w-full">
                                <thead>
                                <tr>
                                    <th class="text-center">Tanggal</th>
                                    <th class="text-center">Nama Pupuk</th>
                                    <th class="text-center">Jenis Pupuk</th>
                                    <th class="text-center">Bentuk Pupuk</th>
                                    <th class="text-center">Dosis</th>
                                    <th class="text-center">Satuan</th>
                                    @if(Auth::check() && Auth::user()->role_id != 4)
                                    <th class="text-center">Aksi</th>
                                    @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($fertilizations as $fertilization)
                                <tr>
                                    <td class="text-center">{{ \Carbon\Carbon::parse($fertilization->tanggal_pemupukan)->format('d/m/Y') }}</td>
                                    <td class="text-center">{{ $fertilization->nama_pupuk }}</td>
                                    <td class="text-center">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $fertilization->jenis_pupuk == 'Organik' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ $fertilization->jenis_pupuk }}
                                            </span>
                                        </td>
                                    <td class="text-center">{{ $fertilization->bentuk_pupuk }}</td>
                                    <td class="text-center">{{ $fertilization->dosis_pupuk }}</td>
                                    <td class="text-center">{{ $fertilization->unit }}</td>
                                    @if(Auth::check() && Auth::user()->role_id != 4)
                                    <td class="text-center">
                                        <div class="flex justify-center space-x-1">
                                            <button onclick="editFertilization({{ $fertilization->id }})"
                                                    class="p-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition-colors">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteFertilization({{ $fertilization->id }})"
                                                    class="p-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        </td>
                                    @endif
                                    </tr>
                                    @empty
                                    <tr>
                                    <td colspan="{{ Auth::check() && Auth::user()->role_id != 4 ? 7 : 6 }}" class="text-center py-8">
                                        <div class="flex flex-col items-center justify-center text-gray-500">
                                            <i class="fas fa-info-circle text-2xl mb-2"></i>
                                            <p>Belum ada data pemupukan</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                    </div>
                </div>

                <!-- Tab Pestisida -->
                <div x-show="activeTab === 'pesticide'" class="tab-content">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center">
                            <i class="fas fa-spray-can text-green-500 mr-2"></i>Riwayat Pestisida
                        </h2>
                        @if(Auth::check() && Auth::user()->role_id != 4)
                        <button @click="$dispatch('open-pesticide-modal')" class="btn-primary">
                            <i class="fas fa-plus"></i>Tambah Data
                            </button>
                        @endif
                        </div>
                    <div class="overflow-x-auto rounded-lg">
                        <table class="data-table w-full">
                                <thead>
                                <tr>
                                    <th class="text-center">Tanggal</th>
                                    <th class="text-center">Nama Pestisida</th>
                                    <th class="text-center">Jenis Pestisida</th>
                                    <th class="text-center">Bentuk Pestisida</th>
                                    <th class="text-center">Dosis</th>
                                    <th class="text-center">Satuan</th>
                                    @if(Auth::check() && Auth::user()->role_id != 4)
                                    <th class="text-center">Aksi</th>
                                    @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pesticides as $pesticide)
                                <tr>
                                    <td class="text-center">{{ \Carbon\Carbon::parse($pesticide->tanggal_pestisida)->format('d/m/Y') }}</td>
                                    <td class="text-center">{{ $pesticide->nama_pestisida }}</td>
                                    <td class="text-center">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium
                                            @if($pesticide->jenis_pestisida == 'Insektisida') bg-red-100 text-red-800
                                            @elseif($pesticide->jenis_pestisida == 'Fungisida') bg-blue-100 text-blue-800
                                            @elseif($pesticide->jenis_pestisida == 'Herbisida') bg-green-100 text-green-800
                                            @else bg-purple-100 text-purple-800
                                            @endif">
                                            {{ $pesticide->jenis_pestisida }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $pesticide->bentuk_pestisida ?? '-' }}</td>
                                    <td class="text-center">{{ $pesticide->dosis }}</td>
                                    <td class="text-center">{{ $pesticide->unit }}</td>
                                    @if(Auth::check() && Auth::user()->role_id != 4)
                                    <td class="text-center">
                                        <div class="flex justify-center space-x-1">
                                            <button onclick="editPesticide({{ $pesticide->id }})"
                                                    class="p-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition-colors">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deletePesticide({{ $pesticide->id }})"
                                                    class="p-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        </td>
                                    @endif
                                    </tr>
                                    @empty
                                    <tr>
                                    <td colspan="{{ Auth::check() && Auth::user()->role_id != 4 ? 7 : 6 }}" class="text-center py-8">
                                        <div class="flex flex-col items-center justify-center text-gray-500">
                                            <i class="fas fa-info-circle text-2xl mb-2"></i>
                                            <p>Belum ada data pestisida</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                    </div>
                </div>

                <!-- Tab Riwayat ZPT -->
                <div x-show="activeTab === 'zpt'" class="tab-content">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center">
                            <i class="fas fa-flask text-green-500 mr-2"></i>Riwayat ZPT
                        </h2>
                        @if(Auth::check() && Auth::user()->role_id != 4)
                        <button @click="$dispatch('open-zpt-modal')" class="btn-primary">
                            <i class="fas fa-plus"></i>Tambah Data
                            </button>
                        @endif
                        </div>
                    <div class="overflow-x-auto rounded-lg">
                        <table class="data-table w-full">
                                <thead>
                                <tr>
                                    <th class="text-center">Tanggal</th>
                                    <th class="text-center">Nama ZPT</th>
                                    <th class="text-center">Merek</th>
                                    <th class="text-center">Jenis Senyawa</th>
                                    <th class="text-center">Konsentrasi</th>
                                    <th class="text-center">Volume</th>
                                    <th class="text-center">Fase</th>
                                    <th class="text-center">Metode</th>
                                    @if(Auth::check() && Auth::user()->role_id != 4)
                                    <th class="text-center">Aksi</th>
                                    @endif
                                    </tr>
                                </thead>
                                <tbody id="zptRecordsTable">
                                    @forelse($zptRecords ?? [] as $record)
                                    <tr>
                                        <td class="text-center">{{ \Carbon\Carbon::parse($record->tanggal_aplikasi)->format('d/m/Y') }}</td>
                                        <td class="text-center">{{ $record->nama_zpt ?? '-' }}</td>
                                        <td class="text-center">{{ $record->merek ?? '-' }}</td>
                                        <td class="text-center">{{ $record->jenis_senyawa ?? '-' }}</td>
                                        <td class="text-center">{{ $record->konsentrasi ?? '-' }}</td>
                                        <td class="text-center">{{ $record->volume_larutan ?? '-' }} {{ $record->unit ?? '' }}</td>
                                        <td class="text-center">{{ $record->fase_pertumbuhan ?? '-' }}</td>
                                        <td class="text-center">{{ $record->metode_aplikasi ?? '-' }}</td>
                                        @if(Auth::check() && Auth::user()->role_id != 4)
                                        <td class="text-center">
                                            <div class="flex justify-center space-x-1">
                                                <button onclick="editZptRecord({{ $record->id }})" class="p-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition-colors">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="deleteZptRecord({{ $record->id }})" class="p-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                    <td colspan="{{ Auth::check() && Auth::user()->role_id != 4 ? 9 : 8 }}" class="text-center py-8">
                                        <div class="flex flex-col items-center justify-center text-gray-500">
                                            <i class="fas fa-info-circle text-2xl mb-2"></i>
                                            <p>Belum ada data ZPT</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                    </div>
                </div>

                <!-- Tab Panen -->
                <div x-show="activeTab === 'harvest'" class="tab-content">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center">
                            <i class="fas fa-leaf text-green-500 mr-2"></i>Riwayat Panen
                        </h2>
                        @if(Auth::check() && Auth::user()->role_id != 4)
                        <button @click="$dispatch('open-harvest-modal')" class="btn-primary">
                            <i class="fas fa-plus"></i>Tambah Data
                            </button>
                        @endif
                        </div>
                    <div class="overflow-x-auto rounded-lg">
                        <table class="data-table w-full">
                                <thead>
                                <tr>
                                    <th class="text-center">Tanggal</th>
                                    <th class="text-center">Total Berat</th>
                                    <th class="text-center">Jumlah Buah</th>
                                    <th class="text-center">Rata-rata/Buah</th>
                                    <th class="text-center">Kondisi</th>
                                    @if(Auth::check() && Auth::user()->role_id != 4)
                                    <th class="text-center">Aksi</th>
                                    @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($harvests as $harvest)
                                <tr>
                                    <td class="text-center">{{ \Carbon\Carbon::parse($harvest->tanggal_panen)->format('d/m/Y') }}</td>
                                    <td class="text-center">{{ $harvest->total_weight }} {{ $harvest->unit }}</td>
                                    <td class="text-center">{{ $harvest->fruit_count }}</td>
                                    <td class="text-center">{{ number_format($harvest->average_weight_per_fruit, 2) }} {{ $harvest->unit }}</td>
                                    <td class="text-center">
                                            @php
                                                $condition = floatval($harvest->fruit_condition);
                                                $conditionClass = '';
                                                $conditionText = '';

                                                if ($condition >= 0 && $condition <= 20) {
                                                    $conditionClass = 'bg-red-100 text-red-800';
                                                    $conditionText = 'Sangat Tidak Baik';
                                                } elseif ($condition > 20 && $condition <= 40) {
                                                    $conditionClass = 'bg-orange-100 text-orange-800';
                                                    $conditionText = 'Tidak Baik';
                                                } elseif ($condition > 40 && $condition <= 60) {
                                                    $conditionClass = 'bg-yellow-100 text-yellow-800';
                                                    $conditionText = 'Cukup';
                                                } elseif ($condition > 60 && $condition <= 80) {
                                                    $conditionClass = 'bg-blue-100 text-blue-800';
                                                    $conditionText = 'Baik';
                                                } else {
                                                    $conditionClass = 'bg-green-100 text-green-800';
                                                    $conditionText = 'Sangat Baik';
                                                }
                                            @endphp
                                            <span class="px-2 py-1 rounded-full {{ $conditionClass }}">
                                                {{ $conditionText }} ({{ number_format($harvest->fruit_condition, 2) }}%)
                                            </span>
                                        </td>
                                    @if(Auth::check() && Auth::user()->role_id != 4)
                                    <td class="text-center">
                                        <div class="flex justify-center space-x-1">
                                            <button onclick="editHarvest({{ $harvest->id }})"
                                                    class="p-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition-colors">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteHarvest({{ $harvest->id }})"
                                                    class="p-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ Auth::check() && Auth::user()->role_id != 4 ? 6 : 5 }}" class="text-center py-8">
                                        <div class="flex flex-col items-center justify-center text-gray-500">
                                            <i class="fas fa-info-circle text-2xl mb-2"></i>
                                            <p>Belum ada data panen</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                    </div>
                </div>

            </div>
    </div>
</div>

<!-- Modal Foto Kesehatan -->
<div id="photoModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-800 opacity-80 backdrop-blur-sm"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-camera text-green-500 mr-2"></i>
                        Foto Kondisi Pohon
                    </h3>
                    <button onclick="closePhotoModal()" class="text-gray-500 hover:text-gray-700 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="flex justify-center p-2 bg-gray-50 rounded-lg">
                    <img id="modalPhotoImg" src="" alt="Foto kondisi pohon" class="max-h-96 max-w-full rounded-lg shadow-md">
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end border-t border-gray-100">
                <button type="button" onclick="closePhotoModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-2 rounded-lg font-medium transition-colors flex items-center">
                    <i class="fas fa-times mr-2"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Riwayat Pertumbuhan -->
<div id="growthModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true" onclick="closeGrowthModal()">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 flex items-center" id="growthModalTitle">
                        <i class="fas fa-ruler text-green-500 mr-2"></i>
                        Tambah Data Pertumbuhan
                    </h3>
                    <button onclick="closeGrowthModal()" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Close</span>
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="mt-4">
                    <form id="growthForm" action="/tree/growth">
                        <input type="hidden" name="tree_id" value="{{ $tree->id }}">
                        <input type="hidden" id="growth_id" name="growth_id">
                        <div class="mb-4">
                            <label for="tanggal" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pengukuran</label>
                            <input type="date" id="tanggal" name="tanggal" required
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>

                        <div class="mb-4">
                            <label for="fase" class="block text-sm font-medium text-gray-700 mb-1">Fase Pertumbuhan</label>
                            <input type="text" id="fase" name="fase"
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500"
                                   placeholder="Contoh: Vegetatif, Generatif, dll">
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="tinggi" class="block text-sm font-medium text-gray-700 mb-1">Tinggi (cm)</label>
                                <input type="number" step="0.01" id="tinggi" name="tinggi"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500"
                                       placeholder="0.00">
                            </div>
                            <div>
                                <label for="diameter" class="block text-sm font-medium text-gray-700 mb-1">Diameter (cm)</label>
                                <input type="number" step="0.01" id="diameter" name="diameter"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500"
                                       placeholder="0.00">
                            </div>
                        </div>

                        <div class="flex justify-end mt-6 space-x-3">
                            <button type="button" onclick="closeGrowthModal()"
                                    class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                Batal
                            </button>
                            <button type="submit"
                            <button type="submit"
                                    class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Riwayat Pertumbuhan -->
<div id="editGrowthModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true" onclick="closeEditGrowthModal()">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 flex items-center">
                        <i class="fas fa-ruler text-blue-500 mr-2"></i>
                        Edit Data Pertumbuhan
                    </h3>
                    <button onclick="closeEditGrowthModal()" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Close</span>
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="mt-4">
                    <form id="editGrowthForm">
                        <input type="hidden" id="edit_growth_id" name="growth_id">
                        <div class="mb-4">
                            <label for="edit_tanggal" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pengukuran</label>
                            <input type="date" id="edit_tanggal" name="tanggal" required
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="mb-4">
                            <label for="edit_fase" class="block text-sm font-medium text-gray-700 mb-1">Fase Pertumbuhan</label>
                            <input type="text" id="edit_fase" name="fase"
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Contoh: Vegetatif, Generatif, dll">
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="edit_tinggi" class="block text-sm font-medium text-gray-700 mb-1">Tinggi (cm)</label>
                                <input type="number" step="0.01" id="edit_tinggi" name="tinggi"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="0.00">
                            </div>
                            <div>
                                <label for="edit_diameter" class="block text-sm font-medium text-gray-700 mb-1">Diameter (cm)</label>
                                <input type="number" step="0.01" id="edit_diameter" name="diameter"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="0.00">
                            </div>
                        </div>

                        <div class="flex justify-end mt-6 space-x-3">
                            <button type="button" onclick="closeEditGrowthModal()"
                                    class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Batal
                            </button>
                            <button type="submit"
                                    class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('assets/js/tree-dashboard.js') }}"></script>
<script>
    // Fungsi untuk menampilkan notifikasi toast
    function showToast(message, type = 'info') {
        // Fungsi ini dinonaktifkan atas permintaan user
        console.log(`Toast message (${type}):`, message);
    }

    // Inisialisasi grafik pemupukan
    const fertilizationCtx = document.getElementById('fertilizationChart');
    let fertilizationChart = null;

    if (fertilizationCtx) {
        const fertilizationData = {!! json_encode($fertilizations->map(function($item) {
            return [
                'id' => $item->id,
                'tanggal' => Carbon\Carbon::parse($item->tanggal_pemupukan)->format('d/m/Y'),
                'tanggal_sort' => $item->tanggal_pemupukan,
                'dosis' => $item->dosis_pupuk,
                'nama' => $item->nama_pupuk,
                'unit' => $item->unit,
                'jenis_pupuk' => $item->jenis_pupuk
            ];
        })->sortBy('tanggal_sort')->values()) !!};

        function renderFertilizationChart(unitFilter = 'g/tanaman', typeFilter = 'all') {
            // Hapus chart sebelumnya jika ada
            if (fertilizationChart) {
                fertilizationChart.destroy();
            }

            // Filter data berdasarkan unit dan jenis pupuk
            let filteredData = [...fertilizationData];
            
            // Filter berdasarkan unit jika bukan 'all'
            if (unitFilter !== 'all') {
                filteredData = filteredData.filter(item => item.unit === unitFilter);
            }
            
            // Filter berdasarkan jenis pupuk jika bukan 'all'
            if (typeFilter !== 'all') {
                filteredData = filteredData.filter(item => item.jenis_pupuk === typeFilter);
            }

            // Jika data kosong setelah filter, tampilkan pesan
            if (filteredData.length === 0) {
                const ctx = fertilizationCtx.getContext('2d');
                ctx.clearRect(0, 0, fertilizationCtx.width, fertilizationCtx.height);

                // Buat elemen div untuk pesan "tidak ada data"
                const wrapper = document.createElement('div');
                wrapper.style.position = 'absolute';
                wrapper.style.top = '0';
                wrapper.style.left = '0';
                wrapper.style.width = '100%';
                wrapper.style.height = '100%';
                wrapper.style.display = 'flex';
                wrapper.style.flexDirection = 'column';
                wrapper.style.alignItems = 'center';
                wrapper.style.justifyContent = 'center';
                wrapper.style.color = '#6B7280';

                // Buat ikon
                const icon = document.createElement('i');
                icon.className = 'fas fa-exclamation-circle';
                icon.style.fontSize = '24px';
                icon.style.marginBottom = '10px';

                // Buat teks
                const text = document.createElement('div');
                text.textContent = 'Belum ada data pemupukan untuk filter yang dipilih';
                text.style.textAlign = 'center';
                text.style.fontSize = '14px';
                text.style.maxWidth = '80%';

                // Tambahkan ke wrapper
                wrapper.appendChild(icon);
                wrapper.appendChild(text);

                // Tambahkan wrapper ke parent element dari canvas
                const parent = fertilizationCtx.parentElement;
                parent.style.position = 'relative';
                parent.appendChild(wrapper);

                return;
            } else {
                // Hapus pesan error jika ada
                const parent = fertilizationCtx.parentElement;
                const existingError = parent.querySelector('div');
                if (existingError && existingError !== fertilizationCtx) {
                    parent.removeChild(existingError);
                }
            }

            // Mengelompokkan data berdasarkan tanggal
            const groupedData = {};
            filteredData.forEach(item => {
                if (!groupedData[item.tanggal]) {
                    groupedData[item.tanggal] = {};
                }
                if (!groupedData[item.tanggal][item.nama]) {
                    groupedData[item.tanggal][item.nama] = 0;
                }
                groupedData[item.tanggal][item.nama] += item.dosis;
            });

            // Mendapatkan semua nama pupuk yang unik
            const namaPupuk = [...new Set(filteredData.map(item => item.nama))];

            // Membuat dataset untuk setiap nama pupuk
            const datasets = namaPupuk.map((nama, index) => {
                const colors = [
                    { bg: 'rgba(44, 123, 229, 0.5)', border: 'rgb(44, 123, 229)' },     // Biru
                    { bg: 'rgba(67, 160, 71, 0.5)', border: 'rgb(67, 160, 71)' },       // Hijau
                    { bg: 'rgba(255, 152, 0, 0.5)', border: 'rgb(255, 152, 0)' },       // Oranye
                    { bg: 'rgba(156, 39, 176, 0.5)', border: 'rgb(156, 39, 176)' },     // Ungu
                    { bg: 'rgba(233, 30, 99, 0.5)', border: 'rgb(233, 30, 99)' },       // Pink
                    { bg: 'rgba(0, 150, 136, 0.5)', border: 'rgb(0, 150, 136)' },       // Teal
                    { bg: 'rgba(121, 85, 72, 0.5)', border: 'rgb(121, 85, 72)' }        // Coklat
                ];

                return {
                    label: nama,
                    data: Object.keys(groupedData).map(tanggal => groupedData[tanggal][nama] || 0),
                    backgroundColor: colors[index % colors.length].bg,
                    borderColor: colors[index % colors.length].border,
                    borderWidth: 1
                };
            });

            // Tentukan unit label berdasarkan filter bentuk
            let unitLabel = 'Dosis';
            if (unitFilter === 'ml/tanaman') {
                unitLabel = 'Dosis (ml/tanaman)';
            } else if (unitFilter === 'g/tanaman') {
                unitLabel = 'Dosis (g/tanaman)';
            } else {
                // Jika "Semua Bentuk", periksa data untuk menentukan unit yang tepat
                const hasMl = filteredData.some(item => item.unit === 'ml/tanaman');
                const hasG = filteredData.some(item => item.unit === 'g/tanaman');
                
                if (hasMl && !hasG) {
                    unitLabel = 'Dosis (ml/tanaman)';
                } else if (!hasMl && hasG) {
                    unitLabel = 'Dosis (g/tanaman)';
                } else if (hasMl && hasG) {
                    unitLabel = 'Dosis (campuran unit)';
                }
            }

            fertilizationChart = new Chart(fertilizationCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(groupedData),
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: false,
                            grid: {
                                offset: false
                            },
                            ticks: {
                                align: 'center'
                            },
                            offset: true
                        },
                        y: {
                            stacked: false,
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: unitLabel,
                                font: {
                                    weight: 'normal'
                                }
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: false,
                            text: '',
                        },
                        legend: {
                            position: 'top',
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    // Ambil tanggal dan nama pupuk dari data yang di-hover
                                    const tanggal = context.chart.data.labels[context.dataIndex];
                                    const namaPupuk = context.dataset.label;
                                    
                                    // Cari semua item pemupukan dengan tanggal dan nama yang sama dari data asli
                                    const matchingItems = fertilizationData.filter(item => 
                                        item.tanggal === tanggal && 
                                        item.nama === namaPupuk
                                    );
                                    
                                    // Gunakan unit dari data asli jika ditemukan
                                    let unit;
                                    if (matchingItems.length > 0) {
                                        unit = matchingItems[0].unit;
                                    } else if (unitFilter !== 'all') {
                                        unit = unitFilter;
                                    } else {
                                        unit = 'unit';
                                    }
                                    
                                    return `${context.dataset.label}: ${context.parsed.y} ${unit}`;
                                }
                            }
                        }
                    },
                    barPercentage: 1,
                    categoryPercentage: 1,
                    borderRadius: 3
                }
            });
        }

        // Render chart pertama kali dengan semua bentuk (default)
        renderFertilizationChart('all', 'all');

        // Tambahkan event listener untuk filter unit dan jenis pupuk
        document.getElementById('fertilizationUnitFilter').addEventListener('change', function() {
            renderFertilizationChart(this.value, document.getElementById('fertilizationTypeFilter').value);
        });
        
        document.getElementById('fertilizationTypeFilter').addEventListener('change', function() {
            renderFertilizationChart(document.getElementById('fertilizationUnitFilter').value, this.value);
        });
    }

    // Inisialisasi grafik pestisida
    const pesticideCtx = document.getElementById('pesticideChart');
    let pesticideChart = null;

    if (pesticideCtx) {
        const pesticideData = {!! json_encode($pesticides->map(function($item) {
            return [
                'id' => $item->id,
                'tanggal' => Carbon\Carbon::parse($item->tanggal_pestisida)->format('d/m/Y'),
                'tanggal_sort' => $item->tanggal_pestisida,
                'dosis' => $item->dosis,
                'nama' => $item->nama_pestisida,
                'jenis' => $item->jenis_pestisida,
                'unit' => $item->unit
            ];
        })->sortBy('tanggal_sort')->values()) !!};

        function renderPesticideChart(typeFilter = 'all', formFilter = 'all') {
            // Hapus chart sebelumnya jika ada
            if (pesticideChart) {
                pesticideChart.destroy();
            }

            // Filter data berdasarkan jenis pestisida
            let filteredData = [...pesticideData];
            if (typeFilter !== 'all') {
                filteredData = filteredData.filter(item => item.jenis === typeFilter);
            }
            
            // Filter berdasarkan bentuk pestisida (cair/non-cair)
            if (formFilter !== 'all') {
                if (formFilter === 'cair') {
                    // Filter untuk pestisida cair (unit ml/tanaman)
                    filteredData = filteredData.filter(item => item.unit === 'ml/tanaman');
                } else if (formFilter === 'non-cair') {
                    // Filter untuk pestisida non-cair (unit g/tanaman)
                    filteredData = filteredData.filter(item => item.unit === 'g/tanaman');
                }
            }

            // Jika data kosong setelah filter, tampilkan pesan
            if (filteredData.length === 0) {
                const ctx = pesticideCtx.getContext('2d');
                ctx.clearRect(0, 0, pesticideCtx.width, pesticideCtx.height);

                // Buat elemen div untuk pesan "tidak ada data"
                const wrapper = document.createElement('div');
                wrapper.style.position = 'absolute';
                wrapper.style.top = '0';
                wrapper.style.left = '0';
                wrapper.style.width = '100%';
                wrapper.style.height = '100%';
                wrapper.style.display = 'flex';
                wrapper.style.flexDirection = 'column';
                wrapper.style.alignItems = 'center';
                wrapper.style.justifyContent = 'center';
                wrapper.style.color = '#6B7280';

                // Buat ikon untuk grafik pestisida
                const icon = document.createElement('i');
                icon.className = 'fas fa-exclamation-circle';
                icon.style.fontSize = '24px';
                icon.style.marginBottom = '10px';

                // Buat teks untuk grafik pestisida
                const text = document.createElement('div');
                text.textContent = 'Belum ada data pestisida untuk filter yang dipilih';
                text.style.textAlign = 'center';
                text.style.fontSize = '14px';
                text.style.maxWidth = '80%';

                // Tambahkan ke wrapper
                wrapper.appendChild(icon);
                wrapper.appendChild(text);

                // Tambahkan wrapper ke parent element dari canvas
                const parent = pesticideCtx.parentElement;
                parent.style.position = 'relative';
                parent.appendChild(wrapper);

                return;
            } else {
                // Hapus pesan error jika ada
                const parent = pesticideCtx.parentElement;
                const existingError = parent.querySelector('div');
                if (existingError && existingError !== pesticideCtx) {
                    parent.removeChild(existingError);
                }
            }

            // Mengelompokkan data berdasarkan tanggal
            const groupedData = {};
            filteredData.forEach(item => {
                if (!groupedData[item.tanggal]) {
                    groupedData[item.tanggal] = {};
                }
                if (!groupedData[item.tanggal][item.nama]) {
                    groupedData[item.tanggal][item.nama] = 0;
                }
                groupedData[item.tanggal][item.nama] += item.dosis;
            });

            // Mendapatkan semua nama pestisida yang unik
            const namaPestisida = [...new Set(filteredData.map(item => item.nama))];

            // Membuat dataset untuk setiap nama pestisida
            const datasets = namaPestisida.map((nama, index) => {
                const colors = [
                    { bg: 'rgba(44, 123, 229, 0.5)', border: 'rgb(44, 123, 229)' },     // Biru
                    { bg: 'rgba(67, 160, 71, 0.5)', border: 'rgb(67, 160, 71)' },       // Hijau
                    { bg: 'rgba(255, 152, 0, 0.5)', border: 'rgb(255, 152, 0)' },       // Oranye
                    { bg: 'rgba(156, 39, 176, 0.5)', border: 'rgb(156, 39, 176)' },     // Ungu
                    { bg: 'rgba(233, 30, 99, 0.5)', border: 'rgb(233, 30, 99)' },       // Pink
                    { bg: 'rgba(0, 150, 136, 0.5)', border: 'rgb(0, 150, 136)' },       // Teal
                    { bg: 'rgba(121, 85, 72, 0.5)', border: 'rgb(121, 85, 72)' }        // Coklat
                ];

                return {
                    label: nama,
                    data: Object.keys(groupedData).map(tanggal => groupedData[tanggal][nama] || 0),
                    backgroundColor: colors[index % colors.length].bg,
                    borderColor: colors[index % colors.length].border,
                    borderWidth: 1
                };
            });

            // Tentukan unit label berdasarkan filter bentuk
            let unitLabel = 'Dosis';
            if (formFilter === 'cair') {
                unitLabel = 'Dosis (ml/tanaman)';
            } else if (formFilter === 'non-cair') {
                unitLabel = 'Dosis (g/tanaman)';
            } else {
                // Jika "Semua Bentuk", periksa data untuk menentukan unit yang tepat
                const hasMl = filteredData.some(item => item.unit === 'ml/tanaman');
                const hasG = filteredData.some(item => item.unit === 'g/tanaman');
                
                if (hasMl && !hasG) {
                    unitLabel = 'Dosis (ml/tanaman)';
                } else if (!hasMl && hasG) {
                    unitLabel = 'Dosis (g/tanaman)';
                } else if (hasMl && hasG) {
                    unitLabel = 'Dosis (campuran unit)';
                }
            }

            pesticideChart = new Chart(pesticideCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(groupedData),
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: false,
                            grid: {
                                offset: false
                            },
                            ticks: {
                                align: 'center'
                            },
                            offset: true
                        },
                        y: {
                            stacked: false,
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: unitLabel,
                                font: {
                                    weight: 'normal'
                                }
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: false,
                            text: '',
                        },
                        legend: {
                            position: 'top',
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    // Ambil nama pestisida dan tanggal dari data yang di-hover
                                    const tanggal = context.chart.data.labels[context.dataIndex];
                                    const namaPestisida = context.dataset.label;
                                    
                                    // Cari pestisida yang sesuai dengan tanggal dan nama
                                    const matchingItems = pesticideData.filter(item => 
                                        item.tanggal === tanggal && 
                                        item.nama === namaPestisida
                                    );
                                    
                                    // Gunakan unit dari data asli jika ditemukan, defaultnya menggunakan unit berdasarkan filter
                                    let unit;
                                    if (matchingItems.length > 0) {
                                        unit = matchingItems[0].unit;
                                    } else if (formFilter !== 'all') {
                                        unit = formFilter === 'cair' ? 'ml/tanaman' : 'g/tanaman';
                                    } else {
                                        unit = filteredData.length > 0 ? filteredData[0].unit : 'unit';
                                    }
                                    
                                    return `${context.dataset.label}: ${context.parsed.y} ${unit}`;
                                }
                            }
                        }
                    },
                    barPercentage: 1,
                    categoryPercentage: 1,
                    borderRadius: 3
                }
            });
        }

        // Render chart pertama kali dengan semua data
        renderPesticideChart();

        // Tambahkan event listener untuk filter jenis pestisida
        document.getElementById('pesticideTypeFilter').addEventListener('change', function() {
            renderPesticideChart(this.value, document.getElementById('pesticideFormFilter').value);
        });
        
        // Tambahkan event listener untuk filter bentuk pestisida
        document.getElementById('pesticideFormFilter').addEventListener('change', function() {
            renderPesticideChart(document.getElementById('pesticideTypeFilter').value, this.value);
        });
    }

    // Inisialisasi grafik berat panen
    const harvestWeightCtx = document.getElementById('harvestWeightChart');
    if (harvestWeightCtx) {
        const harvestData = {!! json_encode($harvests->map(function($item) {
            return [
                'tanggal' => Carbon\Carbon::parse($item->tanggal_panen)->format('d/m/Y'),
                'tanggal_sort' => $item->tanggal_panen,
                'berat' => $item->total_weight
            ];
        })->sortBy('tanggal_sort')->values()) !!};

        new Chart(harvestWeightCtx, {
            type: 'line',
            data: {
                labels: harvestData.map(item => item.tanggal),
                datasets: [
                    {
                        label: 'Total Berat (kg)',
                        data: harvestData.map(item => item.berat),
                        borderColor: 'rgb(44, 123, 229)',
                        backgroundColor: 'rgba(44, 123, 229, 0.1)',
                        tension: 0.1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Berat (kg)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false,
                        position: 'top'
                    }
                }
            }
        });
    }

    // Inisialisasi grafik jumlah buah
    const harvestCountCtx = document.getElementById('harvestCountChart');
    if (harvestCountCtx) {
        const harvestData = {!! json_encode($harvests->map(function($item) {
            return [
                'tanggal' => Carbon\Carbon::parse($item->tanggal_panen)->format('d/m/Y'),
                'tanggal_sort' => $item->tanggal_panen,
                'jumlah' => $item->fruit_count
            ];
        })->sortBy('tanggal_sort')->values()) !!};

        new Chart(harvestCountCtx, {
            type: 'line',
            data: {
                labels: harvestData.map(item => item.tanggal),
                datasets: [
                    {
                        label: 'Jumlah Buah',
                        data: harvestData.map(item => item.jumlah),
                        borderColor: 'rgb(67, 160, 71)',
                        backgroundColor: 'rgba(67, 160, 71, 0.1)',
                        tension: 0.1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Jumlah Buah'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false,
                        position: 'top'
                    }
                }
            }
        });
    }

    // Inisialisasi grafik riwayat kesehatan
    const healthHistoryCtx = document.getElementById('healthHistoryChart');
    if (healthHistoryCtx) {
        fetch(`/api/trees/${document.querySelector('input[name="tree_id"]').value}/health-profiles`)
        .then(response => response.json())
        .then(response => {
            if (response.success && response.data) {
                const healthData = response.data
                    .map(item => ({
                        tanggal: new Date(item.tanggal_pemeriksaan).toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric'
                        }),
                        tanggal_sort: item.tanggal_pemeriksaan,
                        status: item.status_kesehatan
                    }))
                    .sort((a, b) => new Date(a.tanggal_sort) - new Date(b.tanggal_sort));

                const statusColors = {
                    'Sehat': 'rgb(67, 160, 71)',      // Hijau profesional
                    'Stres': 'rgb(255, 152, 0)',      // Oranye profesional
                    'Sakit': 'rgb(233, 30, 99)',      // Pink profesional
                    'Mati': 'rgb(211, 47, 47)'        // Merah profesional
                };

                const statusValues = {
                    'Sehat': 4,
                    'Stres': 3,
                    'Sakit': 2,
                    'Mati': 1
                };

                new Chart(healthHistoryCtx, {
                    type: 'line',
                    data: {
                        labels: healthData.map(item => item.tanggal),
                        datasets: [{
                            label: 'Status Kesehatan',
                            data: healthData.map(item => statusValues[item.status]),
                            borderColor: 'rgb(44, 123, 229)',
                            backgroundColor: 'rgba(44, 123, 229, 0.1)',
                            tension: 0.1,
                            pointBackgroundColor: healthData.map(item => statusColors[item.status]),
                            pointBorderColor: healthData.map(item => statusColors[item.status]),
                            pointRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 5,
                                ticks: {
                                    stepSize: 1,
                                    callback: function(value) {
                                        const labels = {
                                            1: 'Mati',
                                            2: 'Sakit',
                                            3: 'Stres',
                                            4: 'Sehat'
                                        };
                                        return labels[value] || '';
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `Status: ${healthData[context.dataIndex].status}`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error loading health history data:', error);
        });
    }

    // Load health profiles saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        // Periksa apakah tab kesehatan aktif
        const activeTab = document.querySelector('[x-data]')?.__x?.getUnobservedData()?.activeTab;
        if (activeTab === 'health') {
            loadHealthProfiles();
        } else if (activeTab === 'zpt') {
            loadZptRecords();
        }

        // Tambahkan event listener untuk perubahan tab
        window.addEventListener('tabChanged', function(e) {
            if (e.detail.tab === 'health') {
                loadHealthProfiles();
            } else if (e.detail.tab === 'zpt') {
                loadZptRecords();
            }
        });
    });

    // Fungsi untuk memuat riwayat pertumbuhan
    function loadGrowthRecords() {
        fetch(`/tree/growth/{{ $tree->id }}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const growthRecords = data.data;
                    const tableBody = document.getElementById('growthRecordsTable');

                    if (growthRecords.length === 0) {
                        const authCheck = '{{ Auth::check() && Auth::user()->role_id != 4 }}';
                        const colSpan = authCheck === '1' ? 5 : 4;

                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="${colSpan}" class="text-center py-8">
                                    <div class="flex flex-col items-center justify-center text-gray-500">
                                        <i class="fas fa-info-circle text-2xl mb-2"></i>
                                        <p>Belum ada data riwayat pertumbuhan</p>
                                    </div>
                                </td>
                            </tr>
                        `;
                    } else {
                        let html = '';
                        const authCheck = '{{ Auth::check() && Auth::user()->role_id != 4 }}';

                        growthRecords.forEach(record => {
                            const tanggal = new Date(record.tanggal).toLocaleDateString('id-ID');
                            const fase = record.fase || '-';
                            const tinggi = record.tinggi || '-';
                            const diameter = record.diameter || '-';

                            html += `
                                <tr>
                                    <td class="text-center">${tanggal}</td>
                                    <td class="text-center">${fase}</td>
                                    <td class="text-center">${tinggi}</td>
                                    <td class="text-center">${diameter}</td>
                            `;

                            if (authCheck === '1') {
                                html += `
                                    <td class="text-center">
                                        <div class="flex justify-center space-x-1">
                                            <button onclick="editGrowthRecord(${record.id})"
                                                    class="p-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition-colors">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteGrowthRecord(${record.id})"
                                                    class="p-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                `;
                            }

                            html += `</tr>`;
                        });

                        tableBody.innerHTML = html;
                    }
                } else {
                    showToast('Gagal memuat data pertumbuhan', 'error');
                }
            })
            .catch(error => {
                showToast('Terjadi kesalahan: ' + error, 'error');
            });
    }

    // Fungsi untuk menghapus riwayat pertumbuhan
    function deleteGrowthRecord(id) {
        if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            fetch(`/tree-dashboard/growth/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadGrowthRecords();
                    showToast('Data pertumbuhan berhasil dihapus', 'success');
                } else {
                    showToast('Gagal menghapus data: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Terjadi kesalahan: ' + error, 'error');
            });
        }
    }

    // Fungsi untuk menampilkan modal pertumbuhan
    function openGrowthModal() {
        document.getElementById('growthModal').style.display = 'block';
    }

    // Fungsi untuk menutup modal pertumbuhan
    function closeGrowthModal() {
        document.getElementById('growthModal').style.display = 'none';
        resetGrowthForm();
    }

    // Setup form pertumbuhan
    document.addEventListener('DOMContentLoaded', function() {
        // Muat data riwayat pertumbuhan saat halaman dimuat
        loadGrowthRecords();

        // Event listener untuk submit form pertumbuhan
        if (document.getElementById('growthForm')) {
            document.getElementById('growthForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const data = {};
                formData.forEach((value, key) => {
                    // Skip growth_id karena tidak diperlukan dalam body request
                    if (key !== 'growth_id') {
                        data[key] = value;
                    }
                });

                // Tentukan metode dan URL berdasarkan apakah ini adalah edit atau tambah
                const isEdit = formData.get('_method') === 'PUT';
                const growthId = document.getElementById('growth_id').value;

                // Log nilai ID untuk debugging
                console.log('Growth ID from hidden input:', growthId);

                const url = isEdit
                    ? `/tree/growth/${growthId}`
                    : '/tree/growth';
                const method = isEdit ? 'PUT' : 'POST';

                console.log(`Sending ${method} request to ${url} with data:`, data);

                fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(data)
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Server response:', data);
                    if (data.success) {
                        closeGrowthModal();
                        loadGrowthRecords();
                        showToast(isEdit
                            ? 'Data pertumbuhan berhasil diperbarui'
                            : 'Data pertumbuhan berhasil disimpan', 'success');
                    } else {
                        showToast('Gagal ' + (isEdit ? 'memperbarui' : 'menyimpan') +
                                  ' data: ' + (data.message || 'Terjadi kesalahan'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error submitting form:', error);
                    showToast('Terjadi kesalahan: ' + error, 'error');
                });
            });
        }
    });

    // Fungsi untuk membuka form edit riwayat pertumbuhan
    function editGrowthRecord(id) {
        if (!id) {
            showToast('ID data tidak valid', 'error');
            return;
        }

        console.log('Editing growth record with ID:', id);

        // Reset form terlebih dahulu
        resetGrowthForm();

        // Ubah judul modal dan tindakan form
        document.getElementById('growthModalTitle').textContent = 'Edit Data Pertumbuhan';

        // Simpan ID pada form
        document.getElementById('growth_id').value = id;

        // Tambahkan method PUT
        let methodInput = document.querySelector('#growthForm input[name="_method"]');
        if (!methodInput) {
            methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'PUT';
            document.getElementById('growthForm').appendChild(methodInput);
        } else {
            methodInput.value = 'PUT';
        }

        // Tampilkan modal terlebih dahulu
        openGrowthModal();

        // Ambil data dari server dengan ID yang benar
        // Menggunakan endpoint show untuk mendapatkan data pertumbuhan
        const url = `/tree/growth/show/${id}`;
        console.log('Fetching growth data from:', url);

        fetch(url, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Growth data received:', data);
            if (data.success) {
                const growth = data.data;
                console.log('Growth data details:', growth);

                // Format tanggal dengan benar untuk input date (yyyy-MM-dd)
                if (growth.tanggal) {
                    try {
                        // Coba parse tanggal dan format ulang untuk memastikan format yang benar
                        const tanggalObj = new Date(growth.tanggal);
                        if (!isNaN(tanggalObj.getTime())) {
                            // Tanggal valid, format sebagai yyyy-MM-dd
                            const yyyy = tanggalObj.getFullYear();
                            const mm = String(tanggalObj.getMonth() + 1).padStart(2, '0');
                            const dd = String(tanggalObj.getDate()).padStart(2, '0');
                            const formattedDate = `${yyyy}-${mm}-${dd}`;

                            document.getElementById('tanggal').value = formattedDate;
                            console.log('Set tanggal to formatted date:', formattedDate);
                        } else {
                            // Tanggal tidak valid, gunakan tanggal hari ini
                            console.warn('Invalid date received:', growth.tanggal);
                            document.getElementById('tanggal').value = new Date().toISOString().split('T')[0];
                        }
                    } catch (e) {
                        console.error('Error formatting date:', e);
                        document.getElementById('tanggal').value = new Date().toISOString().split('T')[0];
                    }
                } else {
                    // Tanggal tidak ada, gunakan tanggal hari ini
                    console.warn('No date value received');
                    document.getElementById('tanggal').value = new Date().toISOString().split('T')[0];
                }

                // Dapatkan elemen-elemen form
                const faseInput = document.getElementById('fase');
                const tinggiInput = document.getElementById('tinggi');
                const diameterInput = document.getElementById('diameter');

                // Log nilai yang diterima dari server (untuk debugging)
                console.log('Fase from server:', growth.fase, typeof growth.fase);
                console.log('Tinggi from server:', growth.tinggi, typeof growth.tinggi);
                console.log('Diameter from server:', growth.diameter, typeof growth.diameter);

                // Periksa dan isi nilai-nilai dengan penanganan yang lebih baik
                if (faseInput) {
                    faseInput.value = growth.fase !== null && growth.fase !== undefined ? String(growth.fase) : '';
                    console.log('Set fase to:', faseInput.value);
                } else {
                    console.error('Fase input element not found');
                }

                if (tinggiInput) {
                    // Konversi ke number, defaultnya 0 jika tidak ada atau invalid
                    const tinggiValue = growth.tinggi !== null && growth.tinggi !== undefined ? Number(growth.tinggi) : 0;
                    tinggiInput.value = tinggiValue;
                    console.log('Set tinggi to:', tinggiInput.value);
                } else {
                    console.error('Tinggi input element not found');
                }

                if (diameterInput) {
                    // Konversi ke number, defaultnya 0 jika tidak ada atau invalid
                    const diameterValue = growth.diameter !== null && growth.diameter !== undefined ? Number(growth.diameter) : 0;
                    diameterInput.value = diameterValue;
                    console.log('Set diameter to:', diameterInput.value);
                } else {
                    console.error('Diameter input element not found');
                }
            } else {
                showToast('Gagal memuat data pertumbuhan: ' + data.message, 'error');
                closeGrowthModal();
            }
        })
        .catch(error => {
            console.error('Error loading growth data:', error);
            showToast('Terjadi kesalahan: ' + error, 'error');
            closeGrowthModal();
        });
    }

    // Fungsi untuk menampilkan modal edit pertumbuhan
    function openEditGrowthModal() {
        document.getElementById('editGrowthModal').style.display = 'block';
    }

    // Fungsi untuk menutup modal edit pertumbuhan
    function closeEditGrowthModal() {
        document.getElementById('editGrowthModal').style.display = 'none';
    }

    // Fungsi untuk mereset form pertumbuhan
    function resetGrowthForm() {
        console.log('Resetting growth form');

        const form = document.getElementById('growthForm');
        if (!form) {
            console.error('Growth form not found!');
            return;
        }

        // Reset form
        form.reset();

        // Reset ID
        const idInput = document.getElementById('growth_id');
        if (idInput) {
            console.log('Clearing growth_id');
            idInput.value = '';
        } else {
            console.error('growth_id input not found!');
        }

        // Hapus method PUT jika ada
        const methodInput = form.querySelector('input[name="_method"]');
        if (methodInput) {
            console.log('Removing _method input');
            methodInput.remove();
        }

        // Reset judul modal
        const titleEl = document.getElementById('growthModalTitle');
        if (titleEl) {
            console.log('Resetting modal title');
            titleEl.textContent = 'Tambah Data Pertumbuhan';
        } else {
            console.error('growthModalTitle element not found!');
        }

        console.log('Form reset complete');
    }
</script>
@endpush
