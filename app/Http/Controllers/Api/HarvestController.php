<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Harvest;
use Illuminate\Http\Request;

class HarvestController extends Controller
{
    public function index()
    {
        try {
            $harvests = Harvest::with('tree')->get();
            return response()->json([
                'success' => true,
                'data' => $harvests
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data panen: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tree_id' => 'required|exists:trees,id',
                'fruit_count' => 'required|integer',
                'total_weight' => 'required|numeric',
                'average_weight_per_fruit' => 'required|numeric',
                'fruit_condition' => 'required|string',
                'tanggal_panen' => 'required|date'
            ]);

            $harvest = Harvest::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Data panen berhasil disimpan',
                'data' => $harvest
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data panen: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $harvest = Harvest::with('tree')->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $harvest
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data panen tidak ditemukan'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $harvest = Harvest::findOrFail($id);

            $validated = $request->validate([
                'tree_id' => 'exists:trees,id',
                'fruit_count' => 'integer',
                'total_weight' => 'numeric',
                'average_weight_per_fruit' => 'numeric',
                'fruit_condition' => 'string',
                'tanggal_panen' => 'date'
            ]);

            $harvest->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Data panen berhasil diperbarui',
                'data' => $harvest
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data panen: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $harvest = Harvest::findOrFail($id);
            $harvest->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data panen berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data panen: ' . $e->getMessage()
            ], 500);
        }
    }
}
