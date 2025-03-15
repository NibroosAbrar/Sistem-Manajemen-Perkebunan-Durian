<?php

namespace App\Http\Controllers;

use App\Models\Tree;
use App\Models\TreeHealthProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;

class TreeHealthProfileController extends Controller
{
    /**
     * Menampilkan daftar riwayat kesehatan untuk pohon tertentu
     */
    public function index($treeId)
    {
        try {
            $tree = Tree::findOrFail($treeId);
            $healthProfiles = $tree->healthProfiles()->orderBy('tanggal_pemeriksaan', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $healthProfiles,
                'tree' => $tree
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data riwayat kesehatan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menyimpan riwayat kesehatan baru
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            
            // Validasi input
            $request->validate([
                'tree_id' => 'required|exists:trees,id',
                'tanggal_pemeriksaan' => 'required|date',
                'status_kesehatan' => 'required|in:Sehat,Stres,Terinfeksi,Mati',
                'gejala' => 'nullable|string',
                'diagnosis' => 'nullable|string',
                'tindakan_penanganan' => 'nullable|string',
                'catatan_tambahan' => 'nullable|string',
                'foto_kondisi' => 'nullable|image|max:2048'
            ]);
            
            // Upload foto jika ada
            $fotoPath = null;
            if ($request->hasFile('foto_kondisi')) {
                $fotoPath = $request->file('foto_kondisi')->store('health-profiles', 'public');
            }
            
            // Buat riwayat kesehatan baru
            $healthProfile = new TreeHealthProfile([
                'tree_id' => $request->tree_id,
                'tanggal_pemeriksaan' => $request->tanggal_pemeriksaan,
                'status_kesehatan' => $request->status_kesehatan,
                'gejala' => $request->gejala,
                'diagnosis' => $request->diagnosis,
                'tindakan_penanganan' => $request->tindakan_penanganan,
                'catatan_tambahan' => $request->catatan_tambahan,
                'foto_kondisi' => $fotoPath
            ]);
            
            $healthProfile->save();
            
            // Update status kesehatan pohon berdasarkan tanggal pemeriksaan terbaru
            $latestProfile = TreeHealthProfile::where('tree_id', $request->tree_id)
                ->orderBy('tanggal_pemeriksaan', 'desc')
                ->first();
                
            if ($latestProfile) {
                $tree = Tree::findOrFail($request->tree_id);
                $tree->health_status = $latestProfile->status_kesehatan;
                $tree->save();
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Riwayat kesehatan berhasil disimpan',
                'data' => $healthProfile
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan riwayat kesehatan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan detail riwayat kesehatan
     */
    public function show($id)
    {
        try {
            $healthProfile = TreeHealthProfile::find($id);
            
            if (!$healthProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data riwayat kesehatan tidak ditemukan'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $healthProfile
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail riwayat kesehatan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mengupdate riwayat kesehatan
     */
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            
            $healthProfile = TreeHealthProfile::findOrFail($id);
            
            // Validasi input
            $request->validate([
                'tanggal_pemeriksaan' => 'required|date',
                'status_kesehatan' => 'required|in:Sehat,Stres,Terinfeksi,Mati',
                'gejala' => 'nullable|string',
                'diagnosis' => 'nullable|string',
                'tindakan_penanganan' => 'nullable|string',
                'catatan_tambahan' => 'nullable|string',
                'foto_kondisi' => 'nullable|image|max:2048'
            ]);
            
            // Hapus foto lama jika ada foto baru
            if ($request->hasFile('foto_kondisi') && $healthProfile->foto_kondisi) {
                Storage::disk('public')->delete($healthProfile->foto_kondisi);
            }
            
            // Upload foto baru jika ada
            if ($request->hasFile('foto_kondisi')) {
                $fotoPath = $request->file('foto_kondisi')->store('health-profiles', 'public');
                $healthProfile->foto_kondisi = $fotoPath;
            }
            
            // Update data
            $healthProfile->tanggal_pemeriksaan = $request->tanggal_pemeriksaan;
            $healthProfile->status_kesehatan = $request->status_kesehatan;
            $healthProfile->gejala = $request->gejala;
            $healthProfile->diagnosis = $request->diagnosis;
            $healthProfile->tindakan_penanganan = $request->tindakan_penanganan;
            $healthProfile->catatan_tambahan = $request->catatan_tambahan;
            
            $healthProfile->save();
            
            // Update status kesehatan pohon berdasarkan tanggal pemeriksaan terbaru
            $latestProfile = TreeHealthProfile::where('tree_id', $healthProfile->tree_id)
                ->orderBy('tanggal_pemeriksaan', 'desc')
                ->first();
                
            if ($latestProfile) {
                $tree = Tree::findOrFail($healthProfile->tree_id);
                $tree->health_status = $latestProfile->status_kesehatan;
                $tree->save();
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Riwayat kesehatan berhasil diupdate',
                'data' => $healthProfile
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate riwayat kesehatan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menghapus riwayat kesehatan
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            // Cek apakah data ada
            $healthProfile = TreeHealthProfile::find($id);
            
            if (!$healthProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data riwayat kesehatan tidak ditemukan'
                ], 404);
            }
            
            $treeId = $healthProfile->tree_id;
            
            // Hapus foto jika ada
            if ($healthProfile->foto_kondisi) {
                Storage::disk('public')->delete($healthProfile->foto_kondisi);
            }
            
            $healthProfile->delete();
            
            // Update status kesehatan pohon berdasarkan riwayat terbaru
            $latestProfile = TreeHealthProfile::where('tree_id', $treeId)
                ->orderBy('tanggal_pemeriksaan', 'desc')
                ->first();
                
            if ($latestProfile) {
                $tree = Tree::findOrFail($treeId);
                $tree->health_status = $latestProfile->status_kesehatan;
                $tree->save();
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Riwayat kesehatan berhasil dihapus'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus riwayat kesehatan: ' . $e->getMessage()
            ], 500);
        }
    }
} 