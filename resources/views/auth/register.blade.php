@extends('layouts.master')

@section('title', 'Buat Akun - DuriGeo')

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
    <h1 class="text-2xl font-bold text-center mb-6 text-green-700">Buat Akun di DuriGeo</h1>

    <!-- Menampilkan pesan sukses setelah registrasi -->
    @if (session('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded-md mb-4">
            {{ session('message') }}
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Nama Lengkap<span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" placeholder="Masukkan nama lengkap" required>
            @error('name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email<span class="text-red-500">*</span></label>
            <input type="email" name="email" value="{{ old('email') }}" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" placeholder="Masukkan email" required>
            @error('email')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Kata Sandi<span class="text-red-500">*</span></label>
            <input type="password" name="password" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" placeholder="Buat kata sandi" required>
            @error('password')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Konfirmasi Kata Sandi<span class="text-red-500">*</span></label>
            <input type="password" name="password_confirmation" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" placeholder="Konfirmasi kata sandi" required>
        </div>

        <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition">Buat Akun</button>
    </form>

    <p class="text-center text-gray-600 mt-4">Sudah punya akun?
        <a href="{{ route('login') }}" class="text-green-700 font-bold">Masuk</a>
    </p>
</div>
@endsection
