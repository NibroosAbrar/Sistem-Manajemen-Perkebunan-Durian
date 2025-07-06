{{-- filepath: /c:/laragon/www/laravel11/resources/views/pages/webgis.blade.php --}}
@extends('layouts.app')

@section('title', 'Pengguna - Symadu')
@section('header-title', 'Manajemen Pengguna')

@section('content')

<div id="loading-screen">
</div>

<div class="w-full flex flex-col h-screen overflow-y-auto">
    <!-- Konten -->
    <div class="px-6 py-6" x-data="{ showDeleteModal: false, userIdToDelete: null, showEditConfirmModal: false }">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-bold text-gray-800">Daftar Pengguna</h1>
            <form method="GET" action="{{ route('akun') }}" class="flex flex-col md:flex-row gap-4 items-center">
                <input type="text" name="search" placeholder="Cari nama atau username..." value="{{ request('search') }}" class="border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                <select name="role_id" class="border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    <option value="">Semua Role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-4 rounded-md shadow-sm transition-all duration-200 transform hover:scale-105">Filter</button>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <div class="overflow-x-auto rounded-lg shadow">
                    <table class="w-full border-collapse bg-white">
                        <thead>
                            <tr class="bg-emerald-800 text-white">
                                <th class="p-4 text-center font-semibold text-base capitalize">Nama</th>
                                <th class="p-4 text-center font-semibold text-base capitalize">Username</th>
                                <th class="p-4 text-center font-semibold text-base capitalize">Email</th>
                                <th class="p-4 text-center font-semibold text-base capitalize">Role</th>
                                <th class="p-4 text-center font-semibold text-base capitalize">Tanggal Dibuat</th>
                                <th class="p-4 text-center font-semibold text-base capitalize">Tanggal Diperbarui</th>
                                <th class="p-4 text-center font-semibold text-base capitalize w-40">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($users as $user)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="p-4 text-center text-gray-700 text-base">{{ $user->name }}</td>
                                <td class="p-4 text-center text-gray-700 text-base">{{ $user->username ?? '-' }}</td>
                                <td class="p-4 text-center text-gray-700 text-base">{{ $user->email }}</td>
                                <td class="p-4 text-center">
                                    <form id="edit-form-{{ $user->id }}" action="{{ route('akun.update', $user->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="flex items-center justify-center gap-2">
                                            <select name="role_id"
                                                class="border rounded-md p-2 focus:ring-2 focus:ring-emerald-500 focus:border-transparent
                                                {{ $user->role_id == 1 ? 'bg-emerald-100 text-emerald-800 font-medium' : 'bg-white text-gray-700' }}">
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>
                                                        {{ $role->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="button" @click="showEditConfirmModal = true; $store.editUserId = {{ $user->id }}"
                                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-3 rounded-md shadow-sm transition-all duration-200 transform hover:scale-105 flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M5 4a1 1 0 00-1 1v10a1 1 0 001 1h10a1 1 0 001-1V5a1 1 0 00-1-1H5zm7 11h-4v-3h4v3zm0-4h-4V8h4v3zm1-9H4a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V7l-5-5z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </form>
                                </td>
                                <td class="p-4 text-center text-gray-700 text-base">{{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : '-' }}</td>
                                <td class="p-4 text-center text-gray-700 text-base">{{ $user->updated_at ? $user->updated_at->format('d/m/Y H:i') : '-' }}</td>
                                <td class="p-4">
                                    <div class="flex space-x-2 justify-center">
                                        <button @click="showDeleteModal = true; userIdToDelete = {{ $user->id }}"
                                                class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-md shadow-sm transition-all duration-200 transform hover:scale-105 flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Edit -->
        <div x-show="showEditConfirmModal"
             class="fixed inset-0 flex items-center justify-center z-[9999]"
             x-cloak>
            <div class="fixed inset-0 bg-black opacity-50" @click="showEditConfirmModal = false"></div>

            <div class="relative bg-white w-96 rounded-lg shadow-lg z-[10000]">
                <div class="p-6">
                    <h2 class="text-xl font-bold mb-4">Konfirmasi Perubahan</h2>
                    <p class="mb-6">Apakah Anda yakin ingin menyimpan perubahan role ini?</p>

                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="showEditConfirmModal = false"
                            class="px-6 py-2 bg-gray-200 hover:bg-gray-300 rounded-md font-medium">
                            Batal
                        </button>
                        <button type="button" @click="document.getElementById('edit-form-' + $store.editUserId).submit(); showEditConfirmModal = false;"
                            class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md font-medium">
                            Simpan
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus -->
        <div x-show="showDeleteModal"
             class="fixed inset-0 flex items-center justify-center z-[9999]"
             x-cloak>
            <div class="fixed inset-0 bg-black opacity-50" @click="showDeleteModal = false"></div>

            <div class="relative bg-white w-96 rounded-lg shadow-lg z-[10000]">
                <div class="p-6">
                    <h2 class="text-xl font-bold mb-4">Konfirmasi Hapus</h2>
                    <p class="mb-6">Apakah Anda yakin ingin menghapus pengguna ini?</p>

                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="showDeleteModal = false"
                            class="px-6 py-2 bg-gray-200 hover:bg-gray-300 rounded-md font-medium">
                            Batal
                        </button>
                        <form :action="'/akun/' + userIdToDelete" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="px-6 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md font-medium">
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Whitespace di bawah halaman -->
    <div class="py-16"></div>
</div>

@endsection

@section('scripts')
@parent
<script>
    // Inisialisasi store untuk menyimpan ID pengguna yang sedang diedit
    document.addEventListener('alpine:init', () => {
        Alpine.store('editUserId', null);
    });
</script>
@endsection
