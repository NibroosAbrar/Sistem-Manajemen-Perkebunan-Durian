<?php

namespace App\Http\Controllers;

use App\Models\Tree;
use App\Models\TreeGrowth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TreeGrowthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Ambil tree_id dari parameter URL atau dari query string
        $treeId = $request->route('treeId') ?? $request->input('tree_id');

        if (!$treeId) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter tree_id diperlukan'
            ], 400);
        }

        $growths = TreeGrowth::where('tree_id', $treeId)
            ->orderBy('tanggal', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $growths
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tree_id' => 'required|exists:trees,id',
            'tanggal' => 'required|date',
            'fase' => 'nullable|string|max:255',
            'tinggi' => 'nullable|numeric',
            'diameter' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $growth = TreeGrowth::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Data pertumbuhan berhasil disimpan',
            'data' => $growth
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $growth = TreeGrowth::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $growth
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'nullable|date',
            'fase' => 'nullable|string|max:255',
            'tinggi' => 'nullable|numeric',
            'diameter' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $growth = TreeGrowth::findOrFail($id);
        $growth->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Data pertumbuhan berhasil diperbarui',
            'data' => $growth
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $growth = TreeGrowth::findOrFail($id);
        $growth->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data pertumbuhan berhasil dihapus'
        ]);
    }
}
