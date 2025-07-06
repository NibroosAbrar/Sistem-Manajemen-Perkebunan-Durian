<?php

namespace App\Http\Controllers;

use App\Models\AerialPhoto;
use App\Models\Digitasi;
use App\Models\Plantation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class YoloInferenceController extends Controller
{
    protected $pythonServiceUrl = 'http://localhost:5000/yolo-inference';
    protected $modelPath = '/path/to/your/yolo/model.pt'; // Sesuaikan dengan path model YOLO

    /**
     * Menampilkan form untuk inferensi YOLO
     */
    public function index()
    {
        $aerialPhotos = AerialPhoto::all();
        $plantations = Plantation::all();

        return view('yolo.inference', compact('aerialPhotos', 'plantations'));
    }

    /**
     * Memproses permintaan inferensi YOLO
     */
    public function process(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'aerial_photo_id' => 'required|exists:aerial_photos,id',
            'plantation_id' => 'required|exists:plantations,id',
            'plantation_geojson' => 'required|json',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            // Ambil data
            $aerialPhoto = AerialPhoto::findOrFail($request->aerial_photo_id);
            $plantation = Plantation::findOrFail($request->plantation_id);

            // Pastikan file gambar ada
            $imagePath = $aerialPhoto->path;
            if (!Storage::exists($imagePath)) {
                return response()->json([
                    'error' => 'Gambar tidak ditemukan',
                    'logs' => [
                        ['time' => now()->format('H:i:s'), 'message' => '❌ Error: Gambar tidak ditemukan pada path: ' . $imagePath]
                    ]
                ], 404);
            }

            // Log awal proses untuk direturn ke UI
            $logs = [
                ['time' => now()->format('H:i:s'), 'message' => '🚀 Mulai proses inferensi YOLO'],
                ['time' => now()->format('H:i:s'), 'message' => '📥 Memuat gambar dari aerial_photo_id: ' . $request->aerial_photo_id],
                ['time' => now()->format('H:i:s'), 'message' => '🔍 Menggunakan area plantation_id: ' . $request->plantation_id]
            ];

            // Baca file gambar sebagai base64
            $imageContent = Storage::get($imagePath);
            $base64Image = base64_encode($imageContent);
            $logs[] = ['time' => now()->format('H:i:s'), 'message' => '✅ Gambar berhasil dimuat dan dikonversi ke base64'];

            // Siapkan data untuk dikirim ke Python
            $requestData = [
                'image_base64' => $base64Image,
                'plantation_geojson' => $request->plantation_geojson,
                'model_path' => $this->modelPath,
                'class_names' => [
                    0 => 'pohon',
                    1 => 'rumput',
                    // Tambahkan kelas lain sesuai kebutuhan
                ],
            ];

            $logs[] = ['time' => now()->format('H:i:s'), 'message' => '🔄 Mengirim data ke service Python...'];

            // Panggil Python service
            $response = Http::timeout(300)->post($this->pythonServiceUrl, $requestData);

            if (!$response->successful()) {
                $errorMsg = 'Inferensi YOLO gagal: ' . $response->body();
                Log::error($errorMsg, [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                $logs[] = ['time' => now()->format('H:i:s'), 'message' => '❌ Error: ' . $errorMsg];

                return response()->json([
                    'error' => $errorMsg,
                    'logs' => $logs
                ], 500);
            }

            // Ambil hasil respons
            $responseData = $response->json();

            // Gabungkan log dari Laravel dengan log dari Python jika ada
            if (isset($responseData['logs']) && is_array($responseData['logs'])) {
                // Prioritaskan log dari Python karena lebih detail
                $logs = $responseData['logs'];
            } else {
                // Jika tidak ada log dari Python, tambahkan log sukses
                $logs[] = ['time' => now()->format('H:i:s'), 'message' => '✅ Inferensi berhasil dilakukan'];
            }

            // Tambahkan log statistik hasil
            if (!empty($responseData['features'])) {
                $featureCount = count($responseData['features']);
                $logs[] = ['time' => now()->format('H:i:s'), 'message' => "📊 Total objek terdeteksi: {$featureCount} pohon"];

                // Buat log statistik per kelas jika tersedia
                $classCounts = [];
                foreach ($responseData['features'] as $feature) {
                    $className = $feature['properties']['class_name'] ?? 'unknown';
                    if (!isset($classCounts[$className])) {
                        $classCounts[$className] = 0;
                    }
                    $classCounts[$className]++;
                }

                foreach ($classCounts as $className => $count) {
                    $logs[] = ['time' => now()->format('H:i:s'), 'message' => "🌱 Kelas '{$className}': {$count} objek"];
                }

                // Tambahkan log tentang penyimpanan hasil
                $logs[] = ['time' => now()->format('H:i:s'), 'message' => '💾 Menyimpan hasil deteksi ke database...'];

                // Simpan hasil ke database
                $this->saveResults(
                    $responseData,
                    $request->aerial_photo_id,
                    $request->plantation_id
                );

                $logs[] = ['time' => now()->format('H:i:s'), 'message' => '✅ Hasil berhasil disimpan ke database'];
            } else {
                $logs[] = ['time' => now()->format('H:i:s'), 'message' => '⚠️ Tidak ada objek terdeteksi dalam gambar'];
            }

            // Tambahkan info waktu pemrosesan
            if (isset($responseData['processing_time'])) {
                $processingTime = number_format($responseData['processing_time'], 2);
                $logs[] = ['time' => now()->format('H:i:s'), 'message' => "⏱️ Total waktu pemrosesan: {$processingTime} detik"];
            }

            // Tambahkan logs ke respons
            $responseData['logs'] = $logs;

            // Kembalikan hasil
            return response()->json($responseData);

        } catch (\Exception $e) {
            Log::error('Error saat inferensi YOLO', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Error saat inferensi: ' . $e->getMessage(),
                'logs' => [
                    [
                        'time' => now()->format('H:i:s'),
                        'message' => '❌ Error saat memproses: ' . $e->getMessage()
                    ]
                ]
            ], 500);
        }
    }

    /**
     * Menyimpan hasil inferensi YOLO ke database
     */
    protected function saveResults($geojsonData, $aerialPhotoId, $plantationId)
    {
        if (empty($geojsonData['features'])) {
            return;
        }

        // Iterasi setiap fitur hasil deteksi
        foreach ($geojsonData['features'] as $feature) {
            $properties = $feature['properties'];
            $geometry = $feature['geometry'];

            // Buat record baru di tabel digitasi
            Digitasi::create([
                'name' => 'YOLO Detection ' . date('Y-m-d H:i:s'),
                'aerial_photo_id' => $aerialPhotoId,
                'plantation_id' => $plantationId,
                'class' => $properties['class_name'] ?? 'unknown',
                'confidence' => $properties['confidence'] ?? 0,
                'geom' => $this->convertGeoJsonToWKT($geometry),
                'detection_meta' => json_encode([
                    'class_id' => $properties['class_id'] ?? null,
                    'timestamp' => now()->toIso8601String(),
                ])
            ]);
        }
    }

    /**
     * Mengkonversi GeoJSON ke format WKT untuk PostgreSQL
     */
    protected function convertGeoJsonToWKT($geometry)
    {
        // Implementasi sederhana untuk Polygon
        if ($geometry['type'] === 'Polygon') {
            $coords = $geometry['coordinates'][0];
            $wktCoords = implode(',', array_map(function($point) {
                return $point[0] . ' ' . $point[1];
            }, $coords));

            return "POLYGON(($wktCoords))";
        }

        // Untuk MultiPolygon atau tipe geometri lainnya
        // Tambahkan implementasi sesuai kebutuhan

        return null;
    }

    /**
     * Mengambil hasil digitasi dalam format GeoJSON untuk Leaflet
     */
    public function getDigitasiGeoJson($plantationId)
    {
        $digitasi = Digitasi::where('plantation_id', $plantationId)->get();

        $features = [];
        foreach ($digitasi as $item) {
            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($item->geom),
                'properties' => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'class' => $item->class,
                    'confidence' => $item->confidence,
                    'created_at' => $item->created_at->toDateTimeString()
                ]
            ];
        }

        $geoJson = [
            'type' => 'FeatureCollection',
            'features' => $features
        ];

        return response()->json($geoJson);
    }
}
