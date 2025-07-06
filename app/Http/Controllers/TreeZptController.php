<?php

namespace App\Http\Controllers;

use App\Models\Tree;
use App\Models\TreeZpt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TreeZptController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Ambil tree_id dari parameter URL atau dari query string
        $treeId = $request->route('treeId') ?? $request->input('tree_id');
        
        \Log::info('ZPT Index Request:', ['tree_id' => $treeId]);
        
        if (!$treeId) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter tree_id diperlukan'
            ], 400);
        }
        
        $zpts = TreeZpt::where('tree_id', $treeId)
            ->orderBy('tanggal_aplikasi', 'desc')
            ->get();
        
        \Log::info('ZPT Records Found:', ['count' => $zpts->count()]);
            
        return response()->json([
            'success' => true,
            'data' => $zpts
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Log request data untuk debugging
        \Log::info('ZPT Store Request:', $request->all());
        
        $validator = Validator::make($request->all(), [
            'tree_id' => 'required|exists:trees,id',
            'tanggal_aplikasi' => 'nullable|date',
            'nama_zpt' => 'nullable|string|max:255',
            'merek' => 'nullable|string|max:255',
            'jenis_senyawa' => 'nullable|string|in:Alami,Sintetis',
            'konsentrasi' => 'nullable|string|max:255',
            'volume_larutan' => 'nullable|numeric',
            'unit' => 'nullable|string|max:10',
            'fase_pertumbuhan' => 'nullable|string|max:255',
            'metode_aplikasi' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            \Log::error('ZPT Validation Failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $zpt = TreeZpt::create($request->all());
        \Log::info('ZPT Created:', $zpt->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Data ZPT berhasil disimpan',
            'data' => $zpt
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $zpt = TreeZpt::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $zpt
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Log request data untuk debugging
        \Log::info('ZPT Update Request:', ['id' => $id, 'data' => $request->all()]);
        
        $validator = Validator::make($request->all(), [
            'tanggal_aplikasi' => 'nullable|date',
            'nama_zpt' => 'nullable|string|max:255',
            'merek' => 'nullable|string|max:255',
            'jenis_senyawa' => 'nullable|string|in:Alami,Sintetis',
            'konsentrasi' => 'nullable|string|max:255',
            'volume_larutan' => 'nullable|numeric',
            'unit' => 'nullable|string|max:10',
            'fase_pertumbuhan' => 'nullable|string|max:255',
            'metode_aplikasi' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $zpt = TreeZpt::findOrFail($id);
        $zpt->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Data ZPT berhasil diperbarui',
            'data' => $zpt
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $zpt = TreeZpt::findOrFail($id);
        $zpt->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data ZPT berhasil dihapus'
        ]);
    }
} 