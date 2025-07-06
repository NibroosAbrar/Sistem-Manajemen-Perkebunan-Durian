@extends('layouts.app')

@section('title', 'Daftar Digitasi Pohon Durian')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Daftar Digitasi Pohon Durian</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Digitasi Pohon</li>
    </ol>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <span><i class="fas fa-table me-1"></i> Daftar Digitasi</span>
                <a href="{{ route('digitasi.create') }}" class="btn btn-primary">Tambah Baru</a>
            </div>
        </div>
        <div class="card-body">
            <table id="datatablesSimple">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Perkebunan</th>
                        <th>Foto Udara</th>
                        <th>Jumlah Pohon</th>
                        <th>Status</th>
                        <th>Tgl Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($digitasiList as $index => $digitasi)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $digitasi->name }}</td>
                        <td>{{ $digitasi->plantation->name ?? '-' }}</td>
                        <td>{{ $digitasi->aerialPhoto->id ?? '-' }}</td>
                        <td>{{ $digitasi->tree_count }}</td>
                        <td>
                            @if($digitasi->is_processed)
                                <span class="badge bg-success">Selesai</span>
                            @else
                                <span class="badge bg-warning">Belum Diproses</span>
                            @endif
                            
                            @if($digitasi->is_imported_to_trees)
                                <span class="badge bg-info">Diimpor ke Pohon</span>
                            @endif
                        </td>
                        <td>{{ $digitasi->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <a href="{{ route('digitasi.show', $digitasi->id) }}" class="btn btn-sm btn-info" title="Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('digitasi.edit', $digitasi->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('digitasi.destroy', $digitasi->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus data ini?')" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            
                            @if($digitasi->is_processed && !$digitasi->is_imported_to_trees)
                            <form action="{{ route('digitasi.import-to-trees', $digitasi->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Impor data pohon ke tabel Trees?')" title="Impor ke Trees">
                                    <i class="fas fa-file-import"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection 