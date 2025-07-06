@extends('layouts.master')

@section('title', 'Profil Akun - Symadu')

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-auto">
    <h1 class="text-2xl font-bold text-center mb-6 text-green-700">Profil Akun</h1>

    <!-- Menampilkan pesan sukses -->
    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded-md mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Menampilkan pesan error -->
    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded-md mb-4">
            {{ session('error') }}
        </div>
    @endif

    <!-- Menampilkan validasi error -->
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded-md mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('akun.profil.update') }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <!-- Nama Lengkap -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300">
            @error('name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Username -->
        <div>
            <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
            <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300">
            @error('username')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email (Read-Only) -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300">
            @error('email')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Role (Read-Only) -->
        <div>
            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
            <input type="text" id="role" value="{{ $user->role }}" class="w-full px-4 py-2 border rounded-md bg-gray-100 text-gray-500 cursor-not-allowed" readonly>
        </div>

        <!-- Ubah Password -->
        <div>
            <label for="new-password" class="block text-sm font-medium text-gray-700">Password Baru </label>
            <div class="relative">
                <input type="password" name="password" id="new-password" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" placeholder="Buat kata sandi baru">
                <button type="button" onclick="togglePassword('new-password', 'eye-icon-new')" class="absolute inset-y-0 right-3 flex items-center text-gray-600">
                    <i id="eye-icon-new" class="fas fa-eye"></i>
                </button>
            </div>
            @error('password')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Konfirmasi Password -->
        <div>
            <label for="confirm-password" class="block text-sm font-medium text-gray-700">Konfirmasi Password</label>
            <div class="relative">
                <input type="password" name="password_confirmation" id="confirm-password" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" placeholder="Konfirmasi kata sandi">
                <button type="button" onclick="togglePassword('confirm-password', 'eye-icon-confirm')" class="absolute inset-y-0 right-3 flex items-center text-gray-600">
                    <i id="eye-icon-confirm" class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <!-- Tombol Simpan -->
        <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition">Simpan Perubahan</button>
    </form>

    <!-- Tombol Kembali -->
    <p class="text-center text-gray-600 mt-4">
        <a href="{{ session('url.intended.after.profile') ? session('url.intended.after.profile') : route('dashboard') }}" class="text-green-700 font-bold hover:underline">
            Kembali
        </a>
    </p>
</div>

<!-- JavaScript untuk Toggle Password -->
<script>
    function togglePassword(fieldId, iconId) {
        let field = document.getElementById(fieldId);
        let icon = document.getElementById(iconId);
        if (field.type === "password") {
            field.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            field.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
</script>

@endsection
