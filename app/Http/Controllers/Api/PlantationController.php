<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plantation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PlantationController extends Controller
{
    /**
     * Memastikan geometri dalam format WKT teks
     * Data geometri sudah dalam format WKT standar di database jadi tidak perlu konversi apapun
     */
    protected function ensureTextWkt($plantations)
    {
        // Jika single object
        if (!is_array($plantations) || !isset($plantations[0])) {
            if (isset($plantations->geometry) && $plantations->geometry) {
                // Pastikan memiliki SRID jika perlu
                if (!str_starts_with(strtoupper($plantations->geometry), 'SRID=')) {
                    $plantations->geometry = "SRID=4326;" . $plantations->geometry;
                    Log::info("Added SRID to plantation {$plantations->id} geometry");
                }

                // Simpan ke boundary_text jika perlu
                if (!isset($plantations->boundary_text) || !$plantations->boundary_text) {
                    $plantations->boundary_text = $plantations->geometry;
                }
            }
            return $plantations;
        }

        // Jika array/collection
        foreach ($plantations as $plantation) {
            if (isset($plantation->geometry) && $plantation->geometry) {
                // Pastikan memiliki SRID jika perlu
                if (!str_starts_with(strtoupper($plantation->geometry), 'SRID=')) {
                    $plantation->geometry = "SRID=4326;" . $plantation->geometry;
                    Log::info("Added SRID to plantation {$plantation->id} geometry");
                }

                // Simpan ke boundary_text jika perlu
                if (!isset($plantation->boundary_text) || !$plantation->boundary_text) {
                    $plantation->boundary_text = $plantation->geometry;
                }
            }
        }

        return $plantations;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $debug = $request->has('debug') && $request->debug === 'true';

            if ($debug) {
                Log::info('Debug mode activated for plantations API');
            }

            // Dapatkan data dengan geometry dalam format WKT
            $plantations = Plantation::withCount('trees')
                ->select(
                    'plantations.*',
                    DB::raw('ST_AsText(geometry) as geometry')
                )
                ->get();

            if ($debug) {
                Log::info('Plantations loaded count: ' . $plantations->count());
            }

            // Jangan konversi format WKT lagi, cukup tambahkan SRID jika perlu
            foreach ($plantations as $plantation) {
                // Pastikan geometry ada dan terinisialisasi
                if (isset($plantation->geometry) && $plantation->geometry) {
                    // Pastikan geometry memiliki SRID
                    if (!str_starts_with(strtoupper($plantation->geometry), 'SRID=')) {
                        $plantation->geometry = "SRID=4326;" . $plantation->geometry;
                    }

                    // Simpan geometry ke atribut boundary_text untuk keperluan tampilan
                    $plantation->boundary_text = $plantation->geometry;
                } else if (isset($plantation->geometry) && $plantation->geometry) {
                    // Fallback ke geometry jika geometry tidak ada
                    Log::warning("Plantation {$plantation->id} missing geometry, using geometry attribute");

                    // Pastikan memiliki SRID jika perlu
                    if (!str_starts_with(strtoupper($plantation->geometry), 'SRID=')) {
                        $plantation->geometry = "SRID=4326;" . $plantation->geometry;
                    }

                    $plantation->geometry = $plantation->geometry;
                    $plantation->boundary_text = $plantation->geometry;
                }

                if ($debug && isset($plantation->geometry)) {
                    Log::info("Plantation {$plantation->id} geometry: " .
                             substr($plantation->geometry, 0, 50) . "...");
                }
            }

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
            'luas_area' => 'required|numeric|min:0',
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
            // Cari ID terkecil yang tersedia (ID yang telah dihapus)
            $availableId = $this->findSmallestAvailableId();
            Log::info('Using available ID for new plantation:', ['id' => $availableId]);

            // Buat blok kebun baru
            $plantation = new Plantation();

            // Jika ada ID yang tersedia, gunakan itu
            if ($availableId) {
                $plantation->id = $availableId;
                Log::info('Setting plantation ID to reuse deleted ID:', ['id' => $availableId]);
            }

            $plantation->name = $request->name;

            // Validasi format geometri
            $geometry = $request->geometry;
            Log::info('Received geometry:', ['raw' => substr($geometry, 0, 100)]);

            // Pastikan geometri dimulai dengan POLYGON atau MULTIPOLYGON
            $cleanGeometry = $geometry;
            if (preg_match('/^SRID=\d+;/i', $geometry)) {
                $parts = explode(';', $geometry, 2);
                if (count($parts) >= 2) {
                    $cleanGeometry = $parts[1];
                }
            }

            $cleanGeometry = strtoupper(trim($cleanGeometry));
            if (!preg_match('/^POLYGON/i', $cleanGeometry) && !preg_match('/^MULTIPOLYGON/i', $cleanGeometry)) {
                Log::error('Invalid geometry format:', ['geometry' => $cleanGeometry]);
                return response()->json([
                    'success' => false,
                    'message' => 'Format geometri tidak valid. Harus berupa POLYGON atau MULTIPOLYGON.',
                    'errors' => ['geometry' => ['Format geometri tidak valid']]
                ], 422);
            }

            // Pastikan format WKT geometri benar (SRID=4326;WKT)
            if (!preg_match('/^SRID=\d+;/i', $geometry)) {
                $geometry = 'SRID=4326;' . $geometry;
                Log::info('Added SRID to geometry:', ['formatted' => substr($geometry, 0, 100)]);
            }

            $plantation->geometry = $geometry;

            // Coba validasi geometri dengan PostGIS
            try {
                $isValid = DB::selectOne("SELECT ST_IsValid(ST_GeomFromEWKT(?)) as is_valid", [$geometry]);
                if (!$isValid || $isValid->is_valid == 0) {
                    // Coba perbaiki geometri dengan ST_MakeValid
                    Log::warning('Invalid geometry, attempting to repair');
                    $repairedGeom = DB::selectOne("SELECT ST_AsEWKT(ST_MakeValid(ST_GeomFromEWKT(?))) as geom", [$geometry]);

                    if ($repairedGeom && $repairedGeom->geom) {
                        $geometry = $repairedGeom->geom;
                        $plantation->geometry = $geometry;
                        Log::info('Geometry repaired successfully');
                    } else {
                        Log::error('Failed to repair geometry');
                        return response()->json([
                            'success' => false,
                            'message' => 'Geometri tidak valid dan tidak dapat diperbaiki',
                            'errors' => ['geometry' => ['Geometri tidak valid']]
                        ], 422);
                    }
                }
            } catch (\Exception $validEx) {
                Log::error('Error validating geometry: ' . $validEx->getMessage());
                // Lanjutkan meskipun validasi gagal, mungkin masih bisa disimpan
            }

            // Gunakan nilai luas area dari frontend sebagai default
            $plantation->luas_area = $request->luas_area;

            // Hitung luas area menggunakan PostGIS (dalam hektar)
            try {
                // Gunakan proyeksi yang konsisten untuk perhitungan luas (3857)
                // SRID 3857 adalah Web Mercator, 4326 adalah WGS84
                // Bagi dengan 10000 untuk konversi dari m² ke hektar
                $areaQuery = "
                    SELECT
                        ST_Area(ST_Transform(ST_GeomFromEWKT(?), 3857))/10000 as area_in_hectares,
                        ST_AsEWKT(ST_Centroid(ST_GeomFromEWKT(?))) as centroid
                ";
                $areaResult = DB::selectOne($areaQuery, [$geometry, $geometry]);

                // Jika nilai luas area dikirim dari frontend dan valid, prioritaskan nilai tersebut
                if ($request->has('luas_area') && is_numeric($request->luas_area) && $request->luas_area > 0) {
                    $plantation->luas_area = round($request->luas_area, 4);
                    Log::info('Using client-provided area value:', ['area' => $plantation->luas_area]);
                } else if ($areaResult && isset($areaResult->area_in_hectares)) {
                    // Jika tidak ada nilai dari frontend, hitung dengan PostGIS
                    $plantation->luas_area = round($areaResult->area_in_hectares, 4);
                    Log::info('Using PostGIS calculated area:', ['area' => $plantation->luas_area]);
                }

                // Ambil centroid untuk koordinat latitude/longitude
                if ($areaResult && isset($areaResult->centroid)) {
                    $centroidWkt = $areaResult->centroid;
                    // Parse centroid untuk mendapatkan lat/lon
                    if (preg_match('/POINT\s*\(([\d\.\-]+)\s+([\d\.\-]+)\)/', $centroidWkt, $matches)) {
                        $plantation->longitude = $matches[1];
                        $plantation->latitude = $matches[2];
                        Log::info('Centroid coordinates:', [
                            'longitude' => $plantation->longitude,
                            'latitude' => $plantation->latitude
                        ]);
                    }
                }
            } catch (\Exception $areaEx) {
                Log::error('Error calculating area: ' . $areaEx->getMessage());
                // Lanjutkan dengan nilai area yang diberikan oleh frontend
            }

            // PENTING: Jika plantasi dibuat melalui leaflet geoman (manual drawing), tidak perlu shapefile_id
            if ($request->has('shapefile_id') && $request->shapefile_id) {
                $plantation->shapefile_id = $request->shapefile_id;
            }
            // Jika dibuat manual dari leaflet geoman, tidak perlu shapefile_id

            // Simpan data blok kebun
            $plantation->save();
            Log::info('Plantation saved successfully with ID: ' . $plantation->id);

            // Jika dibuat dari Leaflet Geoman, buat shapefile secara otomatis dan proses
            if (!$request->has('shapefile_id') || !$request->shapefile_id) {
                try {
                    // Buat shapefile baru
                    $shapefile = new \App\Models\Shapefile();
                    $shapefile->name = $plantation->name . ' (Auto Leaflet)';
                    $shapefile->type = 'plantation';
                    $shapefile->description = 'Dibuat otomatis dari Leaflet Geoman';
                    
                    // Simpan shapefile
                    $shapefile->save();
                    
                    // Simpan geometri ke shapefile
                    $shapefile->geometry = $plantation->geometry;
                    $shapefile->save();
                    
                    // Update plantation dengan shapefile_id
                    $plantation->shapefile_id = $shapefile->id;
                    $plantation->save();
                    
                    Log::info('Auto-created shapefile for Leaflet Geoman plantation', [
                        'plantation_id' => $plantation->id,
                        'shapefile_id' => $shapefile->id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to auto-create shapefile: ' . $e->getMessage());
                    // Lanjutkan meskipun gagal membuat shapefile
                }
            }

            // Ambil data lengkap plantation untuk respons API
            $plantation = Plantation::select(
                    'plantations.*',
                    DB::raw('ST_AsText(geometry) as geometry')
                )
                ->withCount('trees')
                ->find($plantation->id);

            // Pastikan format WKT benar untuk respons
            $plantation = $this->ensureTextWkt($plantation);

            return response()->json([
                'success' => true,
                'message' => 'Blok kebun berhasil ditambahkan',
                'data' => $plantation
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error in PlantationController@store: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data blok kebun',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mencari ID terkecil yang tersedia untuk plantation baru
     * Jika tidak ada data sama sekali, kembalikan ID 1
     * Jika ada ID yang kosong di tengah (karena penghapusan), gunakan ID tersebut
     */
    private function findSmallestAvailableId()
    {
        try {
            // Dapatkan semua ID yang ada di database
            $existingIds = Plantation::pluck('id')->toArray();

            // Jika tidak ada data sama sekali, mulai dari ID 1
            if (empty($existingIds)) {
                Log::info('No plantations exist, starting with ID 1');
                return 1;
            }

            // Dapatkan ID terbesar yang ada di database
            $maxId = max($existingIds);

            // Cari ID terkecil yang tersedia dalam rentang 1 sampai maxId
            for ($i = 1; $i <= $maxId; $i++) {
                if (!in_array($i, $existingIds)) {
                    Log::info('Found smallest available ID:', ['id' => $i]);
                    return $i;
                }
            }

            // Jika semua ID dari 1 sampai maxId sudah digunakan,
            // gunakan ID berikutnya (maxId + 1)
            $nextId = $maxId + 1;
            Log::info('No gaps found, using next ID:', ['id' => $nextId]);
            return $nextId;
        } catch (\Exception $e) {
            Log::error('Error finding smallest available ID: ' . $e->getMessage());
            // Default ke ID 1 jika terjadi error
            return 1;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $plantation = Plantation::select(
                'plantations.*',
                DB::raw('ST_AsText(geometry) as geometry')
            )->findOrFail($id);

            // Pastikan geometry memiliki SRID
            if (isset($plantation->geometry) && $plantation->geometry) {
                if (!str_starts_with(strtoupper($plantation->geometry), 'SRID=')) {
                    $plantation->geometry = "SRID=4326;" . $plantation->geometry;
                }

                // Simpan juga ke boundary_text untuk konsistensi
                $plantation->boundary_text = $plantation->geometry;
            }

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
            'luas_area' => 'required|numeric|min:0',
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

            // Validasi format geometri
            $geometry = $request->geometry;
            Log::info('Received geometry for update:', ['raw' => substr($geometry, 0, 100)]);

            // Pastikan geometri dimulai dengan POLYGON atau MULTIPOLYGON
            $cleanGeometry = $geometry;
            if (preg_match('/^SRID=\d+;/i', $geometry)) {
                $parts = explode(';', $geometry, 2);
                if (count($parts) >= 2) {
                    $cleanGeometry = $parts[1];
                }
            }

            $cleanGeometry = strtoupper(trim($cleanGeometry));
            if (!preg_match('/^POLYGON/i', $cleanGeometry) && !preg_match('/^MULTIPOLYGON/i', $cleanGeometry)) {
                Log::error('Invalid geometry format for update:', ['geometry' => $cleanGeometry]);
                return response()->json([
                    'success' => false,
                    'message' => 'Format geometri tidak valid. Harus berupa POLYGON atau MULTIPOLYGON.',
                    'errors' => ['geometry' => ['Format geometri tidak valid']]
                ], 422);
            }

            // Pastikan format WKT geometri benar (SRID=4326;WKT)
            if (!preg_match('/^SRID=\d+;/i', $geometry)) {
                $geometry = 'SRID=4326;' . $geometry;
                Log::info('Added SRID to geometry for update:', ['formatted' => substr($geometry, 0, 100)]);
            }

            $plantation->geometry = $geometry;

            // Coba validasi geometri dengan PostGIS
            try {
                $isValid = DB::selectOne("SELECT ST_IsValid(ST_GeomFromEWKT(?)) as is_valid", [$geometry]);
                if (!$isValid || $isValid->is_valid == 0) {
                    // Coba perbaiki geometri dengan ST_MakeValid
                    Log::warning('Invalid geometry for update, attempting to repair');
                    $repairedGeom = DB::selectOne("SELECT ST_AsEWKT(ST_MakeValid(ST_GeomFromEWKT(?))) as geom", [$geometry]);

                    if ($repairedGeom && $repairedGeom->geom) {
                        $geometry = $repairedGeom->geom;
                        $plantation->geometry = $geometry;
                        Log::info('Geometry for update repaired successfully');
                    } else {
                        Log::error('Failed to repair geometry for update');
                        return response()->json([
                            'success' => false,
                            'message' => 'Geometri tidak valid dan tidak dapat diperbaiki',
                            'errors' => ['geometry' => ['Geometri tidak valid']]
                        ], 422);
                    }
                }
            } catch (\Exception $validEx) {
                Log::error('Error validating geometry for update: ' . $validEx->getMessage());
                // Lanjutkan meskipun validasi gagal, mungkin masih bisa disimpan
            }

            // Gunakan nilai luas area dari frontend sebagai default
            $plantation->luas_area = $request->luas_area;

            // Hitung luas area menggunakan PostGIS (dalam hektar)
            try {
                // Gunakan proyeksi yang konsisten (3857, sama dengan method store)
                // SRID 3857 adalah Web Mercator, 4326 adalah WGS84
                // Bagi dengan 10000 untuk konversi dari m² ke hektar
                $areaQuery = "
                    SELECT
                        ST_Area(ST_Transform(ST_GeomFromEWKT(?), 3857))/10000 as area_in_hectares,
                        ST_AsEWKT(ST_Centroid(ST_GeomFromEWKT(?))) as centroid
                ";
                $areaResult = DB::selectOne($areaQuery, [$geometry, $geometry]);

                // Jika nilai luas area dikirim dari frontend dan valid, prioritaskan nilai tersebut
                if ($request->has('luas_area') && is_numeric($request->luas_area) && $request->luas_area > 0) {
                    $plantation->luas_area = round($request->luas_area, 4);
                    Log::info('Using client-provided area value for update:', ['area' => $plantation->luas_area]);
                } else if ($areaResult && isset($areaResult->area_in_hectares)) {
                    // Jika tidak ada nilai dari frontend, hitung dengan PostGIS
                    $plantation->luas_area = round($areaResult->area_in_hectares, 4);
                    Log::info('Area calculated with PostGIS for update:', ['area' => $plantation->luas_area]);
                }

                // Ekstrak centroid untuk latitude/longitude
                if (isset($areaResult->centroid) && $areaResult->centroid) {
                    try {
                        $centroidText = $areaResult->centroid;
                        // Format: SRID=4326;POINT(longitude latitude)
                        if (preg_match('/POINT\(([-\d.]+) ([-\d.]+)\)/i', $centroidText, $matches)) {
                            $plantation->longitude = $matches[1];
                            $plantation->latitude = $matches[2];
                            Log::info('Centroid extracted for update:', [
                                'longitude' => $plantation->longitude,
                                'latitude' => $plantation->latitude
                            ]);
                        }
                    } catch (\Exception $centroidEx) {
                        Log::warning('Error extracting centroid for update: ' . $centroidEx->getMessage());
                    }
                }
            } catch (\Exception $areaEx) {
                Log::error('Error calculating area for update: ' . $areaEx->getMessage(), [
                    'trace' => $areaEx->getTraceAsString()
                ]);
                // Lanjutkan dengan nilai luas dari client
            }

            // Update tipe_tanah jika ada
            if ($request->has('tipe_tanah')) {
                $plantation->tipe_tanah = $request->tipe_tanah;
            }

            // Periksa apakah perlu update shapefile_id
            if (!$plantation->shapefile_id) {
                // Cari atau buat shapefile untuk user yang login
                if (Auth::check()) {
                    $shapefile = \App\Models\Shapefile::firstOrCreate(
                        [
                            'user_id' => Auth::id(),
                            'type' => 'plantation'
                        ],
                        [
                            'name' => 'Shapefile Kebun ' . Auth::user()->name,
                            'description' => 'Shapefile untuk kebun yang dibuat oleh ' . Auth::user()->name,
                            'processed' => true
                        ]
                    );

                    $plantation->shapefile_id = $shapefile->id;
                    Log::info('Assigned shapefile_id to plantation during update:', ['shapefile_id' => $shapefile->id]);
                } else {
                    // Jika tidak ada user yang login, gunakan shapefile default
                    $shapefile = \App\Models\Shapefile::firstOrCreate(
                        [
                            'user_id' => 1, // Default user id
                            'type' => 'plantation'
                        ],
                        [
                            'name' => 'Shapefile Kebun Default',
                            'description' => 'Shapefile default untuk kebun',
                            'processed' => true
                        ]
                    );

                    $plantation->shapefile_id = $shapefile->id;
                    Log::info('Assigned default shapefile_id to plantation during update:', ['shapefile_id' => $shapefile->id]);
                }
            }

            // Pastikan nilai luas area valid
            if ($plantation->luas_area === null || !is_numeric($plantation->luas_area)) {
                $plantation->luas_area = 0;
            }

            $plantation->save();

            Log::info('Plantation updated successfully:', [
                'id' => $plantation->id,
                'luas_area' => $plantation->luas_area,
                'latitude' => $plantation->latitude,
                'longitude' => $plantation->longitude
            ]);

            // Pastikan format WKT dalam bentuk teks, bukan binary
            $plantation = $this->ensureTextWkt($plantation);

            return response()->json([
                'success' => true,
                'message' => 'Blok kebun berhasil diperbarui',
                'data' => $plantation
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating plantation: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'id' => $id,
                'data' => $request->all()
            ]);
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
        Log::info('Attempting to delete plantation with ID: ' . $id);

        // Enable query log untuk debugging
        DB::enableQueryLog();

        try {
            // Cari plantation terlebih dahulu, jika tidak ada langsung return 404
            $plantation = Plantation::find($id);

            if (!$plantation) {
                Log::warning('Plantation not found:', ['id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Blok kebun tidak ditemukan'
                ], 404);
            }

            $deletedId = $plantation->id;
            Log::info('Found plantation to delete:', ['id' => $deletedId, 'attributes' => $plantation->toArray()]);

            // Mulai transaksi DB
            try {
                DB::beginTransaction();
                Log::info('Transaction started');
            } catch (\Exception $txEx) {
                Log::error('Failed to start transaction: ' . $txEx->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memulai transaksi database: ' . $txEx->getMessage(),
                    'error' => $txEx->getMessage()
                ], 500);
            }

            // Periksa relasi trees
            try {
                // Dapatkan tree count dengan query builder
                $treeCount = DB::table('trees')
                    ->where('plantation_id', $deletedId)
                    ->count();

                Log::info('Tree count for plantation:', ['id' => $deletedId, 'count' => $treeCount]);

                if ($treeCount > 0) {
                    Log::warning('Cannot delete plantation with related trees:', [
                        'id' => $deletedId,
                        'tree_count' => $treeCount
                    ]);

                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Blok kebun tidak dapat dihapus karena masih memiliki {$treeCount} pohon terkait. Hapus pohon terlebih dahulu."
                    ], 400);
                }
            } catch (\Exception $treeEx) {
                Log::error('Error checking tree relations: ' . $treeEx->getMessage(), [
                    'trace' => $treeEx->getTraceAsString(),
                    'id' => $deletedId
                ]);

                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memeriksa relasi pohon: ' . $treeEx->getMessage(),
                    'error' => $treeEx->getMessage()
                ], 500);
            }

            // Periksa apakah plantation memiliki entitas lain yang terkait
            try {
                // Coba periksa semua tabel relasi yang mungkin
                $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
                $relatedTables = [];

                foreach ($tables as $table) {
                    $tableName = $table->table_name;

                    // Periksa apakah tabel ini memiliki kolom plantation_id
                    $columns = DB::select("
                        SELECT column_name
                        FROM information_schema.columns
                        WHERE table_schema = 'public'
                        AND table_name = ?
                        AND column_name = 'plantation_id'",
                        [$tableName]
                    );

                    if (!empty($columns)) {
                        // Tabel ini memiliki relasi ke plantation
                        $count = DB::table($tableName)->where('plantation_id', $deletedId)->count();

                        if ($count > 0) {
                            $relatedTables[$tableName] = $count;
                        }
                    }
                }

                if (!empty($relatedTables)) {
                    Log::warning('Found related records in other tables:', [
                        'plantation_id' => $deletedId,
                        'related_tables' => $relatedTables
                    ]);
                }

            } catch (\Exception $relEx) {
                Log::warning('Error checking other relations (non-critical): ' . $relEx->getMessage());
                // Lanjutkan proses meskipun terjadi error pada pengecekan relasi lain
            }

            // Hapus plantation dengan hard delete
            try {
                // Coba tangkap semua SQL errors dengan dengan force query
                DB::statement('SET session_replication_role = replica');

                // Gunakan query builder untuk delete (ini lebih safe dari model Eloquent delete)
                $deleteResult = DB::table('plantations')->where('id', $deletedId)->delete();

                // Kembalikan ke mode normal
                DB::statement('SET session_replication_role = DEFAULT');

                Log::info('Raw delete result:', ['result' => $deleteResult, 'id' => $deletedId]);
                Log::info('SQL Queries:', ['queries' => DB::getQueryLog()]);

                if ($deleteResult !== 1) {
                    throw new \Exception('Gagal menghapus data dari database (affected rows: ' . $deleteResult . ')');
                }
            } catch (\Exception $deleteEx) {
                Log::error('Error executing delete: ' . $deleteEx->getMessage(), [
                    'trace' => $deleteEx->getTraceAsString(),
                    'id' => $deletedId,
                    'exception_class' => get_class($deleteEx),
                    'sql_queries' => DB::getQueryLog()
                ]);

                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus blok kebun: ' . $deleteEx->getMessage(),
                    'error' => $deleteEx->getMessage()
                ], 500);
            }

            // Commit transaksi jika berhasil
            try {
                DB::commit();
                Log::info('Transaction committed successfully');
            } catch (\Exception $commitEx) {
                Log::error('Failed to commit transaction: ' . $commitEx->getMessage(), [
                    'sql_queries' => DB::getQueryLog()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menyimpan perubahan ke database: ' . $commitEx->getMessage(),
                    'error' => $commitEx->getMessage()
                ], 500);
            }

            Log::info('Plantation deleted successfully:', ['id' => $deletedId]);

            // Kirim response dengan data ID yang dihapus
            return response()->json([
                'success' => true,
                'message' => 'Blok kebun berhasil dihapus',
                'deleted_id' => $deletedId,
                'timestamp' => now()->timestamp
            ]);
        } catch (\Exception $e) {
            // Jika terjadi error di level atas, pastikan rollback dan log error
            try {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                    Log::info('Transaction rolled back');
                }
            } catch (\Exception $dbEx) {
                Log::error('Failed to rollback transaction: ' . $dbEx->getMessage());
            }

            Log::error('Error deleting plantation: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'id' => $id,
                'exception_class' => get_class($e),
                'sql_queries' => DB::getQueryLog()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus blok kebun: ' . $e->getMessage(),
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
