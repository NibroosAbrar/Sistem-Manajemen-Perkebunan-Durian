@extends('layouts.master')

@section('title', 'Edit Foto Udara - Symadu')

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-auto">
    <h1 class="text-2xl font-bold text-center mb-6 text-green-700">Edit Foto Udara</h1>
    <form id="aerialPhotoForm" action="{{ route('aerial-photo.update', $photo->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="aerial_photo" class="block text-sm font-medium text-gray-700">Foto Udara</label>
            <input type="file" name="aerial_photo" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300">
        </div>
        <div class="form-group">
            <label for="bounds" class="block text-sm font-medium text-gray-700">Bounds (Koordinat)<span class="text-red-500">*</span></label>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label for="bounds_topleft" class="block text-xs text-gray-600">Top Left (Lat, Lng)</label>
                    @php
                        $boundsArray = json_decode($photo->bounds ?? '[[0,0],[0,0]]', true);
                        $topLeft = $boundsArray[0] ?? [0, 0];
                        $bottomRight = $boundsArray[1] ?? [0, 0];
                    @endphp
                    <input type="text" name="bounds_topleft" id="bounds_topleft"
                           placeholder="contoh: -7.123, 110.456"
                           value="{{ $topLeft[0] ?? '' }}, {{ $topLeft[1] ?? '' }}"
                           class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" required>
                </div>
                <div>
                    <label for="bounds_bottomright" class="block text-xs text-gray-600">Bottom Right (Lat, Lng)</label>
                    <input type="text" name="bounds_bottomright" id="bounds_bottomright"
                           placeholder="contoh: -7.234, 110.567"
                           value="{{ $bottomRight[0] ?? '' }}, {{ $bottomRight[1] ?? '' }}"
                           class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" required>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-1">Masukkan koordinat batas foto dalam format latitude, longitude</p>
        </div>
        <div class="form-group">
            <label for="resolution" class="block text-sm font-medium text-gray-700">Resolusi (cm/piksel)<span class="text-red-500">*</span></label>
            <input type="number" name="resolution" value="{{ $photo->resolution }}" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" step="0.01" required>
        </div>
        <div class="form-group">
            <label for="capture_time" class="block text-sm font-medium text-gray-700">Waktu Pengambilan<span class="text-red-500">*</span></label>
            <input type="datetime-local" name="capture_time" value="{{ $photo->capture_time }}" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" required>
        </div>
        <div class="form-group">
            <label for="drone_type" class="block text-sm font-medium text-gray-700">Tipe Drone<span class="text-red-500">*</span></label>
            <input type="text" name="drone_type" value="{{ $photo->drone_type }}" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" required>
        </div>
        <div class="form-group">
            <label for="height" class="block text-sm font-medium text-gray-700">Ketinggian (meter)<span class="text-red-500">*</span></label>
            <input type="number" name="height" value="{{ $photo->height }}" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" required>
        </div>
        <div class="form-group">
            <label for="overlap" class="block text-sm font-medium text-gray-700">Overlap (%)<span class="text-red-500">*</span></label>
            <input type="number" name="overlap" value="{{ $photo->overlap }}" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" required>
        </div>
        <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition">Simpan</button>
    </form>
    <p class="text-center text-gray-600 mt-4">
        <a href="{{ route('aerial-photo.index') }}" class="text-green-700 font-bold hover:underline">Kembali</a>
    </p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('aerialPhotoForm');
    
    form.addEventListener('submit', function() {
        // Pastikan fungsi showLoading tersedia
        if (typeof showLoading === 'function') {
            showLoading('Memperbarui foto udara...');
        } else {
            // Jika fungsi tidak tersedia, tambahkan fallback sederhana
            const loadingEl = document.createElement('div');
            loadingEl.className = 'fixed top-0 left-0 w-full h-full flex items-center justify-center bg-black bg-opacity-50 z-50';
            loadingEl.innerHTML = `
                <div class="bg-white p-4 rounded-lg shadow-lg text-center">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-green-700 mx-auto mb-2"></div>
                    <p class="text-gray-700">Memperbarui foto udara...</p>
                </div>
            `;
            document.body.appendChild(loadingEl);
        }
    });
});
</script>
@endsection
