<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TreePesticide;
use Illuminate\Http\Request;

class TreePesticideController extends Controller
{
    public function index()
    {
        try {
            $pesticides = TreePesticide::with('tree')->get();
            return response()->json([
                'success' => true,
                'data' => $pesticides
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pestisida: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tree_id' => 'required|exists:trees,id',
                'nama_pestisida' => 'required|string',
                'jenis_pestisida' => 'required|string',
                'bentuk_pestisida' => 'nullable|string',
                'dosis' => 'required|numeric',
                'tanggal_pestisida' => 'required|date',
                'unit' => 'required|in:ml/tanaman,l/tanaman,g/tanaman'
            ]);

            $pesticide = TreePesticide::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Data pestisida berhasil disimpan',
                'data' => $pesticide
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data pestisida: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $pesticide = TreePesticide::with('tree')->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $pesticide
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data pestisida tidak ditemukan'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $pesticide = TreePesticide::findOrFail($id);

            $validated = $request->validate([
                'tree_id' => 'exists:trees,id',
                'nama_pestisida' => 'string',
                'jenis_pestisida' => 'string',
                'bentuk_pestisida' => 'nullable|string',
                'dosis' => 'numeric',
                'tanggal_pestisida' => 'date',
                'unit' => 'in:ml/tanaman,l/tanaman,g/tanaman'
            ]);

            $pesticide->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Data pestisida berhasil diperbarui',
                'data' => $pesticide
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data pestisida: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $pesticide = TreePesticide::findOrFail($id);
            $pesticide->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data pestisida berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data pestisida: ' . $e->getMessage()
            ], 500);
        }
    }
}
