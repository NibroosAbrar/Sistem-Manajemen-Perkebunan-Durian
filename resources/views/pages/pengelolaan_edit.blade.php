@extends('layouts.app')

@section('title', 'Edit Kegiatan - Symadu')
@section('header-title', 'Edit Kegiatan')

@section('content')
<style>
    html, body {
        height: 100%;
        overflow-y: auto !important;
    }
    .content-wrapper {
        min-height: 100vh;
        padding-bottom: 6rem;
    }
</style>

<div class="px-6 py-8 content-wrapper overflow-y-auto" style="padding-bottom: 150px;">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6 mb-20">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-800">Edit Kegiatan</h2>
            <a href="{{ route('pengelolaan',['tab' => 'kegiatan']) }}" class="text-emerald-600 hover:text-emerald-700">
                &larr; Kembali ke Daftar Kegiatan
            </a>
        </div>

        <form action="{{ route('pengelolaan.update', $kegiatan->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="nama_kegiatan">Nama Kegiatan</label>
                <input type="text" name="nama_kegiatan" id="nama_kegiatan" value="{{ old('nama_kegiatan', $kegiatan->nama_kegiatan) }}" required
                    class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('nama_kegiatan') border-red-500 @enderror">
                @error('nama_kegiatan')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="tanggal_mulai">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" id="tanggal_mulai" value="{{ old('tanggal_mulai', $kegiatan->tanggal_mulai ? $kegiatan->tanggal_mulai->format('Y-m-d') : '') }}" required
                        class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('tanggal_mulai') border-red-500 @enderror"
                        onchange="document.getElementById('tanggal_selesai').min = this.value">
                    @error('tanggal_mulai')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="tanggal_selesai">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" id="tanggal_selesai" value="{{ old('tanggal_selesai', $kegiatan->tanggal_selesai ? $kegiatan->tanggal_selesai->format('Y-m-d') : '') }}" required
                        class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('tanggal_selesai') border-red-500 @enderror"
                        min="{{ old('tanggal_mulai', $kegiatan->tanggal_mulai ? $kegiatan->tanggal_mulai->format('Y-m-d') : '') }}">
                    @error('tanggal_selesai')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="jenis_kegiatan">Jenis Kegiatan</label>
                <select name="jenis_kegiatan" id="jenis_kegiatan" required class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('jenis_kegiatan') border-red-500 @enderror">
                    <option value="">Pilih Jenis Kegiatan</option>
                    <option value="Penanaman" {{ old('jenis_kegiatan', $kegiatan->jenis_kegiatan) == 'Penanaman' ? 'selected' : '' }}>Penanaman</option>
                    <option value="Pemupukan" {{ old('jenis_kegiatan', $kegiatan->jenis_kegiatan) == 'Pemupukan' ? 'selected' : '' }}>Pemupukan</option>
                    <option value="Pengendalian OPT" {{ old('jenis_kegiatan', $kegiatan->jenis_kegiatan) == 'Pengendalian OPT' || old('jenis_kegiatan', $kegiatan->jenis_kegiatan) == 'Pestisida' ? 'selected' : '' }}>Pengendalian OPT</option>
                    <option value="Pengatur Tumbuh" {{ old('jenis_kegiatan', $kegiatan->jenis_kegiatan) == 'Pengatur Tumbuh' ? 'selected' : '' }}>Pengatur Tumbuh</option>
                    <option value="Panen" {{ old('jenis_kegiatan', $kegiatan->jenis_kegiatan) == 'Panen' ? 'selected' : '' }}>Panen</option>
                    {{-- Tambahkan jenis kegiatan lain jika ada --}}
                </select>
                @error('jenis_kegiatan')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="deskripsi_kegiatan">Deskripsi Kegiatan</label>
                <textarea name="deskripsi_kegiatan" id="deskripsi_kegiatan" required rows="3"
                    class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('deskripsi_kegiatan') border-red-500 @enderror">{{ old('deskripsi_kegiatan', $kegiatan->deskripsi_kegiatan) }}</textarea>
                @error('deskripsi_kegiatan')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="status">Status</label>
                <select name="status" id="status" required class="w-full border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('status') border-red-500 @enderror">
                    <option value="Belum Berjalan" {{ old('status', $kegiatan->status) == 'Belum Berjalan' ? 'selected' : '' }}>Belum Berjalan</option>
                    <option value="Sedang Berjalan" {{ old('status', $kegiatan->status) == 'Sedang Berjalan' ? 'selected' : '' }}>Sedang Berjalan</option>
                    <option value="Selesai" {{ old('status', $kegiatan->status) == 'Selesai' ? 'selected' : '' }}>Selesai</option>
                </select>
                @error('status')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end space-x-2 pt-4 pb-10">
                <a href="{{ route('pengelolaan', ['tab' => 'kegiatan']) }}"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-md transition-colors duration-200">
                    Batal
                </a>
                <button type="submit"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded-md shadow-sm transition-all duration-200">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Pastikan halaman dapat di-scroll
    document.addEventListener('DOMContentLoaded', function() {
        document.body.classList.add('overflow-y-auto');
        window.scrollTo(0, 0);
    });
</script>
@endsection
