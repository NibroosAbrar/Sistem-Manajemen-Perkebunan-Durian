@extends('layouts.master')

@section('title', 'Edit Shapefile - Symadu')

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-center mb-6 text-green-700">Edit Shapefile</h1>

    <form action="{{ route('shapefile.update', $shapefile->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

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
            <input type="text" name="name" id="name" value="{{ old('name', $shapefile->name) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500">
        </div>

        <div>
            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Tipe Data <span class="text-red-500">*</span></label>
            <select name="type" id="type" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500">
                <option value="plantation" {{ (old('type', $shapefile->type) == 'plantation') ? 'selected' : '' }}>Blok Kebun</option>
                <option value="tree" {{ (old('type', $shapefile->type) == 'tree') ? 'selected' : '' }}>Pohon</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">File Shapefile Saat Ini</label>
            @if($shapefile->file_path)
                <div class="mt-1 flex items-center">
                    <svg class="h-5 w-5 text-gray-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span class="text-sm text-gray-600">
                        {{ basename($shapefile->file_path) }}
                    </span>
                </div>
            @else
                <div class="mt-1 text-sm text-gray-500 italic">Tidak ada file</div>
            @endif
        </div>

        <div>
            <label for="file" class="block text-sm font-medium text-gray-700 mb-1">Ganti File Shapefile</label>
            <input type="file" name="file" id="file" accept=".kml,.zip,.shp" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500">
            <p class="text-xs text-gray-500 mt-1">Biarkan kosong jika tidak ingin mengganti file. Format yang didukung: .kml, .zip (untuk .shp)</p>
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
            <textarea name="description" id="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500">{{ old('description', $shapefile->description) }}</textarea>
        </div>

        @if($shapefile->geometry)
            <div class="rounded-md bg-green-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">
                            Shapefile ini telah diproses. Mengganti file akan memerlukan pemrosesan kembali.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div class="flex justify-end space-x-3">
            <a href="{{ route('shapefile.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded">
                Batal
            </a>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded">
                Update
            </button>
        </div>
    </form>
</div>
@endsection 