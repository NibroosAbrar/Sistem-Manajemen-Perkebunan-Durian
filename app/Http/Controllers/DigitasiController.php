<?php

namespace App\Http\Controllers;

use App\Models\Digitasi;
use App\Models\AerialPhoto;
use App\Models\Plantation;
use App\Models\Tree;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DigitasiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $digitasiList = Digitasi::with('plantation', 'aerialPhoto')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.digitasi.index', compact('digitasiList'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $aerialPhotos = AerialPhoto::orderBy('created_at', 'desc')->get();
        $plantations = Plantation::orderBy('name', 'asc')->get();

        return view('pages.digitasi.create', compact('aerialPhotos', 'plantations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'aerial_photo_id' => 'required|exists:aerial_photos,id',
            'plantation_id' => 'required|exists:plantations,id',
        ]);

        $digitasi = Digitasi::create([
            'name' => $request->name,
            'aerial_photo_id' => $request->aerial_photo_id,
            'plantation_id' => $request->plantation_id,
            'is_processed' => false,
            'tree_count' => 0
        ]);

        return redirect()->route('digitasi.index')
            ->with('success', 'Proses digitasi berhasil dibuat!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Digitasi $digitasi)
    {
        return view('pages.digitasi.show', compact('digitasi'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Digitasi $digitasi)
    {
        $aerialPhotos = AerialPhoto::orderBy('created_at', 'desc')->get();
        $plantations = Plantation::orderBy('name', 'asc')->get();

        return view('pages.digitasi.edit', compact('digitasi', 'aerialPhotos', 'plantations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Digitasi $digitasi)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'aerial_photo_id' => 'required|exists:aerial_photos,id',
            'plantation_id' => 'required|exists:plantations,id',
        ]);

        $digitasi->update([
            'name' => $request->name,
            'aerial_photo_id' => $request->aerial_photo_id,
            'plantation_id' => $request->plantation_id,
        ]);

        return redirect()->route('digitasi.index')
            ->with('success', 'Data digitasi berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Digitasi $digitasi)
    {
        // Cek apakah ada trees yang terkait
        if ($digitasi->trees()->count() > 0) {
            return redirect()->route('digitasi.index')
                ->with('error', 'Tidak dapat menghapus data karena sudah digunakan pada data pohon!');
        }

        $digitasi->delete();

        return redirect()->route('digitasi.index')
            ->with('success', 'Data digitasi berhasil dihapus!');
    }

    /**
     * API untuk menampilkan preview Aerial Photo berdasarkan ID
     */
    public function getAerialPhotoPreview($id)
    {
        try {
            \Illuminate\Support\Facades\Log::info('API dipanggil: aerial-photo/' . $id . '/preview');
            
            $aerialPhoto = AerialPhoto::findOrFail($id);
            
            // Simpan original path dari database untuk digunakan dengan asset('storage/')
            $originalPath = $aerialPhoto->path;
            
            \Illuminate\Support\Facades\Log::info('Original path dari database: ' . ($originalPath ?? 'null'));
            
            // Buat path API sebagai fallback
            $apiPath = url('/aerial-photo-image/' . $id);
            
            // Log informasi path
            \Illuminate\Support\Facades\Log::info('Path yang akan digunakan: ' . 
                ($originalPath ? 'original path' : 'api path'));
            
            $data = [
                'id' => $aerialPhoto->id,
                'path' => $apiPath, // Fallback path menggunakan API endpoint
                'original_path' => $originalPath, // Path asli dari database untuk digunakan dengan asset('storage/')
                'bounds' => $aerialPhoto->bounds,
                'resolution' => $aerialPhoto->resolution,
                'capture_time' => $aerialPhoto->capture_time,
            ];
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error di getAerialPhotoPreview: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data foto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper untuk membuat file fallback jika diperlukan
     */
    private function createFallbackImage($id)
    {
        try {
            // Buat direktori jika belum ada
            $fallbackDir = storage_path('app/public/aerial-photos/' . $id);
            if (!file_exists($fallbackDir)) {
                mkdir($fallbackDir, 0755, true);
            }
            
            // Buat placeholder image jika belum ada
            $fallbackPath = $fallbackDir . '/preview.jpg';
            if (!file_exists($fallbackPath)) {
                // Coba salin dari fallback yang ada
                if (file_exists(public_path('img/sample-aerial.jpg'))) {
                    copy(public_path('img/sample-aerial.jpg'), $fallbackPath);
                    \Illuminate\Support\Facades\Log::info('Fallback image created at: ' . $fallbackPath);
                }
                // Atau buat file kosong
                else {
                    file_put_contents($fallbackPath, 'Placeholder Image');
                    \Illuminate\Support\Facades\Log::info('Empty fallback image created at: ' . $fallbackPath);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error creating fallback image: ' . $e->getMessage());
        }
    }

    /**
     * API untuk menampilkan preview geometri Plantation berdasarkan ID
     */
    public function getPlantationPreview($id)
    {
        try {
            \Illuminate\Support\Facades\Log::info('API dipanggil: plantation/' . $id . '/preview');
            
            $plantation = Plantation::findOrFail($id);
            
            // Konversi geometri ke GeoJSON untuk preview
            $geojson = null;
            if ($plantation->geometry) {
                $result = DB::select("SELECT ST_AsGeoJSON(geometry) as geojson FROM plantations WHERE id = ?", [$id]);
                if (!empty($result) && isset($result[0]->geojson)) {
                    $geojson = $result[0]->geojson;
                    \Illuminate\Support\Facades\Log::info('Geometry berhasil dikonversi ke GeoJSON untuk plantation ID ' . $id);
                } else {
                    \Illuminate\Support\Facades\Log::warning('Plantation ID ' . $id . ' memiliki geometry tetapi gagal dikonversi ke GeoJSON');
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('Plantation ID ' . $id . ' tidak memiliki data geometry');
            }
            
            $data = [
                'id' => $plantation->id,
                'name' => $plantation->name,
                'luas_area' => $plantation->luas_area,
                'geojson' => $geojson
            ];
            
            \Illuminate\Support\Facades\Log::info('Mengembalikan data preview plantation: ' . json_encode([
                'id' => $plantation->id,
                'name' => $plantation->name,
                'luas_area' => $plantation->luas_area,
                'has_geojson' => !empty($geojson)
            ]));
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error di getPlantationPreview: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data area: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API untuk melakukan deteksi pohon dengan YOLO
     */
    public function detectTrees(Request $request)
    {
        $request->validate([
            'aerial_photo_id' => 'required|exists:aerial_photos,id',
            'plantation_id' => 'required|exists:plantations,id',
        ]);

        try {
            // Ambil data aerial photo dan plantation
            $aerialPhoto = AerialPhoto::findOrFail($request->aerial_photo_id);
            $plantation = Plantation::findOrFail($request->plantation_id);
            
            // Jalankan deteksi YOLO (simulasi)
            // Implementasi nyata akan memanggil Python atau endpoint API YOLO
            $treeCount = rand(10, 200); // Simulasi jumlah pohon yang terdeteksi
            
            // Generate simulasi data vektor kanopi (dalam format GeoJSON)
            $treeVectors = $this->simulateTreeVectors($plantation->geometry, $treeCount);
            
            return response()->json([
                'success' => true,
                'tree_count' => $treeCount,
                'preview_data' => $treeVectors,
                'message' => 'Deteksi pohon berhasil!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API untuk menyimpan hasil deteksi pohon
     */
    public function saveDetection(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'aerial_photo_id' => 'required|exists:aerial_photos,id',
            'plantation_id' => 'required|exists:plantations,id',
            'tree_count' => 'required|integer|min:0',
            'geometry' => 'required|string', // Data geometri dalam format WKT
        ]);

        try {
            $digitasi = Digitasi::create([
                'name' => $request->name,
                'aerial_photo_id' => $request->aerial_photo_id,
                'plantation_id' => $request->plantation_id,
                'tree_count' => $request->tree_count,
                'geometry' => $request->geometry,
                'is_processed' => true,
                'detection_data' => $request->detection_data
            ]);

            return response()->json([
                'success' => true,
                'data' => $digitasi,
                'message' => 'Hasil deteksi berhasil disimpan!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan hasil deteksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API untuk mengimpor hasil deteksi ke tabel trees
     */
    public function importToTrees(Digitasi $digitasi)
    {
        if ($digitasi->is_imported_to_trees) {
            return redirect()->route('digitasi.show', $digitasi->id)
                ->with('error', 'Data sudah diimpor sebelumnya!');
        }

        try {
            // Mulai transaksi database
            DB::beginTransaction();
            
            // Ekstrak data pohon dari digitasi
            $treeGeometries = $this->extractTreeGeometries($digitasi->geometry);
            
            // Buat record tree untuk setiap geometri kanopi
            foreach ($treeGeometries as $index => $geom) {
                Tree::create([
                    'id' => 'TREE-' . Str::uuid(),
                    'plantation_id' => $digitasi->plantation_id,
                    'digitasi_id' => $digitasi->id,
                    'varietas' => 'Durian',
                    'tahun_tanam' => date('Y'),
                    'health_status' => 'Baik',
                    'canopy_geometry' => $geom,
                    'latitude' => $this->extractCentroidY($geom),
                    'longitude' => $this->extractCentroidX($geom),
                ]);
            }
            
            // Update status digitasi menjadi sudah diimpor
            $digitasi->update([
                'is_imported_to_trees' => true
            ]);
            
            // Commit transaksi
            DB::commit();
            
            return redirect()->route('digitasi.show', $digitasi->id)
                ->with('success', 'Berhasil mengimpor ' . count($treeGeometries) . ' data pohon!');
        } catch (\Exception $e) {
            // Rollback transaksi jika gagal
            DB::rollBack();
            
            return redirect()->route('digitasi.show', $digitasi->id)
                ->with('error', 'Gagal mengimpor data: ' . $e->getMessage());
        }
    }

    /**
     * Simulasi data vektor kanopi pohon (untuk testing)
     */
    private function simulateTreeVectors($plantationGeometry, $count)
    {
        // Ambil bounds dari plantation geometry
        // Note: Implementasi sebenarnya perlu menggunakan library GIS
        $bounds = $this->getDummyBounds();
        
        $trees = [];
        for ($i = 0; $i < $count; $i++) {
            // Generate random point dalam bounds
            $lat = $bounds['minLat'] + ($bounds['maxLat'] - $bounds['minLat']) * mt_rand(0, 1000) / 1000;
            $lng = $bounds['minLng'] + ($bounds['maxLng'] - $bounds['minLng']) * mt_rand(0, 1000) / 1000;
            
            // Generate radius random antara 2-5 meter
            $radius = 2 + mt_rand(0, 30) / 10;
            
            // Buat circle polygon sebagai kanopi
            $trees[] = $this->createCirclePolygon($lat, $lng, $radius);
        }
        
        return [
            'type' => 'FeatureCollection',
            'features' => $trees
        ];
    }

    /**
     * Buat polygon lingkaran (untuk simulasi)
     */
    private function createCirclePolygon($lat, $lng, $radius)
    {
        $vertices = 32;
        $coords = [];
        
        for ($i = 0; $i < $vertices; $i++) {
            $angle = ($i / $vertices) * 2 * pi();
            $dx = $radius * cos($angle) * 0.00001; // Konversi ke derajat approx
            $dy = $radius * sin($angle) * 0.00001;
            $coords[] = [$lng + $dx, $lat + $dy];
        }
        
        // Tutup polygon dengan menambahkan titik pertama di akhir
        $coords[] = $coords[0];
        
        return [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [$coords]
            ],
            'properties' => [
                'radius' => $radius,
                'center' => [$lng, $lat]
            ]
        ];
    }

    /**
     * Get dummy bounds untuk simulasi
     */
    private function getDummyBounds()
    {
        return [
            'minLat' => -6.2,
            'maxLat' => -6.19,
            'minLng' => 106.8,
            'maxLng' => 106.81
        ];
    }

    /**
     * Ekstrak geometri kanopi pohon dari WKT multi-polygon
     */
    private function extractTreeGeometries($multipolygonWkt)
    {
        // Implementasi sebenarnya perlu library GIS untuk parsing WKT
        // Ini hanya simulasi
        $geometries = [];
        
        // Buat 10 geometri dummy untuk simulasi
        for ($i = 0; $i < 10; $i++) {
            $geometries[] = "POLYGON((106.8 -6.2, 106.801 -6.2, 106.801 -6.201, 106.8 -6.201, 106.8 -6.2))";
        }
        
        return $geometries;
    }

    /**
     * Ekstrak koordinat Y (latitude) dari centroid geometri
     */
    private function extractCentroidY($geometryWkt)
    {
        // Implementasi sederhana, seharusnya menggunakan PostGIS
        // Untuk simulasi, return nilai random
        return -6.2 + mt_rand(0, 100) / 10000;
    }

    /**
     * Ekstrak koordinat X (longitude) dari centroid geometri
     */
    private function extractCentroidX($geometryWkt)
    {
        // Implementasi sederhana, seharusnya menggunakan PostGIS
        // Untuk simulasi, return nilai random
        return 106.8 + mt_rand(0, 100) / 10000;
    }

    /**
     * API untuk mendapatkan daftar plantations yang terkait dengan aerial photo
     */
    public function getPlantationsByAerialPhoto($aerialPhotoId)
    {
        try {
            \Illuminate\Support\Facades\Log::info('API dipanggil: plantations-by-aerial-photo/' . $aerialPhotoId);
            
            // Ambil data aerial photo
            $aerialPhoto = AerialPhoto::findOrFail($aerialPhotoId);
            
            // Ambil semua plantation
            $plantations = Plantation::select(
                'id',
                'name',
                'luas_area',
                'geometry'
            )
            ->orderBy('name')
            ->get();
            
            // Format data untuk response
            $formattedPlantations = [];
            foreach ($plantations as $plantation) {
                // Konversi geometry ke GeoJSON
                $geojson = null;
                if ($plantation->geometry) {
                    try {
                        $result = DB::select("SELECT ST_AsGeoJSON(geometry) as geojson FROM plantations WHERE id = ?", [$plantation->id]);
                        if (!empty($result) && isset($result[0]->geojson)) {
                            $geojson = json_decode($result[0]->geojson);
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Error converting geometry to GeoJSON: ' . $e->getMessage());
                    }
                }
                
                $formattedPlantations[] = [
                    'id' => $plantation->id,
                    'name' => $plantation->name,
                    'luas_area' => $plantation->luas_area,
                    'geometry' => $geojson
                ];
            }
            
            \Illuminate\Support\Facades\Log::info('Mengembalikan data plantation: ' . count($formattedPlantations) . ' items');
            
            // Kembalikan response dengan data array
            return response()->json([
                'success' => true,
                'data' => $formattedPlantations
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error fetching plantations: ' . $e->getMessage());
            
            // Selalu kembalikan array kosong
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mendapatkan daftar semua plantation tanpa filter
     */
    public function getAllPlantations()
    {
        try {
            \Illuminate\Support\Facades\Log::info('API dipanggil: plantation/list');
            
            // Ambil semua plantation
            $plantations = Plantation::select(
                'id',
                'name',
                'luas_area',
                'geometry'
            )
            ->orderBy('name')
            ->get();
            
            // Format data untuk response
            $formattedPlantations = [];
            foreach ($plantations as $plantation) {
                // Konversi geometry ke GeoJSON
                $geojson = null;
                if ($plantation->geometry) {
                    try {
                        $result = DB::select("SELECT ST_AsGeoJSON(geometry) as geojson FROM plantations WHERE id = ?", [$plantation->id]);
                        if (!empty($result) && isset($result[0]->geojson)) {
                            $geojson = json_decode($result[0]->geojson);
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Error converting geometry to GeoJSON: ' . $e->getMessage());
                    }
                }
                
                $formattedPlantations[] = [
                    'id' => $plantation->id,
                    'name' => $plantation->name,
                    'luas_area' => $plantation->luas_area,
                    'geometry' => $geojson
                ];
            }
            
            \Illuminate\Support\Facades\Log::info('Mengembalikan daftar plantation: ' . count($formattedPlantations) . ' items');
            
            // Kembalikan response dengan data array
            return response()->json([
                'success' => true,
                'data' => $formattedPlantations
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error fetching all plantations: ' . $e->getMessage());
            
            // Selalu kembalikan array kosong untuk memastikan respons bisa di-iterasi
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan langsung gambar foto udara dari database
     */
    public function getAerialPhotoImage($id)
    {
        try {
            \Illuminate\Support\Facades\Log::info('API dipanggil: aerial-photo-image/' . $id);
            
            // Cari data aerial photo
            $aerialPhoto = AerialPhoto::findOrFail($id);
            
            // Cek prioritas lokasi file
            $filePath = storage_path('app/public/aerial-photos/' . $id . '/preview.jpg');
            if (file_exists($filePath)) {
                return response()->file($filePath);
            }
            
            // Alternatif path di storage
            $altPath = storage_path('app/public/aerial-photos/' . $id . '.jpg');
            if (file_exists($altPath)) {
                return response()->file($altPath);
            }
            
            // Cek path dari database
            if ($aerialPhoto->path) {
                $dbPath = storage_path('app/public/' . $aerialPhoto->path);
                if (file_exists($dbPath)) {
                    return response()->file($dbPath);
                }
            }
              
            // Cek apakah ada kolom blob/binary di database
            if (isset($aerialPhoto->image_data) && $aerialPhoto->image_data) {
                \Illuminate\Support\Facades\Log::info('File ditemukan di kolom blob database');
                $imageData = $aerialPhoto->image_data;
                return response($imageData)
                    ->header('Content-Type', 'image/jpeg');
            }
            
            // Jika masih tidak ditemukan, lihat jika ada gambar sample di public/img
            $samplePath = public_path('img/sample-aerial.jpg');
            if (file_exists($samplePath)) {
                \Illuminate\Support\Facades\Log::warning('Menggunakan sample aerial sebagai fallback terakhir');
                return response()->file($samplePath);
            }
            
            // Jika tidak ada file ditemukan di semua lokasi
            return response()->json([
                'error' => 'Tidak dapat menemukan file gambar di semua lokasi yang dicek',
                'id' => $id,
                'paths_checked' => [$filePath, $altPath, isset($dbPath) ? $dbPath : null]
            ], 404);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error menampilkan foto udara: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve image: ' . $e->getMessage()], 500);
        }
    }
}
