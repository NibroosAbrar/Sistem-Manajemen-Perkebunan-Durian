@extends('layouts.master')

@section('title', 'Upload Shapefile Baru - Symadu')

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-center mb-6 text-green-700">Upload Shapefile Baru</h1>

    <div class="mb-6">
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        Upload file KML (.kml) atau Shapefile (.shp dalam format .zip). File ini akan digunakan untuk membuat geometri blok kebun atau pohon secara massal.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('shapefile.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Shapefile <span class="text-red-500">*</span></label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500">
            <p class="text-xs text-gray-500 mt-1">Nama untuk mengidentifikasi shapefile ini</p>
        </div>

        <div>
            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Tipe Data <span class="text-red-500">*</span></label>
            <select name="type" id="type" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500">
                <option value="" disabled {{ !request('type') && !old('type') ? 'selected' : '' }}>-- Pilih Tipe Data --</option>
                <option value="plantation" {{ (request('type') == 'plantation' || old('type') == 'plantation') ? 'selected' : '' }}>Blok Kebun</option>
                <option value="tree" {{ (request('type') == 'tree' || old('type') == 'tree') ? 'selected' : '' }}>Pohon</option>
            </select>
            <p class="text-xs text-gray-500 mt-1">Jenis data yang akan dibuat dari shapefile ini</p>
        </div>

        <div>
            <label for="file" class="block text-sm font-medium text-gray-700 mb-1">File Shapefile <span class="text-red-500">*</span></label>
            <input type="file" name="file" id="file" required accept=".kml,.zip,.shp" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500">
            <p class="text-xs text-gray-500 mt-1">Format yang didukung: .kml (dari Google Earth atau aplikasi GIS), .zip (untuk .shp)</p>
            <div class="bg-blue-50 border-l-4 border-blue-400 p-3 mt-2">
                <p class="text-xs text-blue-700">
                    <strong>Tips file KML:</strong> Pastikan file KML berisi Placemark dengan geometri Polygon, LineString, atau Point. 
                    Jika menggunakan Google Earth, export area sebagai KML dengan cara klik kanan pada area → Save Place As → pilih format .kml
                </p>
            </div>
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
            <textarea name="description" id="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500">{{ old('description') }}</textarea>
            <p class="text-xs text-gray-500 mt-1">Deskripsi singkat tentang shapefile ini (opsional)</p>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('shapefile.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded">
                Batal
            </a>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded">
                Upload
            </button>
        </div>
    </form>
</div>
@endsection 