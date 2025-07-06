@extends('layouts.master')

@section('title', 'Data Foto Udara - Symadu')

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold text-center mb-6 text-green-700">Data Foto Udara</h1>

    <div class="mb-4 flex justify-between items-center">
        <h2 class="text-lg font-semibold">Daftar Foto Udara</h2>
        <a href="{{ route('aerial-photo.create') }}" class="bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition">
            Tambah Foto Udara Baru
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    @if($photos->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resolusi (cm/piksel)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bounds</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Pengambilan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe Drone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ketinggian</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overlap</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($photos as $photo)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $photo->id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $photo->resolution }} cm/piksel</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        @php
                            $boundsArray = json_decode($photo->bounds ?? '[[0,0],[0,0]]', true);
                            $topLeft = isset($boundsArray[0]) ? implode(', ', $boundsArray[0]) : '-';
                            $bottomRight = isset($boundsArray[1]) ? implode(', ', $boundsArray[1]) : '-';
                        @endphp
                        <span class="block">TL: {{ $topLeft }}</span>
                        <span class="block">BR: {{ $bottomRight }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $photo->capture_time }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $photo->drone_type }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $photo->height }} m</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $photo->overlap }}%</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('aerial-photo.edit', $photo->id) }}" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                        <form action="{{ route('aerial-photo.destroy', $photo->id) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Apakah Anda yakin ingin menghapus foto ini?')">Hapus</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="bg-gray-100 p-4 rounded text-center">
        <p class="text-gray-600">Belum ada foto udara yang tersedia</p>
    </div>
    @endif

    @if($photos->count() > 0)
    <div class="mt-8 bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-4">Preview Foto Udara</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($photos as $photo)
                @if($photo->path)
                <div class="relative group">
                    <div class="overflow-hidden rounded-lg shadow-md aspect-square">
                        <img src="{{ asset('storage/'.$photo->path) }}"
                            alt="Foto Udara #{{ $photo->id }}"
                            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105 cursor-pointer"
                            onclick="openImagePreview('{{ asset('storage/'.$photo->path) }}')">
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-60 text-white text-xs px-2 py-1">
                        ID: {{ $photo->id }} - {{ $photo->drone_type }}
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    <div class="mt-6 text-center">
        <a href="{{ route('webgis') }}" class="text-green-700 font-bold hover:underline">Kembali ke Peta</a>
    </div>
</div>

<!-- Modal Preview Gambar -->
<div id="imagePreviewModal" class="fixed inset-0 flex items-center justify-center z-50 hidden bg-black bg-opacity-75">
    <div class="relative bg-white p-2 rounded-lg max-w-3xl max-h-[90vh] overflow-auto">
        <button onclick="closeImagePreview()" class="absolute top-2 right-2 text-gray-500 hover:text-gray-800 bg-white rounded-full w-8 h-8 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <img id="previewImage" src="" alt="Preview Foto Udara" class="max-h-[80vh] max-w-full">
    </div>
</div>

<script>
    function openImagePreview(imageUrl) {
        document.getElementById('previewImage').src = imageUrl;
        document.getElementById('imagePreviewModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Mencegah scroll pada body
    }

    function closeImagePreview() {
        document.getElementById('imagePreviewModal').classList.add('hidden');
        document.body.style.overflow = 'auto'; // Mengembalikan scroll pada body
    }

    // Menutup modal jika user mengklik di luar gambar
    document.getElementById('imagePreviewModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeImagePreview();
        }
    });

    // Menutup modal dengan tombol ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !document.getElementById('imagePreviewModal').classList.contains('hidden')) {
            closeImagePreview();
        }
    });
</script>
@endsection
