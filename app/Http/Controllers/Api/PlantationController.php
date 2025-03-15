<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plantation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PlantationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $plantations = Plantation::all();
            return response()->json([
                'success' => true,
                'data' => $plantations
            ]);
        } catch (\Exception $e) {
            Log::error('Error in PlantationController@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data blok kebun',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Tidak digunakan untuk API
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('Received plantation data:', $request->all());

        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'geometry' => 'required|string',
            'luas_area' => 'nullable|numeric',
            'tipe_tanah' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Buat blok kebun baru
            $plantation = new Plantation();
            $plantation->name = $request->name;
            $plantation->geometry = $request->geometry;
            $plantation->luas_area = $request->luas_area;
            $plantation->tipe_tanah = $request->tipe_tanah;
            $plantation->user_id = Auth::id();

            $plantation->save();

            Log::info('Plantation created successfully:', ['id' => $plantation->id]);

            return response()->json([
                'success' => true,
                'message' => 'Blok kebun berhasil disimpan',
                'data' => $plantation
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating plantation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan blok kebun: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $plantation = Plantation::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $plantation
            ]);
        } catch (\Exception $e) {
            Log::error('Error in PlantationController@show: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Blok kebun tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Tidak digunakan untuk API
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Log::info('Updating plantation:', ['id' => $id, 'data' => $request->all()]);

        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'geometry' => 'required|string',
            'luas_area' => 'nullable|numeric',
            'tipe_tanah' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $plantation = Plantation::findOrFail($id);

            // Update data
            $plantation->name = $request->name;
            $plantation->geometry = $request->geometry;
            $plantation->luas_area = $request->luas_area;
            $plantation->tipe_tanah = $request->tipe_tanah;

            $plantation->save();

            Log::info('Plantation updated successfully:', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Blok kebun berhasil diperbarui',
                'data' => $plantation
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating plantation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui blok kebun: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $plantation = Plantation::findOrFail($id);
            $plantation->delete();

            Log::info('Plantation deleted successfully:', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Blok kebun berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting plantation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus blok kebun',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
