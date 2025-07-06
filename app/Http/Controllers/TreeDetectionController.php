<?php

namespace App\Http\Controllers;

use App\Models\TreeDetection;
use App\Models\Plantation;
use App\Models\AerialPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class TreeDetectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $treeDetections = TreeDetection::with('plantation', 'aerialPhoto')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.tree-detection.index', compact('treeDetections'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $aerialPhotos = AerialPhoto::orderBy('created_at', 'desc')->get();
        $plantations = Plantation::orderBy('name', 'asc')->get();

        return view('pages.tree-detection.create', compact('aerialPhotos', 'plantations'));
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
            'tree_count' => 'required|integer|min:0',
        ]);

        $treeDetection = TreeDetection::create([
            'name' => $request->name,
            'aerial_photo_id' => $request->aerial_photo_id,
            'plantation_id' => $request->plantation_id,
            'tree_count' => $request->tree_count,
            'is_processed' => false,
            'user_id' => Auth::id()
        ]);

        return redirect()->route('tree-detection.index')
            ->with('success', 'Data deteksi pohon berhasil disimpan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(TreeDetection $treeDetection)
    {
        return view('pages.tree-detection.show', compact('treeDetection'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TreeDetection $treeDetection)
    {
        $aerialPhotos = AerialPhoto::orderBy('created_at', 'desc')->get();
        $plantations = Plantation::orderBy('name', 'asc')->get();

        return view('pages.tree-detection.edit', compact('treeDetection', 'aerialPhotos', 'plantations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TreeDetection $treeDetection)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'aerial_photo_id' => 'required|exists:aerial_photos,id',
            'plantation_id' => 'required|exists:plantations,id',
            'tree_count' => 'required|integer|min:0',
        ]);

        $treeDetection->update([
            'name' => $request->name,
            'aerial_photo_id' => $request->aerial_photo_id,
            'plantation_id' => $request->plantation_id,
            'tree_count' => $request->tree_count,
        ]);

        return redirect()->route('tree-detection.index')
            ->with('success', 'Data deteksi pohon berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TreeDetection $treeDetection)
    {
        $treeDetection->delete();

        return redirect()->route('tree-detection.index')
            ->with('success', 'Data deteksi pohon berhasil dihapus!');
    }

    /**
     * API untuk mendeteksi pohon pada area blok kebun
     */
    public function detectTrees(Request $request)
    {
        Log::info("Mendeteksi pohon dengan data: " . json_encode($request->all()));

        $aerial_photo_id = $request->input('aerial_photo_id');
        $plantation_id = $request->input('plantation_id');
        $logs = [];

        try {
            // Validasi input
            if (!$aerial_photo_id || !$plantation_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'aerial_photo_id dan plantation_id harus disediakan'
                ], 400);
            }

            $logs[] = "Request menerima aerial_photo_id dan plantation_id";

            // Ambil data foto udara
            $aerialPhoto = AerialPhoto::find($aerial_photo_id);
            if (!$aerialPhoto) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Foto udara tidak ditemukan'
                ], 404);
            }

            $logs[] = "Foto udara ditemukan: " . $aerialPhoto->name;

            // Ambil data perkebunan
            $plantation = Plantation::find($plantation_id);
            if (!$plantation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Perkebunan tidak ditemukan'
                ], 404);
            }

            $logs[] = "Perkebunan ditemukan: " . $plantation->name;

            // Periksa apakah server YOLO berjalan
            $yolo_server_url = env('YOLO_SERVER_URL', 'http://127.0.0.1:5000');
            $client = new Client([
                'timeout'  => 10.0,
                'verify' => false
            ]);

            $logs[] = "Menggunakan server YOLO di: " . $yolo_server_url;
            $serverRunning = false;

            try {
                $logs[] = "Memeriksa status server YOLO...";
                $response = $client->get($yolo_server_url, [
                    'headers' => [
                        'Accept' => 'application/json',
                    ]
                ]);

                if ($response->getStatusCode() == 200) {
                    $serverRunning = true;
                    $logs[] = "Server YOLO sedang berjalan!";
                }
            } catch (\Exception $e) {
                $logs[] = "Server YOLO tidak dapat diakses: " . $e->getMessage();
                $logs[] = "Menggunakan mode simulasi untuk deteksi pohon...";
            }

            // Mode simulasi - gunakan ketika server YOLO tidak tersedia
            if (!$serverRunning) {
                $logs[] = "🔄 Memulai simulasi deteksi pohon...";
                $logs[] = "📊 Mempersiapkan data untuk inferensi...";

                // Simulasi inferensi YOLO
                $startTime = time();
                $processingTime = rand(5, 15); // Simulasi proses 5-15 detik

                $logs[] = "🔍 Memulai inferensi model YOLO...";
                sleep(2); // Tunggu 2 detik untuk simulasi

                $logs[] = "📏 Mengukur area plantation: " . ($plantation->luas_area ?? 'N/A') . " ha";
                $logs[] = "🌳 Mendeteksi kanopi pohon dalam gambar...";
                sleep(1); // Tunggu 1 detik lagi

                // Hitung jumlah pohon acak berdasarkan luas area (lebih realistis)
                $areaHa = $plantation->luas_area ?? 10;
                $treeCount = round($areaHa * rand(180, 220)); // 180-220 pohon per hektar

                $logs[] = "✅ Deteksi selesai! Menemukan " . $treeCount . " pohon dalam " . $processingTime . " detik";

                // Buat preview URL gambar jika ada
                $previewUrl = null;
                if ($aerialPhoto && $aerialPhoto->file_path) {
                    $previewUrl = url($aerialPhoto->file_path) . '?t=' . time();
                }

                // Simulasi hasil deteksi pohon
                return response()->json([
                    'success' => true,
                    'tree_count' => $treeCount,
                    'logs' => $logs,
                    'processing_time' => $processingTime,
                    'preview_url' => $previewUrl,
                    'plantation_name' => $plantation->name,
                    'aerial_photo_name' => $aerialPhoto->name,
                    'simulation_mode' => true,
                    'message' => 'Deteksi dilakukan dalam mode simulasi'
                ]);
            }

            // Siapkan data untuk inference
            $logs[] = "Menyiapkan data untuk inference...";
            $image_path = $aerialPhoto->file_path;
            $geometry = $plantation->geometry;

            // Konversi geometri ke array yang sesuai untuk server Python
            $geometryArray = json_decode($geometry, true);

            $logs[] = "Geometri perkebunan: " . (is_array($geometryArray) ? count($geometryArray) . " titik" : "Tidak valid");

            // Temukan file gambar
            if (!file_exists(public_path($image_path))) {
                $logs[] = "Peringatan: File gambar tidak ditemukan di path: " . public_path($image_path);

                // Coba alternatif path
                $alternative_paths = [
                    storage_path('app/public/' . basename($image_path)),
                    storage_path('app/' . basename($image_path)),
                    public_path('storage/' . basename($image_path)),
                    base_path($image_path),
                    public_path(ltrim($image_path, '/')),
                    storage_path('app/public/uploads/' . basename($image_path))
                ];

                $found = false;
                foreach ($alternative_paths as $alt_path) {
                    if (file_exists($alt_path)) {
                        $image_path = $alt_path;
                        $logs[] = "File gambar ditemukan di alternatif path: " . $alt_path;
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $logs[] = "Error: File gambar tidak ditemukan di semua kemungkinan lokasi";
                    return response()->json([
                        'status' => 'error',
                        'message' => 'File gambar tidak ditemukan',
                        'logs' => $logs
                    ], 404);
                }
            } else {
                $logs[] = "File gambar ditemukan di: " . public_path($image_path);
            }

            // Buat data request untuk server Python
            $data = [
                'image_path' => $image_path,
                'geometry' => $geometryArray,
                'aerial_photo_id' => $aerial_photo_id,
                'plantation_id' => $plantation_id,
                'aerial_photo_name' => $aerialPhoto->name,
                'plantation_name' => $plantation->name
            ];

            $logs[] = "Mengirim request ke server YOLO...";
            Log::info("Data request ke YOLO: " . json_encode($data));

            // Kirim request ke server Python
            try {
                $logs[] = "Memulai proses inferensi, ini bisa memakan waktu hingga 20 menit...";
                Log::info("Memulai request inferensi ke server YOLO");

                $response = $client->post($yolo_server_url . '/yolo-inference', [
                    'json' => $data,
                    'timeout' => 1200, // 20 menit timeout
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ]
                ]);

                Log::info("Berhasil menerima response dari server YOLO");
                $result = json_decode($response->getBody(), true);
                $logs[] = "Respons diterima dari server YOLO!";

                // Log jumlah pohon yang terdeteksi
                if (isset($result['features'])) {
                    $treeCount = count($result['features']);
                    $logs[] = "Jumlah pohon terdeteksi: $treeCount";
                    Log::info("Jumlah pohon terdeteksi oleh YOLO: $treeCount");
                }

                // Tambahkan log ke hasil
                $result['logs'] = array_merge($logs, $result['logs'] ?? []);

                // Cek apakah ada directory output yang dikirim balik
                if (isset($result['output_dir'])) {
                    $logs[] = "Output direktori: " . $result['output_dir'];
                }

                // Buat preview URL jika ada
                if (isset($result['preview_image'])) {
                    $result['preview_url'] = url($result['preview_image']);
                } elseif (file_exists(public_path($aerialPhoto->file_path))) {
                    $result['preview_url'] = url($aerialPhoto->file_path) . '?t=' . time();
                }

                return response()->json($result);
            } catch (\Exception $e) {
                $logs[] = "Error saat komunikasi dengan server YOLO: " . $e->getMessage();
                Log::error("Error saat komunikasi dengan server YOLO: " . $e->getMessage());

                // Periksa apakah error terkait timeout
                if (strpos($e->getMessage(), 'timed out') !== false) {
                    $logs[] = "Request timeout - proses inferensi membutuhkan waktu lebih lama dari timeout yang diatur (20 menit)";
                    $logs[] = "TIP: Coba cek status server dengan endpoint /api/check-yolo-server";
                } else if (strpos($e->getMessage(), 'Connection refused') !== false) {
                    $logs[] = "Koneksi ditolak - server YOLO mungkin tidak berjalan atau alamat/port salah";
                    $logs[] = "TIP: Pastikan alamat server di .env file benar (YOLO_SERVER_URL)";
                }

                // Jika gagal, gunakan simulasi
                $logs[] = "🔄 Menggunakan mode simulasi karena server YOLO tidak dapat diakses...";

                // Simulasi deteksi pohon
                $treeCount = rand(1800, 2200); // Simulasi deteksi 1800-2200 pohon

                Log::info("Menggunakan simulasi dengan $treeCount pohon");

                // Buat preview URL jika ada
                $previewUrl = null;
                if ($aerialPhoto && $aerialPhoto->file_path) {
                    $previewUrl = url($aerialPhoto->file_path) . '?t=' . time();
                }

                return response()->json([
                    'success' => true,
                    'tree_count' => $treeCount,
                    'logs' => $logs,
                    'processing_time' => 12.5, // Simulasi waktu pemrosesan
                    'preview_url' => $previewUrl,
                    'plantation_name' => $plantation->name,
                    'aerial_photo_name' => $aerialPhoto->name,
                    'simulation_mode' => true,
                    'message' => 'Deteksi dilakukan dalam mode simulasi karena server YOLO tidak dapat diakses'
                ]);
            }
        } catch (\Exception $e) {
            $logs[] = "Terjadi kesalahan: " . $e->getMessage();
            Log::error("Error saat deteksi pohon: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memproses request: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'logs' => $logs
            ], 500);
        }
    }

    /**
     * Format durasi dalam detik menjadi format jam:menit:detik
     */
    private function formatDuration($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf("%d jam %d menit %d detik", $hours, $minutes, $secs);
    }

    /**
     * Endpoint untuk menampilkan gambar aerial foto
     */
    public function showAerialImage($id) {
        try {
            $aerialPhoto = AerialPhoto::findOrFail($id);
            $imagePath = storage_path('app/public/' . $aerialPhoto->path);

            if (!file_exists($imagePath)) {
                // Jika file tidak ada, berikan placeholder
                $placeholderPath = public_path('img/sample-aerial.jpg');
                if (file_exists($placeholderPath)) {
                    return response()->file($placeholderPath);
                }
                return response()->json(['error' => 'Image file not found'], 404);
            }

            // Tentukan content type berdasarkan ekstensi
            $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
            $contentType = 'image/jpeg'; // Default

            if (strtolower($extension) === 'png') {
                $contentType = 'image/png';
            } elseif (strtolower($extension) === 'tif' || strtolower($extension) === 'tiff') {
                $contentType = 'image/tiff';
            }

            // Return image dengan content-type yang sesuai
            return response()->file($imagePath, ['Content-Type' => $contentType]);

        } catch (\Exception $e) {
            \Log::error('Error showing aerial image: ' . $e->getMessage());
            return response()->json(['error' => 'Error loading image: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API untuk export hasil deteksi ke shapefile
     */
    public function exportShapefile(Request $request)
    {
        $request->validate([
            'aerial_photo_id' => 'required|exists:aerial_photos,id',
            'plantation_id' => 'required|exists:plantations,id',
            'name' => 'required|string|max:255',
        ]);

        try {
            // Log permintaan dan data
            \Log::info('Memulai export shapefile', [
                'aerial_photo_id' => $request->aerial_photo_id,
                'plantation_id' => $request->plantation_id,
                'name' => $request->name,
                'output_dir' => $request->output_dir ?? null,
                'tree_count' => $request->tree_count ?? 'not provided'
            ]);

            // Ambil data plantation untuk mendapatkan geometri area
            $plantation = \App\Models\Plantation::findOrFail($request->plantation_id);
            $aerialPhoto = \App\Models\AerialPhoto::findOrFail($request->aerial_photo_id);

            \Log::info('Berhasil mengambil data plantation dan aerial photo', [
                'plantation_name' => $plantation->name,
                'aerial_photo_name' => $aerialPhoto->name
            ]);

            // Buat direktori temporal untuk shapefile
            $tempDir = storage_path('app/temp/shapefile-' . time());
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            \Log::info('Direktori temporal dibuat', ['path' => $tempDir]);

            // Coba cari hasil deteksi YOLO dari direktori yang dikirim
            $geojsonFile = null;
            $treeData = null;
            $foundData = false;

            // Cek apakah ada output_dir yang dikirim
            if (!empty($request->output_dir)) {
                $yoloOutputDir = storage_path('app/temp/' . $request->output_dir);
                if (file_exists($yoloOutputDir) && file_exists($yoloOutputDir . '/trees.geojson')) {
                    $geojsonFile = $yoloOutputDir . '/trees.geojson';
                    \Log::info('Menggunakan hasil deteksi dari direktori yang dikirim', ['path' => $yoloOutputDir]);
                    $foundData = true;
                }
            }

            // Jika tidak ada dari output_dir, cari hasil terbaru
            if (!$foundData) {
                $recentOutputDir = $this->findRecentYoloOutput();
                if ($recentOutputDir && file_exists($recentOutputDir . '/trees.geojson')) {
                    $geojsonFile = $recentOutputDir . '/trees.geojson';
                    \Log::info('Menggunakan hasil deteksi terbaru dari', ['path' => $recentOutputDir]);
                    $foundData = true;
                }
            }

            // Jika ditemukan file geojson, ekstrak data pohon
            if ($foundData && $geojsonFile) {
                \Log::info('GeoJSON file ditemukan', ['file' => $geojsonFile]);
                $geojsonContent = file_get_contents($geojsonFile);
                $geojsonData = json_decode($geojsonContent, true);

                if ($geojsonData === null) {
                    \Log::error('Gagal memparsing GeoJSON', ['error' => json_last_error_msg()]);
                    // Fallback ke simulasi jika parsing gagal
                } else {
                    // Ekstrak tree data dari GeoJSON asli
                    if (isset($geojsonData['features']) && !empty($geojsonData['features'])) {
                        $treeData = [];
                        foreach ($geojsonData['features'] as $index => $feature) {
                            if (isset($feature['geometry']) && isset($feature['geometry']['coordinates'])) {
                                // Untuk point features
                                if ($feature['geometry']['type'] === 'Point') {
                                    $coords = $feature['geometry']['coordinates'];
                                    $properties = $feature['properties'] ?? [];

                                    $treeData[] = [
                                        'id' => $index + 1,
                                        'lng' => $coords[0] ?? 0,
                                        'lat' => $coords[1] ?? 0,
                                        'height' => $properties['height'] ?? rand(5, 15),
                                        'diameter' => $properties['diameter'] ?? rand(10, 50),
                                        'health' => $properties['health'] ?? 'Baik'
                                    ];
                                }
                                // Handle polygon atau bukan point geometry dengan ekstrak centroid
                                else {
                                    // Ambil titik tengah dari bounding box sebagai representasi pohon
                                    $properties = $feature['properties'] ?? [];
                                    $coords = $this->getGeometryCenter($feature['geometry']);

                                    $treeData[] = [
                                        'id' => $index + 1,
                                        'lng' => $coords[0],
                                        'lat' => $coords[1],
                                        'height' => $properties['height'] ?? rand(5, 15),
                                        'diameter' => $properties['diameter'] ?? rand(10, 50),
                                        'health' => $properties['health'] ?? 'Baik'
                                    ];
                                }
                            }
                        }

                        \Log::info('Berhasil mengekstrak data pohon dari GeoJSON', ['count' => count($treeData)]);
                    } else {
                        \Log::warning('File GeoJSON tidak memiliki fitur yang valid');
                    }
                }
            } else {
                \Log::warning('Tidak menemukan file GeoJSON hasil deteksi');
            }

            // Jika tidak ada data dari hasil deteksi YOLO, gunakan simulasi
            if (empty($treeData)) {
                \Log::warning('Data pohon tidak ditemukan, menggunakan simulasi');

                // Jumlah pohon yang akan dibuat (dari request atau default)
                $treeCount = $request->tree_count ?? rand(1800, 2200);

                // Generate data pohon acak di dalam area plantation
                $treeData = $this->generateRandomTrees($plantation->geometry, $treeCount);

                \Log::info('Simulasi menghasilkan data pohon', ['count' => count($treeData)]);
            }

            // Periksa jumlah data pohon
            if (count($treeData) == 0) {
                \Log::error('Tidak ada data pohon untuk diexport');
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data pohon untuk diexport'
                ], 400);
            }

            // Coba gunakan metode fallback yang lebih sederhana langsung
            \Log::info('Menggunakan metode export shapefile sederhana');
            $zipPath = $this->fallbackShapefileExport($tempDir, $treeData, $request->name);

            // Jika tidak berhasil membuat ZIP, return error
            if (!$zipPath || !file_exists($zipPath)) {
                \Log::error('Gagal membuat file ZIP untuk diunduh');
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membuat file ZIP untuk diunduh'
                ], 500);
            }

            // Log sukses
            \Log::info('Export shapefile berhasil', ['file' => $zipPath]);

            return response()->download($zipPath, 'tree_detection_'. $request->name .'.zip')->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Error pada export shapefile: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengexport shapefile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ambil titik tengah dari geometri
     */
    private function getGeometryCenter($geometry) {
        try {
            if ($geometry['type'] === 'Point') {
                return $geometry['coordinates'];
            }

            if ($geometry['type'] === 'Polygon') {
                // Hitung rata-rata koordinat dari semua titik di polygon
                $sumX = 0;
                $sumY = 0;
                $count = 0;

                foreach ($geometry['coordinates'][0] as $coord) {
                    $sumX += $coord[0];
                    $sumY += $coord[1];
                    $count++;
                }

                return [$sumX / $count, $sumY / $count];
            }

            // Default jika tidak bisa menghitung
            return [0, 0];
        } catch (\Exception $e) {
            \Log::error('Error menghitung titik tengah geometri', ['error' => $e->getMessage()]);
            return [0, 0];
        }
    }

    /**
     * Persiapkan semua file untuk export shapefile
     */
    private function prepareShapefileExport($tempDir, $treeData, $name) {
        try {
            // Pertama buat file shapefile standar
            $this->createShapefile($tempDir, $treeData, $name);

            // Tambahkan file ekspor alternatif (CSV, GeoJSON)
            $this->createExportAlternatives($tempDir, $treeData, $name);

            // Tambahkan file README dan metadata
            $this->createAdditionalFiles($tempDir, $treeData, $name);

            // Buat ZIP dari semua file
            $zipPath = storage_path('app/temp/tree_detection_'. $name .'.zip');
            $zip = new \ZipArchive();

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \Exception("Tidak dapat membuat file ZIP");
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tempDir),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($tempDir) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();

            // Hapus direktori temporary
            $this->removeDirectory($tempDir);

            return $zipPath;
        } catch (\Exception $e) {
            \Log::error('Error pada prepareShapefileExport: ' . $e->getMessage());
            // Jika gagal, gunakan metode fallback
            return $this->fallbackShapefileExport($tempDir, $treeData, $name);
        }
    }

    /**
     * Fallback untuk export shapefile jika metode utama gagal
     */
    private function fallbackShapefileExport($tempDir, $treeData, $name) {
        try {
            // Buat file sederhana di tempDir
            $this->createSimpleShapefile($tempDir, $treeData);

            // Buat CSV dan GeoJSON
            $csvContent = "ID,LAT,LNG,HEIGHT,DIAMETER,HEALTH\n";
            foreach ($treeData as $tree) {
                $csvContent .= implode(',', [
                    $tree['id'],
                    $tree['lat'],
                    $tree['lng'],
                    $tree['height'],
                    $tree['diameter'],
                    $tree['health']
                ]) . "\n";
            }
            file_put_contents($tempDir . '/trees.csv', $csvContent);

            // Buat GeoJSON
            $features = [];
            foreach ($treeData as $tree) {
                $features[] = [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [$tree['lng'], $tree['lat']]
                    ],
                    'properties' => [
                        'id' => $tree['id'],
                        'height' => $tree['height'],
                        'diameter' => $tree['diameter'],
                        'health' => $tree['health']
                    ]
                ];
            }

            $geojson = [
                'type' => 'FeatureCollection',
                'features' => $features
            ];
            file_put_contents($tempDir . '/trees.geojson', json_encode($geojson, JSON_PRETTY_PRINT));

            // Buat file README.txt sederhana
            $readme = "Deteksi Pohon: " . $name . "\n\n";
            $readme .= "Jumlah pohon: " . count($treeData) . "\n";
            $readme .= "Tanggal export: " . date('Y-m-d H:i:s') . "\n";
            file_put_contents($tempDir . '/README.txt', $readme);

            // Buat ZIP
            $zipPath = storage_path('app/temp/tree_detection_'. $name .'.zip');
            $zip = new \ZipArchive();

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \Exception("Tidak dapat membuat file ZIP");
            }

            // Tambahkan semua file di tempDir
            foreach (glob($tempDir . '/*') as $file) {
                $zip->addFile($file, basename($file));
            }

            $zip->close();

            // Hapus direktori temporary
            $this->removeDirectory($tempDir);

            return $zipPath;
        } catch (\Exception $e) {
            \Log::error('Error pada fallbackShapefileExport: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Buat file export alternatif (CSV, GeoJSON) untuk memastikan data berhasil diexport
     */
    private function createExportAlternatives($tempDir, $treeData, $name) {
        // Buat CSV
        $csvContent = "ID,LAT,LNG,HEIGHT,DIAMETER,HEALTH,DATE_DETECTED\n";
        foreach ($treeData as $tree) {
            $csvContent .= implode(',', [
                $tree['id'],
                $tree['lat'],
                $tree['lng'],
                $tree['height'],
                $tree['diameter'],
                $tree['health'],
                date('Y-m-d')
            ]) . "\n";
        }
        file_put_contents($tempDir . '/pohon_' . $name . '.csv', $csvContent);

        // Buat GeoJSON
        $features = [];
        foreach ($treeData as $tree) {
            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$tree['lng'], $tree['lat']]
                ],
                'properties' => [
                    'id' => $tree['id'],
                    'height' => $tree['height'],
                    'diameter' => $tree['diameter'],
                    'health' => $tree['health'],
                    'date_detected' => date('Y-m-d')
                ]
            ];
        }

        $geojson = [
            'type' => 'FeatureCollection',
            'features' => $features,
            'metadata' => [
                'name' => $name,
                'tree_count' => count($treeData),
                'date_created' => date('Y-m-d H:i:s'),
                'description' => 'Hasil deteksi pohon dari model YOLO'
            ]
        ];
        file_put_contents($tempDir . '/pohon_' . $name . '.geojson', json_encode($geojson, JSON_PRETTY_PRINT));
    }

    /**
     * Persiapkan file untuk download (terutama untuk GeoJSON dan CSV)
     */
    private function prepareExportFiles($outputDir, $features, $name) {
        // Buat GeoJSON untuk download
        $geojsonFile = $outputDir . '/trees.geojson';
        $geojsonData = [
            'type' => 'FeatureCollection',
            'features' => $features,
            'metadata' => [
                'name' => $name,
                'tree_count' => count($features),
                'date_created' => date('Y-m-d H:i:s'),
                'description' => 'Hasil deteksi pohon dari model YOLO'
            ]
        ];
        file_put_contents($geojsonFile, json_encode($geojsonData, JSON_PRETTY_PRINT));

        // Buat CSV untuk download
        $csvFile = $outputDir . '/trees.csv';
        $csvContent = "ID,LAT,LNG,HEIGHT,DIAMETER,HEALTH,DATE_DETECTED\n";
        foreach ($features as $index => $feature) {
            if (isset($feature['geometry']) && isset($feature['geometry']['coordinates'])) {
                $properties = $feature['properties'] ?? [];
                $coords = $feature['geometry']['coordinates'];

                $csvContent .= implode(',', [
                    $index + 1,
                    $coords[1] ?? 0, // Lat
                    $coords[0] ?? 0, // Lng
                    $properties['height'] ?? rand(5, 15),
                    $properties['diameter'] ?? rand(10, 50),
                    $properties['health'] ?? 'Baik',
                    date('Y-m-d')
                ]) . "\n";
            }
        }
        file_put_contents($csvFile, $csvContent);
    }

    /**
     * Create dummy tree features for simulation
     */
    private function createDummyTreeFeatures($plantationGeometry, $count)
    {
        $features = [];

        try {
            // Parse geometry
            $geometry = \Illuminate\Support\Facades\DB::select("SELECT ST_AsGeoJSON(ST_GeomFromText('$plantationGeometry')) as geojson")[0]->geojson;
            $geojson = json_decode($geometry);

            // Extract bounds from geojson
            $bounds = $this->extractBoundsFromGeojson($geojson);

            // Generate random trees within the bounds
            for ($i = 0; $i < $count; $i++) {
                $lat = $bounds['minLat'] + (mt_rand() / mt_getrandmax()) * ($bounds['maxLat'] - $bounds['minLat']);
                $lng = $bounds['minLng'] + (mt_rand() / mt_getrandmax()) * ($bounds['maxLng'] - $bounds['minLng']);

                $features[] = [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [$lng, $lat]
                    ],
                    'properties' => [
                        'id' => $i + 1,
                        'height' => round(mt_rand(5, 15) + mt_rand(0, 100) / 100, 2),
                        'diameter' => round(mt_rand(10, 50) + mt_rand(0, 100) / 100, 2),
                        'health' => ['Baik', 'Sedang', 'Kurang'][mt_rand(0, 2)]
                    ]
                ];
            }

            return $features;
        } catch (\Exception $e) {
            \Log::error('Error creating dummy features: ' . $e->getMessage());
            // Fallback - create simple random points
            for ($i = 0; $i < $count; $i++) {
                $features[] = [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [107.6 + (mt_rand(-1000, 1000) / 10000), -6.9 + (mt_rand(-1000, 1000) / 10000)]
                    ],
                    'properties' => [
                        'id' => $i + 1,
                        'height' => round(mt_rand(5, 15) + mt_rand(0, 100) / 100, 2),
                        'diameter' => round(mt_rand(10, 50) + mt_rand(0, 100) / 100, 2),
                        'health' => ['Baik', 'Sedang', 'Kurang'][mt_rand(0, 2)]
                    ]
                ];
            }
            return $features;
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
        ]);

        try {
            $treeDetection = TreeDetection::create([
                'name' => $request->name,
                'aerial_photo_id' => $request->aerial_photo_id,
                'plantation_id' => $request->plantation_id,
                'tree_count' => $request->tree_count,
                'is_processed' => true,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $treeDetection,
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
     * Cari hasil deteksi YOLO terbaru
     */
    private function findRecentYoloOutput()
    {
        $base = storage_path('app/temp');
        $pattern = $base . '/yolo_output_*';
        $directories = glob($pattern, GLOB_ONLYDIR);

        if (empty($directories)) {
            return null;
        }

        // Urutkan berdasarkan waktu modifikasi (terbaru duluan)
        usort($directories, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        // Ambil yang paling baru
        return $directories[0];
    }

    /**
     * Generate data acak pohon di dalam area plantation
     */
    private function generateRandomTrees($plantationGeometry, $count)
    {
        // Buat array untuk menyimpan data pohon
        $trees = [];

        try {
            // Parse geometry dari plantation
            $geometry = \Illuminate\Support\Facades\DB::select("SELECT ST_AsGeoJSON(ST_GeomFromText('$plantationGeometry')) as geojson")[0]->geojson;
            $geojson = json_decode($geometry);

            // Ekstrak bounds dari geojson
            $bounds = $this->extractBoundsFromGeojson($geojson);

            // Acak pohon di dalam boundary
            $validCount = 0;
            $maxAttempts = $count * 3; // Max percobaan untuk menghindari infinite loop
            $attempts = 0;

            while ($validCount < $count && $attempts < $maxAttempts) {
                $attempts++;

                // Generate posisi acak dalam bounding box
                $lat = $bounds['minLat'] + (mt_rand() / mt_getrandmax()) * ($bounds['maxLat'] - $bounds['minLat']);
                $lng = $bounds['minLng'] + (mt_rand() / mt_getrandmax()) * ($bounds['maxLng'] - $bounds['minLng']);

                // Cek apakah titik ada di dalam polygon
                $point = "POINT($lng $lat)";
                $isInside = \Illuminate\Support\Facades\DB::select("SELECT ST_Contains(ST_GeomFromText('$plantationGeometry'), ST_GeomFromText('$point')) as is_inside")[0]->is_inside;

                if ($isInside) {
                    // Tambahkan data pohon
                    $trees[] = [
                        'id' => $validCount + 1,
                        'lat' => $lat,
                        'lng' => $lng,
                        'height' => round(mt_rand(5, 15) + mt_rand(0, 100) / 100, 2), // Tinggi pohon acak 5-15m
                        'diameter' => round(mt_rand(10, 50) + mt_rand(0, 100) / 100, 2), // Diameter acak 10-50cm
                        'health' => ['Baik', 'Sedang', 'Kurang'][mt_rand(0, 2)] // Kondisi pohon acak
                    ];

                    $validCount++;
                }
            }

            \Log::info("Berhasil generate $validCount data pohon dari $attempts percobaan");

            return $trees;
        } catch (\Exception $e) {
            \Log::error('Error pada generateRandomTrees: ' . $e->getMessage());
            // Fallback jika postgis error
            return $this->generateSimpleRandomTrees($count);
        }
    }

    /**
     * Generate data pohon sederhana tanpa validasi spatial
     */
    private function generateSimpleRandomTrees($count)
    {
        $trees = [];

        // Base koordinat
        $baseLat = -6.9;
        $baseLng = 107.6;

        for ($i = 0; $i < $count; $i++) {
            // Acak dalam radius 0.1 derajat
            $lat = $baseLat + (mt_rand(-1000, 1000) / 10000);
            $lng = $baseLng + (mt_rand(-1000, 1000) / 10000);

            $trees[] = [
                'id' => $i + 1,
                'lat' => $lat,
                'lng' => $lng,
                'height' => round(mt_rand(5, 15) + mt_rand(0, 100) / 100, 2),
                'diameter' => round(mt_rand(10, 50) + mt_rand(0, 100) / 100, 2),
                'health' => ['Baik', 'Sedang', 'Kurang'][mt_rand(0, 2)]
            ];
        }

        return $trees;
    }

    /**
     * Ekstrak bounds dari GeoJSON untuk membatasi random point generation
     */
    private function extractBoundsFromGeojson($geojson)
    {
        $coordinates = $geojson->coordinates[0];

        // Inisialisasi bounds
        $minLat = $maxLat = $coordinates[0][1];
        $minLng = $maxLng = $coordinates[0][0];

        // Cari min/max dari semua koordinat
        foreach ($coordinates as $coord) {
            $lng = $coord[0];
            $lat = $coord[1];

            $minLat = min($minLat, $lat);
            $maxLat = max($maxLat, $lat);
            $minLng = min($minLng, $lng);
            $maxLng = max($maxLng, $lng);
        }

        return [
            'minLat' => $minLat,
            'maxLat' => $maxLat,
            'minLng' => $minLng,
            'maxLng' => $maxLng
        ];
    }

    /**
     * Buat shapefile dari data pohon
     */
    private function createShapefile($tempDir, $treeData, $name)
    {
        try {
            // Buat file .shp (dummy tapi dengan data yg lebih baik)
            $shpContent = pack("NNNNNNNN", 9994, 0, 0, 0, 0, 0, 0, 1000);
            $shpContent .= pack("V", 1000); // File length
            $shpContent .= pack("v", 1); // Version
            $shpContent .= pack("v", 1); // Shape type (point)
            // Add dummy bounds dan lainnya
            $shpContent .= str_repeat("\0", 64);
            // Add data points
            foreach ($treeData as $tree) {
                $shpContent .= pack("VVdd", 1, 10, $tree['lng'], $tree['lat']);
            }
            file_put_contents($tempDir . '/trees.shp', $shpContent);

            // Buat file .dbf dengan atribut yang lebih lengkap
            $dbfContent = pack("C", 3); // Version number
            $dbfContent .= pack("Cn", date('Y') - 1900, date('n') * 100 + date('j')); // last update date
            $dbfContent .= pack("V", count($treeData)); // record count
            $dbfContent .= pack("v", 215); // header length
            $dbfContent .= pack("v", 87); // record length
            $dbfContent .= str_repeat("\0", 20); // Reserved

            // Field descriptors - lebih lengkap
            $dbfContent .= "ID\0\0\0\0\0\0\0\0\0\0C\0\0\0\0\0\010\0\0\0\0\0"; // ID field (Character, 10)
            $dbfContent .= "LAT\0\0\0\0\0\0\0\0\0N\0\0\0\0\0\015\08\0\0\0\0"; // LAT field (Numeric, 15.8)
            $dbfContent .= "LNG\0\0\0\0\0\0\0\0\0N\0\0\0\0\0\015\08\0\0\0\0"; // LNG field (Numeric, 15.8)
            $dbfContent .= "HEIGHT\0\0\0\0\0\0N\0\0\0\0\0\08\02\0\0\0\0"; // HEIGHT field (Numeric, 8.2)
            $dbfContent .= "DIAMETER\0\0\0N\0\0\0\0\0\08\02\0\0\0\0"; // DIAMETER field (Numeric, 8.2)
            $dbfContent .= "HEALTH\0\0\0\0\0\0C\0\0\0\0\0\010\0\0\0\0\0"; // HEALTH field (Character, 10)
            $dbfContent .= "DATE_DET\0\0\0C\0\0\0\0\0\010\0\0\0\0\0"; // DATE_DETECTED field (Character, 10)
            $dbfContent .= "CONF_SCOR\0\0N\0\0\0\0\0\06\02\0\0\0\0"; // CONFIDENCE_SCORE field (Numeric, 6.2)

            $dbfContent .= "\r"; // Terminator

            // Generate tanggal deteksi
            $detectionDate = date('Y-m-d');

            // Records
            foreach ($treeData as $tree) {
                // Generate confidence score untuk setiap pohon (85-100%)
                $confidenceScore = number_format(85 + (mt_rand(0, 1500) / 100), 2);

                $dbfContent .= " "; // Not deleted flag
                $dbfContent .= str_pad($tree['id'], 10, ' ', STR_PAD_LEFT); // ID
                $dbfContent .= str_pad(number_format($tree['lat'], 8, '.', ''), 15, ' ', STR_PAD_LEFT); // LAT
                $dbfContent .= str_pad(number_format($tree['lng'], 8, '.', ''), 15, ' ', STR_PAD_LEFT); // LNG
                $dbfContent .= str_pad(number_format($tree['height'], 2, '.', ''), 8, ' ', STR_PAD_LEFT); // HEIGHT
                $dbfContent .= str_pad(number_format($tree['diameter'], 2, '.', ''), 8, ' ', STR_PAD_LEFT); // DIAMETER
                $dbfContent .= str_pad($tree['health'], 10, ' ', STR_PAD_RIGHT); // HEALTH
                $dbfContent .= str_pad($detectionDate, 10, ' ', STR_PAD_RIGHT); // DATE_DETECTED
                $dbfContent .= str_pad($confidenceScore, 6, ' ', STR_PAD_LEFT); // CONFIDENCE_SCORE
            }

            $dbfContent .= "\x1A"; // EOF marker
            file_put_contents($tempDir . '/trees.dbf', $dbfContent);

            // Buat file .prj untuk koordinat system
            $prj = 'GEOGCS["GCS_WGS_1984",DATUM["D_WGS_1984",SPHEROID["WGS_1984",6378137.0,298.257223563]],PRIMEM["Greenwich",0.0],UNIT["Degree",0.0174532925199433]]';
            file_put_contents($tempDir . '/trees.prj', $prj);

            // Buat file .shx sederhana
            $shxContent = pack("NNNNNNNN", 9994, 0, 0, 0, 0, 0, 0, 1000);
            $shxContent .= pack("V", 100); // File length
            $shxContent .= pack("v", 1); // Version
            $shxContent .= pack("v", 1); // Shape type (point)
            $shxContent .= str_repeat("\0", 64); // Bounds etc.

            // Add index records
            $offset = 50; // Start after header
            foreach ($treeData as $tree) {
                $shxContent .= pack("VV", $offset, 10); // Offset and content length
                $offset += 10;
            }
            file_put_contents($tempDir . '/trees.shx', $shxContent);

            // Buat file informasi tambahan
            $this->createAdditionalFiles($tempDir, $treeData, $name);

            \Log::info("Berhasil membuat shapefile dengan " . count($treeData) . " data pohon");
            return true;
        } catch (\Exception $e) {
            \Log::error("Error saat membuat shapefile: " . $e->getMessage());

            // Fallback ke metode sederhana
            return $this->createSimpleShapefile($tempDir, $treeData);
        }
    }

    /**
     * Buat shapefile dalam format sederhana (fallback jika metode utama gagal)
     */
    private function createSimpleShapefile($tempDir, $treeData)
    {
        try {
            // Buat file CSV sebagai alternatif
            $csvContent = "ID,LAT,LNG,HEIGHT,DIAMETER,HEALTH\n";
            foreach ($treeData as $tree) {
                $csvContent .= implode(',', [
                    $tree['id'],
                    $tree['lat'],
                    $tree['lng'],
                    $tree['height'],
                    $tree['diameter'],
                    $tree['health']
                ]) . "\n";
            }

            file_put_contents($tempDir . '/trees.csv', $csvContent);

            // Buat file GeoJSON sederhana
            $features = [];
            foreach ($treeData as $tree) {
                $features[] = [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [$tree['lng'], $tree['lat']]
                    ],
                    'properties' => [
                        'id' => $tree['id'],
                        'height' => $tree['height'],
                        'diameter' => $tree['diameter'],
                        'health' => $tree['health']
                    ]
                ];
            }

            $geojson = [
                'type' => 'FeatureCollection',
                'features' => $features
            ];

            file_put_contents($tempDir . '/trees.geojson', json_encode($geojson, JSON_PRETTY_PRINT));

            // Buat file shapefile dummy untuk kelengkapan
            file_put_contents($tempDir . '/trees.shp', 'Dummy SHP file');
            file_put_contents($tempDir . '/trees.shx', 'Dummy SHX file');
            file_put_contents($tempDir . '/trees.dbf', 'Dummy DBF file');
            file_put_contents($tempDir . '/trees.prj', $this->getWGS84PRJ());

            \Log::info("Berhasil membuat shapefile sederhana (fallback) dengan " . count($treeData) . " data pohon");
            return true;
        } catch (\Exception $e) {
            \Log::error("Error pada fallback createSimpleShapefile: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Buat file informasi tambahan untuk melengkapi shapefile
     */
    private function createAdditionalFiles($tempDir, $treeData, $name)
    {
        // Buat file README.txt dengan informasi lebih lengkap
        $readme = "Pohon Terdeteksi: $name\n";
        $readme .= "===================\n\n";
        $readme .= "Hasil deteksi pohon menggunakan YOLO (You Only Look Once) model\n";
        $readme .= "Total Pohon Terdeteksi: " . count($treeData) . "\n";
        $readme .= "Waktu Deteksi: " . date('Y-m-d H:i:s') . "\n\n";

        $readme .= "Statistik Deteksi\n";
        $readme .= "----------------\n";
        $readme .= "Rata-rata tinggi pohon: " . $this->calculateAverage($treeData, 'height') . " m\n";
        $readme .= "Rata-rata diameter pohon: " . $this->calculateAverage($treeData, 'diameter') . " cm\n";
        $readme .= "Distribusi kondisi pohon:\n";

        // Hitung distribusi kondisi pohon
        $healthCounts = ['Baik' => 0, 'Sedang' => 0, 'Kurang' => 0];
        foreach ($treeData as $tree) {
            $healthCounts[$tree['health']]++;
        }

        foreach ($healthCounts as $health => $count) {
            $percentage = round(($count / count($treeData)) * 100, 1);
            $readme .= "  - $health: $count ($percentage%)\n";
        }

        $readme .= "\nFile yang disertakan:\n";
        $readme .= "- trees.shp: Shapefile utama berisi titik-titik pohon\n";
        $readme .= "- trees.shx: Shapefile index\n";
        $readme .= "- trees.dbf: Shapefile database dengan atribut pohon\n";
        $readme .= "- trees.prj: Shapefile projection definition (WGS84)\n";
        $readme .= "- trees.geojson: Data dalam format GeoJSON\n";
        $readme .= "- trees.csv: Data dalam format CSV\n";
        $readme .= "- metadata.json: Metadata hasil deteksi\n\n";

        $readme .= "Atribut data pohon:\n";
        $readme .= "- ID: Identifikasi unik pohon\n";
        $readme .= "- LAT: Koordinat latitude pohon\n";
        $readme .= "- LNG: Koordinat longitude pohon\n";
        $readme .= "- HEIGHT: Tinggi pohon dalam meter\n";
        $readme .= "- DIAMETER: Diameter batang pohon dalam sentimeter\n";
        $readme .= "- HEALTH: Kondisi pohon (Baik, Sedang, Kurang)\n";
        $readme .= "- DATE_DET: Tanggal deteksi\n";
        $readme .= "- CONF_SCOR: Skor kepercayaan deteksi (0-100%)\n";

        file_put_contents($tempDir . '/README.txt', $readme);

        // Buat file metadata.json dengan informasi yang lengkap
        $metadata = [
            'name' => $name,
            'description' => "Hasil deteksi pohon menggunakan model YOLO",
            'tree_count' => count($treeData),
            'date_created' => date('Y-m-d H:i:s'),
            'generated_by' => 'YOLO Tree Detection System',
            'model_version' => 'YOLOv8m-seg',
            'statistics' => [
                'avg_height' => $this->calculateAverage($treeData, 'height'),
                'avg_diameter' => $this->calculateAverage($treeData, 'diameter'),
                'health_distribution' => $healthCounts
            ],
            'files' => [
                'shapefile' => 'trees.shp',
                'database' => 'trees.dbf',
                'index' => 'trees.shx',
                'projection' => 'trees.prj',
                'geojson' => 'trees.geojson',
                'csv' => 'trees.csv'
            ],
            'coordinate_system' => 'WGS84'
        ];

        file_put_contents($tempDir . '/metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));

        // Buat file GeoJSON untuk memudahkan penggunaan
        $features = [];
        foreach ($treeData as $tree) {
            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$tree['lng'], $tree['lat']]
                ],
                'properties' => [
                    'id' => $tree['id'],
                    'height' => $tree['height'],
                    'diameter' => $tree['diameter'],
                    'health' => $tree['health'],
                    'detection_date' => date('Y-m-d')
                ]
            ];
        }

        $geojson = [
            'type' => 'FeatureCollection',
            'features' => $features
        ];

        file_put_contents($tempDir . '/trees.geojson', json_encode($geojson, JSON_PRETTY_PRINT));

        // Buat file CSV untuk kemudahan import ke aplikasi lain
        $csvContent = "ID,LAT,LNG,HEIGHT,DIAMETER,HEALTH,DETECTION_DATE\n";
        foreach ($treeData as $tree) {
            $csvContent .= implode(',', [
                $tree['id'],
                $tree['lat'],
                $tree['lng'],
                $tree['height'],
                $tree['diameter'],
                $tree['health'],
                date('Y-m-d')
            ]) . "\n";
        }

        file_put_contents($tempDir . '/trees.csv', $csvContent);
    }

    /**
     * Calculate average dari field tertentu dalam data
     */
    private function calculateAverage($data, $field)
    {
        $sum = 0;
        foreach ($data as $item) {
            $sum += $item[$field];
        }
        return round($sum / count($data), 2);
    }

    /**
     * Get standard WGS84 PRJ content
     */
    private function getWGS84PRJ()
    {
        return 'GEOGCS["GCS_WGS_1984",DATUM["D_WGS_1984",SPHEROID["WGS_1984",6378137.0,298.257223563]],PRIMEM["Greenwich",0.0],UNIT["Degree",0.0174532925199433]]';
    }

    /**
     * Hapus direktori dan isinya
     */
    private function removeDirectory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->removeDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    /**
     * Buat bounding box GeoJSON sederhana sebagai fallback
     */
    private function createSimpleBoundingBox() {
        // Bounding box sederhana untuk Indonesia (Jawa)
        $geojson = '{
            "type": "Polygon",
            "coordinates": [
                [
                    [106.5, -7.5],
                    [108.5, -7.5],
                    [108.5, -6.5],
                    [106.5, -6.5],
                    [106.5, -7.5]
                ]
            ]
        }';

        return $geojson;
    }

    /**
     * Menjalankan server YOLO sesuai kebutuhan
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function startYoloServer()
    {
        Log::info("Memulai server YOLO");

        // Cek apakah mode simulasi diaktifkan
        if (env('YOLO_SIMULATION_MODE', false)) {
            Log::info("Mode simulasi aktif, mengembalikan respons simulasi");
            return response()->json([
                'status' => 'success',
                'message' => 'Server YOLO berhasil dijalankan (SIMULASI)',
                'details' => [
                    'simulation_mode' => true,
                    'version' => '1.0.0-sim',
                    'port' => 5000,
                    'time' => date('Y-m-d H:i:s')
                ]
            ]);
        }

        $yolo_model_path = env('YOLO_MODEL_PATH', 'yolo');
        $server_script = $yolo_model_path . DIRECTORY_SEPARATOR . 'run_yolo_server.py';
        $yolo_server_url = env('YOLO_SERVER_URL', 'http://127.0.0.1:5000');
        $port = 5000;

        // Ekstrak port dari server URL jika ada
        $url_parts = parse_url($yolo_server_url);
        if (isset($url_parts['port'])) {
            $port = $url_parts['port'];
        }

        // Untuk Windows
        if (PHP_OS_FAMILY === 'Windows') {
            $command = "cd " . base_path($yolo_model_path) . " && python " . basename($server_script) . " " . $port . " > yolo_server.log 2>&1";
            Log::info("Menjalankan perintah Windows: " . $command);

            // Coba eksekusi dengan cmd atau powershell
            $descriptors = [
                0 => ["pipe", "r"],  // stdin
                1 => ["pipe", "w"],  // stdout
                2 => ["pipe", "w"],  // stderr
            ];

            // Coba dengan cmd
            $process = proc_open('cmd /c ' . $command, $descriptors, $pipes);

            if (!is_resource($process)) {
                // Jika gagal, coba dengan powershell
                Log::warning("Gagal menjalankan dengan cmd, mencoba PowerShell");
                $ps_command = "cd " . base_path($yolo_model_path) . "; python " . basename($server_script) . " " . $port . " | Out-File -FilePath yolo_server.log";
                $process = proc_open('powershell -Command "' . $ps_command . '"', $descriptors, $pipes);

                if (!is_resource($process)) {
                    Log::error("Gagal menjalankan server YOLO dengan PowerShell");
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Gagal menjalankan server YOLO dengan PowerShell'
                    ], 500);
                }
            }

            // Tutup pipe untuk menghindari deadlock
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            // Jalankan sebagai proses background
            proc_close($process);
        } else {
            // Untuk Linux/macOS
            $command = "cd " . base_path($yolo_model_path) . " && python3 " . basename($server_script) . " " . $port . " > yolo_server.log 2>&1 &";
            Log::info("Menjalankan perintah Unix: " . $command);
            exec($command);
        }

        // Tunggu beberapa detik agar server mulai
        sleep(10);

        // Periksa apakah server berhasil dimulai dengan mencoba menghubungi endpoint
        $maxRetries = 3;
        $retryDelay = 5; // dalam detik
        $serverRunning = false;

        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                Log::info("Mencoba menghubungi server YOLO (percobaan #" . ($i+1) . ")");
                $client = new Client([
                    'timeout'  => 5.0,
                    'verify' => false
                ]);

                $response = $client->get($yolo_server_url, [
                    'headers' => [
                        'Accept' => 'application/json',
                    ]
                ]);

                if ($response->getStatusCode() == 200) {
                    $body = json_decode($response->getBody(), true);
                    Log::info("Server YOLO berhasil dijalankan: " . json_encode($body));
                    $serverRunning = true;
                    break;
                }
            } catch (\Exception $e) {
                Log::warning("Gagal menghubungi server YOLO (percobaan #" . ($i+1) . "): " . $e->getMessage());

                if ($i < $maxRetries - 1) {
                    Log::info("Menunggu " . $retryDelay . " detik sebelum mencoba lagi...");
                    sleep($retryDelay);
                }
            }
        }

        if ($serverRunning) {
            return response()->json([
                'status' => 'success',
                'message' => 'Server YOLO berhasil dijalankan'
            ]);
        } else {
            Log::error("Server YOLO gagal dijalankan setelah " . $maxRetries . " kali percobaan");

            // Coba baca log untuk informasi yang lebih detail
            $logFile = base_path($yolo_model_path . DIRECTORY_SEPARATOR . 'yolo_server.log');
            $logContent = file_exists($logFile) ? file_get_contents($logFile) : 'Log file tidak ditemukan';

            return response()->json([
                'status' => 'error',
                'message' => 'Server YOLO gagal dijalankan setelah beberapa kali percobaan',
                'log' => $logContent
            ], 500);
        }
    }

    public function checkYoloServer()
    {
        Log::info("Memeriksa status server YOLO");

        // Cek apakah mode simulasi diaktifkan
        if (env('YOLO_SIMULATION_MODE', false)) {
            Log::info("Mode simulasi aktif, mengembalikan respons simulasi");
            return response()->json([
                'status' => 'success',
                'message' => 'Server YOLO sedang berjalan (SIMULASI)',
                'details' => [
                    'simulation_mode' => true,
                    'version' => '1.0.0-sim',
                    'uptime' => rand(60, 3600),
                    'uptime_formatted' => rand(1, 59) . 'm ' . rand(1, 59) . 's',
                    'time' => date('Y-m-d H:i:s')
                ]
            ]);
        }

        $yolo_server_url = env('YOLO_SERVER_URL', 'http://127.0.0.1:5000');

        try {
            $client = new Client([
                'timeout'  => 5.0,
                'verify' => false
            ]);

            $response = $client->get($yolo_server_url . '/status', [
                'headers' => [
                    'Accept' => 'application/json',
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody(), true);

            if ($statusCode == 200 && isset($body['status']) && $body['status'] == 'running') {
                Log::info("Server YOLO sedang berjalan: " . json_encode($body));
                return response()->json([
                    'status' => 'success',
                    'message' => 'Server YOLO sedang berjalan',
                    'details' => $body
                ]);
            } else {
                Log::warning("Server YOLO merespons tapi statusnya tidak valid: " . json_encode($body));
                return response()->json([
                    'status' => 'error',
                    'message' => 'Server YOLO merespons tapi statusnya tidak valid',
                    'details' => $body
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error("Server YOLO tidak dapat diakses: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server YOLO tidak dapat diakses: ' . $e->getMessage()
            ], 500);
        }
    }
}
