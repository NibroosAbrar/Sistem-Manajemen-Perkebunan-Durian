@extends('layouts.master')

@section('title', 'Verifikasi Email')

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
    <h1 class="text-2xl font-bold text-center mb-6 text-green-700">Verifikasi Email Anda</h1>

    <p class="text-gray-600 text-center">
        Kami telah mengirimkan tautan verifikasi ke email Anda. Silakan periksa email Anda dan klik tautan yang diberikan.
    </p>

    <form method="POST" action="{{ route('verification.send') }}" class="mt-4">
        @csrf
        <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition">
            Kirim Ulang Email Verifikasi
        </button>
    </form>
</div>
@endsection
