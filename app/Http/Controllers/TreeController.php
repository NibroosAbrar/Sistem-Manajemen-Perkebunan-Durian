<?php

namespace App\Http\Controllers;

use App\Models\Tree;
use App\Models\Plantation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\TreeFertilization;
use App\Models\TreePesticide;
use App\Models\Harvest;
use Carbon\Carbon;

class TreeController extends Controller
{
    public function index()
    {
        $plantations = Plantation::all();
        return view('pages.webgis', compact('plantations'));
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Generate ID alfanumerik pohon
            $id = $request->id;
            $plantationId = $request->plantation_id;

            if (empty($plantationId)) {
                throw new Exception('ID Kebun tidak valid');
            }

            // Jika tidak ada ID yang dikirim, generate ID baru
            if (empty($id)) {
                // Ambil ID pohon terbaru yang berupa angka saja
                $latestNumericId = Tree::where('id', 'regexp', '^[0-9]+$')
                    ->orderByRaw('CAST(id AS UNSIGNED) DESC')
                    ->value('id');

                // Jika tidak ada ID numerik, mulai dari 1
                $newNumericId = $latestNumericId ? (int)$latestNumericId + 1 : 1;
                $id = (string)$newNumericId;
            } else {
                // Pastikan ID dalam huruf kapital jika berisi huruf
                $id = strtoupper($id);
            }

            // Periksa apakah ID sudah digunakan di plantation yang sama
            $exists = Tree::where('id', $id)
                        ->where('plantation_id', $plantationId)
                        ->exists();

            if ($exists) {
                throw new Exception('ID pohon sudah digunakan di blok kebun yang sama');
            }

            // Jika ID berisi angka dan huruf, periksa apakah formatnya valid
            if (preg_match('/^(\d+)([A-Z]+)$/', $id)) {
                \Log::info('Store - Valid alphanumeric ID format: ' . $id);
            }

            $tree = new Tree();
            // Set ID
            $tree->id = $id;
            $tree->plantation_id = $request->plantation_id;
            $tree->varietas = $request->varietas;
            $tree->tahun_tanam = $request->tahun_tanam ?: null;
            $tree->health_status = $request->health_status;
            $tree->fase = $request->fase;
            $tree->sumber_bibit = $request->sumber_bibit;

            // Validate and clean WKT format for canopy
            $canopy = trim($request->canopy_geometry);

            if (empty($canopy)) {
                throw new Exception('Canopy geometry is required for new trees');
            }

            // Log untuk debugging
            \Log::info('Store - Original WKT: ' . $canopy);
            \Log::info('Store - Shape Type: ' . ($request->shape_type ?? 'Not specified'));

            // Normalisasi format WKT
            $canopy = preg_replace('/\s+/', ' ', $canopy); // Bersihkan spasi berlebih
            $canopy = strtoupper($canopy); // Konversi ke uppercase untuk konsistensi

            // Validasi format WKT berdasarkan tipe bentuk
            $isValid = false;

            // Cek apakah ini POINT
            if (preg_match('/^POINT\s*\(.+\)$/i', $canopy)) {
                $isValid = true;
                \Log::info('Store - Valid POINT format: ' . $canopy);
            }
            // Cek apakah ini POLYGON
            else if (preg_match('/^POLYGON\s*\(\s*\(.+\)\s*\)$/i', $canopy)) {
                $isValid = true;
                \Log::info('Store - Valid POLYGON format: ' . $canopy);
            }
            // Coba perbaiki format POLYGON jika tidak sesuai
            else if (preg_match('/^POLYGON\s*\(\s*[^()]+\s*\)$/i', $canopy)) {
                $canopy = preg_replace('/^(POLYGON\s*\()(.+)(\))$/i', '$1($2)$3', $canopy);
                $isValid = true;
                \Log::info('Store - Fixed POLYGON format (added inner parentheses): ' . $canopy);
            }

            if (!$isValid) {
                \Log::error('Store - Invalid WKT format: ' . $canopy);
                throw new Exception('Invalid geometry format. Expected format: POLYGON((x y, x y, ...)) or POINT(x y)');
            }

            try {
            // Set geometry using raw SQL to ensure proper SRID
            $tree->canopy_geometry = DB::raw("ST_GeomFromText('$canopy', 4326)");
            $tree->save();
                \Log::info('Geometry saved successfully');
            } catch (\Exception $e) {
                \Log::error('Error saving geometry: ' . $e->getMessage());
                throw new Exception('Error saving geometry: ' . $e->getMessage());
            }

            // Simpan data pemupukan jika ada
            if ($request->filled('nama_pupuk') && $request->filled('jenis_pupuk') && $request->filled('dosis_pupuk')) {
                $tree->fertilizations()->create([
                    'nama_pupuk' => $request->nama_pupuk,
                    'jenis_pupuk' => $request->jenis_pupuk,
                    'dosis_pupuk' => $request->dosis_pupuk,
                ]);
            }

            // Simpan data pestisida jika ada
            if ($request->filled('nama_pestisida') || $request->filled('jenis_pestisida') || $request->filled('dosis')) {
                $tree->pesticides()->create([
                    'nama_pestisida' => $request->nama_pestisida,
                    'jenis_pestisida' => $request->jenis_pestisida,
                    'dosis' => $request->dosis,
                ]);
            }

            // Simpan data panen jika ada dan semua field yang diperlukan diisi
            if ($request->filled('fruit_count') && $request->filled('total_weight') &&
                $request->filled('average_weight_per_fruit') && $request->filled('fruit_condition')) {
                $tree->harvests()->create([
                    'fruit_count' => $request->fruit_count,
                    'total_weight' => $request->total_weight,
                    'average_weight_per_fruit' => $request->average_weight_per_fruit,
                    'fruit_condition' => $request->fruit_condition,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tree created successfully',
                'data' => $tree
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // Log request data untuk debugging
            \Log::info('Update Tree Request Data:', [
                'id' => $id,
                'all_data' => $request->all(),
                'plantation_id' => $request->plantation_id,
                'varietas' => $request->varietas,
                'tahun_tanam' => $request->tahun_tanam,
                'health_status' => $request->health_status,
                'fase' => $request->fase,
                'method' => $request->method(),
                'has_plantation_id' => $request->has('plantation_id'),
                'content_type' => $request->header('Content-Type')
            ]);

            DB::beginTransaction();

            // Ambil data pohon sebelum diupdate untuk tracking perubahan
            $oldTree = Tree::findOrFail($id);
            $oldData = $oldTree->toArray();

            // Periksa apakah ID pohon akan diubah
            $newId = $request->filled('id') ? strtoupper($request->id) : $id;
            $isChangingId = $newId !== $id;

            // Jika ID akan berubah, pastikan ID baru tidak sudah digunakan
            if ($isChangingId) {
                // Log untuk debugging
                \Log::info('Changing tree ID from ' . $id . ' to ' . $newId);

                // Periksa apakah ID baru sudah digunakan di plantation yang sama
                $plantationId = $request->plantation_id ?: $oldTree->plantation_id;
                $exists = Tree::where('id', $newId)
                            ->where('plantation_id', $plantationId)
                            ->exists();

                if ($exists) {
                    throw new Exception('ID pohon baru sudah digunakan di blok kebun yang sama');
                }

                // Validasi format ID baru jika berisi angka dan huruf
                if (!preg_match('/^(\d+)([A-Z]+)$/', $newId)) {
                    throw new Exception('Format ID tidak valid. Gunakan format angka diikuti huruf (contoh: 1A, 2B, 10C)');
                } else {
                    \Log::info('Update - Valid alphanumeric ID format: ' . $newId);
                }
            }

            // Jika ID berubah, buat pohon baru dengan data lama
            if ($isChangingId) {
                // Buat objek pohon baru dengan ID baru
                $tree = new Tree();
                $tree->id = $newId;
            } else {
                // Ambil data pohon untuk diupdate jika ID tidak berubah
                $tree = $oldTree;
            }

            // Pastikan plantation_id tidak null
            if (!$request->has('plantation_id') || $request->plantation_id === null) {
                \Log::warning('plantation_id is null or not provided, using existing value');
                $tree->plantation_id = $oldTree->plantation_id;
            } else {
                $tree->plantation_id = $request->plantation_id;
            }

            // Update data pohon dengan nilai dari request
            if ($request->has('varietas')) {
                $tree->varietas = $request->varietas;
            } elseif ($isChangingId) {
                $tree->varietas = $oldTree->varietas;
            }

            if ($request->has('tahun_tanam')) {
                $tree->tahun_tanam = $request->tahun_tanam;
            } elseif ($isChangingId) {
                $tree->tahun_tanam = $oldTree->tahun_tanam;
            }

            if ($request->has('health_status')) {
                $tree->health_status = $request->health_status;
            } elseif ($isChangingId) {
                $tree->health_status = $oldTree->health_status;
            }

            if ($request->has('fase')) {
                $tree->fase = $request->fase;
            } elseif ($isChangingId) {
                $tree->fase = $oldTree->fase;
            }

            if ($request->has('sumber_bibit')) {
                $tree->sumber_bibit = $request->sumber_bibit;
            } elseif ($isChangingId) {
                $tree->sumber_bibit = $oldTree->sumber_bibit;
            }

            // Log tree data sebelum disimpan
            \Log::info('Tree data before save:', [
                'id' => $tree->id,
                'plantation_id' => $tree->plantation_id,
                'varietas' => $tree->varietas,
                'tahun_tanam' => $tree->tahun_tanam,
                'health_status' => $tree->health_status,
            ]);

            // Jika ada perubahan geometri atau ID berubah
            if ($request->filled('canopy_geometry') || $isChangingId) {
                // Validate and clean WKT format for canopy
                $canopy = $request->filled('canopy_geometry') ? trim($request->canopy_geometry) : null;

                if (empty($canopy) && $isChangingId) {
                    // Jika geometri kosong dan ID berubah, ambil geometri dari pohon lama
                    $existingGeometry = DB::select("SELECT ST_AsText(canopy_geometry) as canopy_geometry FROM trees WHERE id = ?", [$id])[0]->canopy_geometry;

                    if (!empty($existingGeometry)) {
                        \Log::info('Update - Using existing geometry: ' . $existingGeometry);
                        $canopy = $existingGeometry;
                    }
                }

                if (empty($canopy)) {
                    // Jika geometri masih kosong, coba ambil geometri yang sudah ada
                    $existingGeometry = DB::select("SELECT ST_AsText(canopy_geometry) as canopy_geometry FROM trees WHERE id = ?", [$id])[0]->canopy_geometry;

                    if (empty($existingGeometry)) {
                        throw new Exception('Canopy geometry is required');
                    } else {
                        \Log::info('Update - Using existing geometry: ' . $existingGeometry);
                        $canopy = $existingGeometry;
                    }
                }

                // Log untuk debugging
                \Log::info('Update - Original WKT: ' . $canopy);
                \Log::info('Update - Shape Type: ' . ($request->shape_type ?? 'Not specified'));

                // Normalisasi format WKT
                $canopy = preg_replace('/\s+/', ' ', $canopy); // Bersihkan spasi berlebih
                $canopy = strtoupper($canopy); // Konversi ke uppercase untuk konsistensi

                // Validasi format WKT berdasarkan tipe bentuk
                $isValid = false;

                // Cek apakah ini POINT
                if (preg_match('/^POINT\s*\(.+\)$/i', $canopy)) {
                    $isValid = true;
                    \Log::info('Update - Valid POINT format: ' . $canopy);
                }
                // Cek apakah ini POLYGON
                else if (preg_match('/^POLYGON\s*\(\s*\(.+\)\s*\)$/i', $canopy)) {
                    $isValid = true;
                    \Log::info('Update - Valid POLYGON format: ' . $canopy);
                }
                // Coba perbaiki format POLYGON jika tidak sesuai
                else if (preg_match('/^POLYGON\s*\(\s*[^()]+\s*\)$/i', $canopy)) {
                    $canopy = preg_replace('/^(POLYGON\s*\()(.+)(\))$/i', '$1($2)$3', $canopy);
                    $isValid = true;
                    \Log::info('Update - Fixed POLYGON format (added inner parentheses): ' . $canopy);
                }

                if (!$isValid) {
                    \Log::error('Update - Invalid WKT format: ' . $canopy);
                    throw new Exception('Invalid geometry format. Expected format: POLYGON((x y, x y, ...)) or POINT(x y)');
                }

                try {
                    // Set geometry using raw SQL to ensure proper SRID
                    $tree->canopy_geometry = DB::raw("ST_GeomFromText('$canopy', 4326)");
                    \Log::info('Update - Geometry updated successfully');
                } catch (\Exception $e) {
                    \Log::error('Update - Error updating geometry: ' . $e->getMessage());
                    throw new Exception('Error updating geometry: ' . $e->getMessage());
                }
            }

            // Simpan perubahan
            $tree->touch(); // Pastikan updated_at diperbarui
            $tree->save();

            // Update data pemupukan
            if ($request->filled('nama_pupuk') && $request->filled('jenis_pupuk') && $request->filled('dosis_pupuk')) {
                // Cek apakah sudah ada data pemupukan
                $fertilization = $isChangingId ? null : $tree->fertilizations()->latest()->first();

                if ($fertilization) {
                    // Update data yang sudah ada
                    $fertilization->update([
                        'nama_pupuk' => $request->nama_pupuk,
                        'jenis_pupuk' => $request->jenis_pupuk,
                        'dosis_pupuk' => $request->dosis_pupuk,
                    ]);
                } else {
                    // Buat data baru jika belum ada
                    $tree->fertilizations()->create([
                        'nama_pupuk' => $request->nama_pupuk,
                        'jenis_pupuk' => $request->jenis_pupuk,
                        'dosis_pupuk' => $request->dosis_pupuk,
                    ]);
                }
            } elseif ($isChangingId) {
                // Copy data pemupukan pohon lama ke pohon baru jika ID berubah
                $fertilizations = $oldTree->fertilizations;
                foreach ($fertilizations as $fertilization) {
                    $tree->fertilizations()->create([
                        'nama_pupuk' => $fertilization->nama_pupuk,
                        'jenis_pupuk' => $fertilization->jenis_pupuk,
                        'dosis_pupuk' => $fertilization->dosis_pupuk,
                        'tanggal_pemupukan' => $fertilization->tanggal_pemupukan,
                        'unit' => $fertilization->unit,
                        'created_at' => $fertilization->created_at,
                        'updated_at' => now(),
                    ]);
                }
            }

            // Update data pestisida
            if ($request->filled('nama_pestisida') || $request->filled('jenis_pestisida') || $request->filled('dosis')) {
                // Cek apakah sudah ada data pestisida
                $pesticide = $isChangingId ? null : $tree->pesticides()->latest()->first();

                if ($pesticide) {
                    // Update data yang sudah ada
                    $pesticide->update([
                        'nama_pestisida' => $request->nama_pestisida,
                        'jenis_pestisida' => $request->jenis_pestisida,
                        'dosis' => $request->dosis,
                    ]);
                } else {
                    // Buat data baru jika belum ada
                    $tree->pesticides()->create([
                        'nama_pestisida' => $request->nama_pestisida,
                        'jenis_pestisida' => $request->jenis_pestisida,
                        'dosis' => $request->dosis,
                    ]);
                }
            } elseif ($isChangingId) {
                // Copy data pestisida pohon lama ke pohon baru jika ID berubah
                $pesticides = $oldTree->pesticides;
                foreach ($pesticides as $pesticide) {
                    $tree->pesticides()->create([
                        'nama_pestisida' => $pesticide->nama_pestisida,
                        'jenis_pestisida' => $pesticide->jenis_pestisida,
                        'dosis' => $pesticide->dosis,
                        'tanggal_penyemprotan' => $pesticide->tanggal_penyemprotan,
                        'unit' => $pesticide->unit,
                        'created_at' => $pesticide->created_at,
                        'updated_at' => now(),
                    ]);
                }
            }

            // Update data panen
            if ($request->filled('fruit_count') && $request->filled('total_weight') &&
                $request->filled('average_weight_per_fruit') && $request->filled('fruit_condition')) {
                // Cek apakah sudah ada data panen
                $harvest = $isChangingId ? null : $tree->harvests()->latest()->first();

                if ($harvest) {
                    // Update data yang sudah ada
                    $harvest->update([
                        'fruit_count' => $request->fruit_count,
                        'total_weight' => $request->total_weight,
                        'average_weight_per_fruit' => $request->average_weight_per_fruit,
                        'fruit_condition' => $request->fruit_condition,
                    ]);
                } else {
                    // Buat data baru jika belum ada
                    $tree->harvests()->create([
                        'fruit_count' => $request->fruit_count,
                        'total_weight' => $request->total_weight,
                        'average_weight_per_fruit' => $request->average_weight_per_fruit,
                        'fruit_condition' => $request->fruit_condition,
                    ]);
                }
            } elseif ($isChangingId) {
                // Copy data panen pohon lama ke pohon baru jika ID berubah
                $harvests = $oldTree->harvests;
                foreach ($harvests as $harvest) {
                    $tree->harvests()->create([
                        'fruit_count' => $harvest->fruit_count,
                        'total_weight' => $harvest->total_weight,
                        'average_weight_per_fruit' => $harvest->average_weight_per_fruit,
                        'fruit_condition' => $harvest->fruit_condition,
                        'tanggal_panen' => $harvest->tanggal_panen,
                        'created_at' => $harvest->created_at,
                        'updated_at' => now(),
                    ]);
                }
            }

            // Jika ID berubah, hapus pohon lama setelah semua data disalin
            if ($isChangingId) {
                $oldTree->delete();
                \Log::info('Old tree with ID ' . $id . ' has been deleted, replaced with ID ' . $newId);
            }

            DB::commit();

            // Ambil data pohon setelah diupdate
            $newTree = Tree::findOrFail($isChangingId ? $newId : $id);
            $newData = $newTree->toArray();

            // Bandingkan data lama dan baru untuk melacak perubahan
            $changes = [];
            foreach ($newData as $key => $value) {
                if ($key === 'id' && $isChangingId) {
                    $changes['id'] = [
                        'old' => $id,
                        'new' => $newId
                    ];
                } elseif (isset($oldData[$key]) && $oldData[$key] != $value) {
                    $changes[$key] = [
                        'old' => $oldData[$key],
                        'new' => $value
                    ];
                }
            }

            // Log tree data setelah disimpan
            \Log::info('Tree updated successfully:', [
                'id' => $newTree->id,
                'plantation_id' => $newTree->plantation_id,
                'varietas' => $newTree->varietas,
                'changes' => $changes
            ]);

            // Ambil data geometri dengan raw SQL
            $geometry = DB::select("SELECT ST_AsText(canopy_geometry) as canopy_geometry FROM trees WHERE id = ?", [$newTree->id])[0]->canopy_geometry;

            // Tambahkan data geometri ke respons
            $newData['canopy_geometry'] = $geometry;

            return response()->json([
                'success' => true,
                'message' => 'Tree updated successfully',
                'data' => $newData,
                'changes' => $changes
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            // Log error
            \Log::error('Error updating tree:', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getAll()
    {
        try {
            // Log untuk debugging
            \Log::info('getAll called with request: ' . json_encode(request()->all()));

            // Cek apakah request memaksa refresh
            $forceRefresh = request()->has('force') && request()->force === 'true';
            \Log::info('Force refresh: ' . ($forceRefresh ? 'true' : 'false'));

            // Ambil data pohon dari database dengan query yang lebih lengkap
            $trees = DB::table('trees')
                ->select([
                    'id',
                    'plantation_id',
                    'varietas',
                    'tahun_tanam',
                    'health_status',
                    'fase',
                    'latitude',
                    'longitude',
                    'sumber_bibit',
                    'created_at',
                    'updated_at',
                    DB::raw("ST_AsText(canopy_geometry) as canopy_geometry")
                ])
                ->orderBy('updated_at', 'desc') // Urutkan berdasarkan waktu update terbaru
                ->get();

            // Log jumlah data yang diambil
            \Log::info('getAll retrieved ' . count($trees) . ' trees');

            // Tambahkan timestamp untuk mencegah cache
            $response = [
                'success' => true,
                'data' => $trees,
                'timestamp' => now()->timestamp,
                'forced' => $forceRefresh
            ];

            return response()->json($response);
        } catch (Exception $e) {
            \Log::error('Error in getAll: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching trees data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Cari pohon berdasarkan ID
            $tree = Tree::find($id);

            // Jika pohon tidak ditemukan
            if (!$tree) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pohon tidak ditemukan'
                ], 404);
            }

            // Hapus pohon
            $tree->delete();

            // Hapus bagian reset sequence karena ID pohon bukan auto-increment
            // dan sequence trees_id_seq tidak ada di database

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data pohon berhasil dihapus'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting tree: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data pohon: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            // Ambil data pohon dengan Eloquent untuk memudahkan relasi
            $tree = Tree::findOrFail($id);

            // Ambil data geometri dengan raw SQL
            $geometry = DB::select("SELECT ST_AsText(canopy_geometry) as canopy_geometry FROM trees WHERE id = ?", [$id])[0]->canopy_geometry;

            // Log untuk debugging
            \Log::info('Show Tree ID ' . $id . ' - Geometry: ' . $geometry);

            // Ambil data pemupukan terkait
            $fertilization = $tree->fertilizations()->first();

            // Ambil data pestisida terkait
            $pesticide = $tree->pesticides()->first();

            // Ambil data panen terkait
            $harvest = $tree->harvests()->first();

            // Gabungkan semua data
            $treeData = $tree->toArray();
            $treeData['canopy_geometry_wkt'] = $geometry;

            // Pastikan canopy_geometry juga tersedia untuk kompatibilitas
            $treeData['canopy_geometry'] = $geometry;

            // Tambahkan data pemupukan jika ada
            if ($fertilization) {
                $treeData['nama_pupuk'] = $fertilization->nama_pupuk;
                $treeData['jenis_pupuk'] = $fertilization->jenis_pupuk;
                $treeData['dosis_pupuk'] = $fertilization->dosis_pupuk;
            }

            // Tambahkan data pestisida jika ada
            if ($pesticide) {
                $treeData['nama_pestisida'] = $pesticide->nama_pestisida;
                $treeData['jenis_pestisida'] = $pesticide->jenis_pestisida;
                $treeData['dosis'] = $pesticide->dosis;
            }

            // Tambahkan data panen jika ada
            if ($harvest) {
                $treeData['fruit_count'] = $harvest->fruit_count;
                $treeData['total_weight'] = $harvest->total_weight;
                $treeData['average_weight_per_fruit'] = $harvest->average_weight_per_fruit;
                $treeData['fruit_condition'] = $harvest->fruit_condition;
            }

            return response()->json([
                'success' => true,
                'data' => $treeData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeFertilization(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validasi input
            $validated = $request->validate([
                'tree_id' => 'required|exists:trees,id',
                'tanggal_pemupukan' => 'required|date',
                'nama_pupuk' => 'required|string',
                'jenis_pupuk' => 'required|in:Organik,Anorganik',
                'bentuk_pupuk' => 'required|string',
                'dosis_pupuk' => 'required|numeric',
                'unit' => 'required|in:g/tanaman,ml/tanaman'
            ]);

            // Simpan data pemupukan
            $fertilization = TreeFertilization::create([
                'tree_id' => $validated['tree_id'],
                'tanggal_pemupukan' => $validated['tanggal_pemupukan'],
                'nama_pupuk' => $validated['nama_pupuk'],
                'jenis_pupuk' => $validated['jenis_pupuk'],
                'bentuk_pupuk' => $validated['bentuk_pupuk'],
                'dosis_pupuk' => $validated['dosis_pupuk'],
                'unit' => $validated['unit']
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data pemupukan berhasil disimpan',
                    'data' => $fertilization,
                    'redirect_url' => route('tree.dashboard', ['id' => $validated['tree_id']])
                ]);
            }

            return redirect()->route('tree.dashboard', ['id' => $validated['tree_id']])
                           ->with('success', 'Data pemupukan berhasil disimpan');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error saving fertilization data: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menyimpan data pemupukan: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Gagal menyimpan data pemupukan: ' . $e->getMessage());
        }
    }

    public function editFertilization($id)
    {
        try {
            $fertilization = TreeFertilization::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $fertilization
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pemupukan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyFertilization($id)
    {
        try {
            $fertilization = TreeFertilization::findOrFail($id);
            $treeId = $fertilization->tree_id;
            $fertilization->delete();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data pemupukan berhasil dihapus',
                    'redirect_url' => route('tree.dashboard', ['id' => $treeId])
                ]);
            }

            return redirect()->route('tree.dashboard', ['id' => $treeId])
                           ->with('success', 'Data pemupukan berhasil dihapus');
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus data pemupukan: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Gagal menghapus data pemupukan: ' . $e->getMessage());
        }
    }

    public function updateFertilization(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'tanggal_pemupukan' => 'required|date',
                'nama_pupuk' => 'required|string',
                'jenis_pupuk' => 'required|in:Organik,Anorganik',
                'bentuk_pupuk' => 'required|string',
                'dosis_pupuk' => 'required|numeric',
                'unit' => 'required|in:g/tanaman,ml/tanaman'
            ]);

            $fertilization = TreeFertilization::findOrFail($id);
            $fertilization->update($validated);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data pemupukan berhasil diperbarui',
                    'redirect_url' => route('tree.dashboard', ['id' => $fertilization->tree_id])
                ]);
            }

            return redirect()->route('tree.dashboard', ['id' => $fertilization->tree_id])
                           ->with('success', 'Data pemupukan berhasil diperbarui');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memperbarui data pemupukan: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Gagal memperbarui data pemupukan: ' . $e->getMessage());
        }
    }

    public function destroyPesticide($id)
    {
        try {
            $pesticide = TreePesticide::findOrFail($id);
            $treeId = $pesticide->tree_id;
            $pesticide->delete();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data pestisida berhasil dihapus',
                    'redirect_url' => route('tree.dashboard', ['id' => $treeId])
                ]);
            }

            return redirect()->route('tree.dashboard', ['id' => $treeId])
                           ->with('success', 'Data pestisida berhasil dihapus');
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus data pestisida: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Gagal menghapus data pestisida: ' . $e->getMessage());
        }
    }

    public function updatePesticide(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'tanggal_pestisida' => 'required|date',
                'nama_pestisida' => 'required|string',
                'jenis_pestisida' => 'required|in:Insektisida,Fungisida,Herbisida,Bakterisida',
                'bentuk_pestisida' => 'nullable|string',
                'dosis' => 'required|numeric',
                'unit' => 'required|in:ml/tanaman,l/tanaman,g/tanaman'
            ]);

            $pesticide = TreePesticide::findOrFail($id);
            $pesticide->update([
                'tanggal_pestisida' => $validated['tanggal_pestisida'],
                'nama_pestisida' => $validated['nama_pestisida'],
                'jenis_pestisida' => $validated['jenis_pestisida'],
                'bentuk_pestisida' => $validated['bentuk_pestisida'] ?? null,
                'dosis' => $validated['dosis'],
                'unit' => $validated['unit']
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data pestisida berhasil diperbarui',
                    'redirect_url' => route('tree.dashboard', ['id' => $pesticide->tree_id])
                ]);
            }

            return redirect()->route('tree.dashboard', ['id' => $pesticide->tree_id])
                           ->with('success', 'Data pestisida berhasil diperbarui');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memperbarui data pestisida: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Gagal memperbarui data pestisida: ' . $e->getMessage());
        }
    }

    public function editPesticide($id)
    {
        try {
            $pesticide = TreePesticide::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $pesticide
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pestisida: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyHarvest($id)
    {
        try {
            $harvest = Harvest::findOrFail($id);
            $treeId = $harvest->tree_id;
            $harvest->delete();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data panen berhasil dihapus',
                    'redirect_url' => route('tree.dashboard', ['id' => $treeId])
                ]);
            }

            return redirect()->route('tree.dashboard', ['id' => $treeId])
                           ->with('success', 'Data panen berhasil dihapus');
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus data panen: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Gagal menghapus data panen: ' . $e->getMessage());
        }
    }

    public function updateHarvest(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $harvest = Harvest::findOrFail($id);

            // Validasi input
            $validated = $request->validate([
                'tanggal_panen' => 'required|date',
                'total_weight' => 'required|numeric',
                'fruit_count' => 'required|integer',
                'average_weight_per_fruit' => 'required|numeric',
                'fruit_condition' => 'required|numeric|min:0|max:100',
                'unit' => 'required|in:kg,g'
            ]);

            // Update data panen
            $harvest->update([
                'tanggal_panen' => $validated['tanggal_panen'],
                'total_weight' => $validated['total_weight'],
                'fruit_count' => $validated['fruit_count'],
                'average_weight_per_fruit' => $validated['average_weight_per_fruit'],
                'fruit_condition' => $validated['fruit_condition'],
                'unit' => $validated['unit']
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data panen berhasil diperbarui',
                'redirect_url' => route('tree.dashboard', ['id' => $harvest->tree_id])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function editHarvest($id)
    {
        try {
            $harvest = Harvest::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $harvest
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data panen: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeHarvest(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validasi input
            $validated = $request->validate([
                'tree_id' => 'required|exists:trees,id',
                'tanggal_panen' => 'required|date',
                'total_weight' => 'required|numeric',
                'fruit_count' => 'required|integer',
                'average_weight_per_fruit' => 'required|numeric',
                'fruit_condition' => 'required|numeric|min:0|max:100',
                'unit' => 'required|in:kg,g'
            ]);

            // Simpan data panen
            $harvest = Harvest::create([
                'tree_id' => $validated['tree_id'],
                'tanggal_panen' => $validated['tanggal_panen'],
                'total_weight' => $validated['total_weight'],
                'fruit_count' => $validated['fruit_count'],
                'average_weight_per_fruit' => $validated['average_weight_per_fruit'],
                'fruit_condition' => $validated['fruit_condition'],
                'unit' => $validated['unit']
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data panen berhasil disimpan',
                'redirect_url' => route('tree.dashboard', ['id' => $validated['tree_id']])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storePesticide(Request $request)
    {
        try {
            $request->validate([
                'tree_id' => 'required|exists:trees,id',
                'tanggal_pestisida' => 'required|date',
                'nama_pestisida' => 'required|string',
                'jenis_pestisida' => 'required|in:Insektisida,Fungisida,Herbisida,Bakterisida',
                'bentuk_pestisida' => 'nullable|string',
                'dosis' => 'required|numeric',
                'unit' => 'required|in:ml/tanaman,l/tanaman,g/tanaman'
            ]);

            $pesticide = TreePesticide::create([
                'tree_id' => $request->tree_id,
                'tanggal_pestisida' => $request->tanggal_pestisida,
                'nama_pestisida' => $request->nama_pestisida,
                'jenis_pestisida' => $request->jenis_pestisida,
                'bentuk_pestisida' => $request->bentuk_pestisida,
                'dosis' => $request->dosis,
                'unit' => $request->unit
            ]);

            if ($pesticide) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data pestisida berhasil disimpan',
                    'redirect_url' => route('tree.dashboard', ['id' => $request->tree_id])
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data pestisida'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
