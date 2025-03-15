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

    <form action="{{ route('akun.profil.update') }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <!-- Nama Lengkap -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Nama Lengkap<span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" required>
            @error('name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email (Read-Only) -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" value="{{ $user->email }}" class="w-full px-4 py-2 border rounded-md bg-gray-100 text-gray-500 cursor-not-allowed" readonly>
        </div>

        <!-- Role (Read-Only) -->
        <div>
            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
            <input type="text" value="{{ $user->role }}" class="w-full px-4 py-2 border rounded-md bg-gray-100 text-gray-500 cursor-not-allowed" readonly>
        </div>

        <!-- Ubah Password -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Password Baru (Opsional)</label>
            <div class="relative">
                <div class="relative">
                    <input type="password" name="password" id="new-password" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" placeholder="Buat kata sandi baru">
                    <button type="button" onclick="togglePassword('new-password', 'eye-icon-new')" class="absolute inset-y-0 right-3 flex items-center text-gray-600">
                        <i id="eye-icon-new" class="fas fa-eye"></i>
                    </button>
                </div>
                <button type="button" onclick="togglePassword('new-password', 'eye-icon-new')" class="absolute inset-y-0 right-3 flex items-center text-gray-600">
                    <i id="eye-icon-new" class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <!-- Konfirmasi Password -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Konfirmasi Password</label>
            <div class="relative">
                <div class="relative">
                    <input type="password" name="password_confirmation" id="confirm-password" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:ring-green-300" placeholder="Konfirmasi kata sandi">
                    <button type="button" onclick="togglePassword('confirm-password', 'eye-icon-confirm')" class="absolute inset-y-0 right-3 flex items-center text-gray-600">
                        <i id="eye-icon-confirm" class="fas fa-eye"></i>
                    </button>
                </div>

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
        <a href="{{ session('previous_url', route('webgis')) }}" class="text-green-700 font-bold hover:underline">
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
