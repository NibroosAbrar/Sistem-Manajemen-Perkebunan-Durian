@extends('layouts.app')

@section('title', 'Tambah Foto Udara - Symadu')

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-auto">
    <h1 class="text-2xl font-bold text-center mb-6 text-green-700">Upload Foto Udara</h1>
    <form action="{{ route('aerial-photo.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <div class="form-group">
            <label for="aerial_photo" class="block text-sm font-medium text-gray-700">Foto Udara</label>
            <input type="file" name="aerial_photo" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300">
        </div>
        <div class="form-group">
            <label for="resolution" class="block text-sm font-medium text-gray-700">Resolusi (cm)<span class="text-red-500">*</span></label>
            <input type="number" name="resolution" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" required>
        </div>
        <div class="form-group">
            <label for="capture_time" class="block text-sm font-medium text-gray-700">Waktu Pengambilan<span class="text-red-500">*</span></label>
            <input type="datetime-local" name="capture_time" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" required>
        </div>
        <div class="form-group">
            <label for="drone_type" class="block text-sm font-medium text-gray-700">Tipe Drone<span class="text-red-500">*</span></label>
            <input type="text" name="drone_type" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" required>
        </div>
        <div class="form-group">
            <label for="height" class="block text-sm font-medium text-gray-700">Ketinggian (meter)<span class="text-red-500">*</span></label>
            <input type="number" name="height" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" required>
        </div>
        <div class="form-group">
            <label for="overlap" class="block text-sm font-medium text-gray-700">Overlap (%)<span class="text-red-500">*</span></label>
            <input type="number" name="overlap" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" required>
        </div>
        <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition">Simpan</button>
    </form>
    <p class="text-center text-gray-600 mt-4">
        <a href="{{ session('previous_url', route('webgis')) }}" class="text-green-700 font-bold hover:underline">Kembali</a>
    </p>
</div>
@endsection
