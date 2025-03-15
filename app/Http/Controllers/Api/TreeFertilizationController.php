<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TreeFertilization;
use Illuminate\Http\Request;

class TreeFertilizationController extends Controller
{
    public function index()
    {
        try {
            $fertilizations = TreeFertilization::with('tree')->get();
            return response()->json([
                'success' => true,
                'data' => $fertilizations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pemupukan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tree_id' => 'required|exists:trees,id',
                'nama_pupuk' => 'required|string',
                'jenis_pupuk' => 'required|string',
                'dosis_pupuk' => 'required|numeric',
                'tanggal_pemupukan' => 'required|date'
            ]);

            $fertilization = TreeFertilization::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Data pemupukan berhasil disimpan',
                'data' => $fertilization
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data pemupukan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $fertilization = TreeFertilization::with('tree')->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $fertilization
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data pemupukan tidak ditemukan'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $fertilization = TreeFertilization::findOrFail($id);

            $validated = $request->validate([
                'tree_id' => 'exists:trees,id',
                'nama_pupuk' => 'string',
                'jenis_pupuk' => 'string',
                'dosis_pupuk' => 'numeric',
                'tanggal_pemupukan' => 'date'
            ]);

            $fertilization->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Data pemupukan berhasil diperbarui',
                'data' => $fertilization
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data pemupukan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $fertilization = TreeFertilization::findOrFail($id);
            $fertilization->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data pemupukan berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data pemupukan: ' . $e->getMessage()
            ], 500);
        }
    }
}
