<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\PengelolaanController;
use App\Http\Controllers\PengelolaanDetailController;
use App\Http\Controllers\TreeController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AerialPhotoController;
use App\Http\Controllers\TreeFertilizationController;
use App\Http\Controllers\TreePesticideController;
use App\Http\Controllers\HarvestController;
use App\Http\Controllers\TreeDashboardController;
use App\Http\Controllers\TreeHealthProfileController;
use App\Http\Controllers\TreeZptController;
use App\Http\Controllers\TreeGrowthController;
use App\Http\Controllers\ShapefileController;
use App\Http\Controllers\TreeDetectionController;
use App\Http\Controllers\DigitasiController;
use App\Http\Controllers\YoloInferenceController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ExportController;

// Redirect ke halaman login jika tidak ada rute
Route::get('/', function () {
    return redirect()->route('login');
});

// Login & Logout Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Register dengan verifikasi email
Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::post('/register', [AuthController::class, 'register'])->name('register');

// Password Reset Routes
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// Verifikasi Email Routes (Tetap Ada, Tapi Tidak Wajib untuk Login)
// Route::get('/email/verify', function () {
//     return view('auth.verify-email');
// })->middleware('auth')->name('verification.notice');

// Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
//     $request->fulfill();
//     return redirect('/webgis');
// })->middleware(['auth', 'signed'])->name('verification.verify');

// Route::post('/email/verification-notification', function (Request $request) {
//     $request->user()->sendEmailVerificationNotification();
//     return back()->with('message', 'Email verifikasi telah dikirim!');
// })->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// Rute yang membutuhkan autentikasi (Tanpa verifikasi email)
Route::middleware(['auth'])->group(function () {
    // WebGIS Dashboard
    Route::get('/webgis', function () {
        return view('pages.webgis');
    })->name('webgis');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Tree Dashboard
    Route::get('/tree-dashboard', [TreeDashboardController::class, 'index'])->name('tree.dashboard');
    Route::delete('/tree-dashboard/fertilization/{id}', [TreeDashboardController::class, 'destroyFertilization'])->name('tree.dashboard.fertilization.destroy');
    Route::delete('/tree-dashboard/pesticide/{id}', [TreeDashboardController::class, 'destroyPesticide'])->name('tree.dashboard.pesticide.destroy');
    Route::delete('/tree-dashboard/harvest/{id}', [TreeDashboardController::class, 'destroyHarvest'])->name('tree.dashboard.harvest.destroy');
    Route::delete('/tree-dashboard/growth/{id}', [TreeDashboardController::class, 'destroyGrowth'])->name('tree.dashboard.growth.destroy');

    // Manajemen Pengguna (Superadmin Only)
    Route::middleware(['auth', 'role:Superadmin'])->group(function () {
        Route::get('/akun', [UserController::class, 'index'])->name('akun');
        Route::put('/akun/{id}', [UserController::class, 'update'])->name('akun.update');
        Route::delete('/akun/{id}', [UserController::class, 'destroy'])->name('akun.destroy');
    });

    // Manajemen Stok (Superadmin, Manajer)
    Route::middleware(['auth', 'role:Superadmin,Manajer'])->group(function () {
        Route::get('/stok', [StockController::class, 'index'])->name('stok');
        Route::post('/stok', [StockController::class, 'store'])->name('stok.store');
        Route::put('/stok/{id}', [StockController::class, 'update'])->name('stok.update');
        Route::delete('/stok/{id}', [StockController::class, 'destroy'])->name('stok.destroy');
        Route::get('/stok/export-excel', [StockController::class, 'exportExcel'])->name('stok.export-excel');
    });

    // Profil
    Route::middleware(['auth'])->group(function () {
        Route::get('/akun/profil', [UserController::class, 'profile'])->name('akun.profil');
        Route::put('/akun/profil/update', [UserController::class, 'updateProfile'])->name('akun.profil.update');

        // Bantuan
        Route::get('/bantuan', function () {
            return view('pages.bantuan');
        })->name('bantuan');
    });

    // Rute pengelolaan dapat diakses oleh guest dan user yang login
    Route::get('/pengelolaan', [PengelolaanController::class, 'index'])->name('pengelolaan');

    // Aksi pengelolaan hanya untuk user yang login dengan role tertentu
    Route::middleware(['auth', 'role:Superadmin,Manajer,Operasional'])->group(function () {
        Route::post('/pengelolaan', [PengelolaanController::class, 'store'])->name('pengelolaan.store');
        Route::delete('/pengelolaan/{id}', [PengelolaanController::class, 'destroy'])->name('pengelolaan.destroy');
        Route::get('/pengelolaan/{id}/edit', [PengelolaanController::class, 'edit'])->name('pengelolaan.edit');
        Route::put('/pengelolaan/{id}', [PengelolaanController::class, 'update'])->name('pengelolaan.update');
        Route::patch('/pengelolaan/update-status/{id}', [PengelolaanController::class, 'updateStatus'])->name('pengelolaan.updateStatus');
    });

    Route::middleware(['auth'])->group(function () {
        // WebGIS Dashboard with Trees Management
        Route::get('/webgis', [TreeController::class, 'index'])->name('webgis');
        Route::get('/trees/get-all', [TreeController::class, 'getAll'])->name('trees.getAll');

        // Tree API Routes
        Route::prefix('api')->group(function () {
            Route::get('/trees', [TreeController::class, 'getAll']);
            Route::post('/trees', [TreeController::class, 'store']);
            Route::post('/trees/fertilization', [TreeController::class, 'storeFertilization']);
            Route::post('/trees/pesticide', [TreeController::class, 'storePesticide']);
            Route::post('/trees/harvest', [TreeController::class, 'storeHarvest']);
            Route::get('/trees/{id}', [TreeController::class, 'show']);
            Route::put('/trees/{id}', [TreeController::class, 'update']);
            Route::delete('/trees/{id}', [TreeController::class, 'destroy']);

            // Tree Health Profile Routes
            Route::get('/trees/{treeId}/health-profiles', [TreeHealthProfileController::class, 'index']);
            Route::post('/trees/health-profiles', [TreeHealthProfileController::class, 'store'])->name('tree-health-profiles.store');
            Route::get('/trees/health-profiles/{id}', [TreeHealthProfileController::class, 'show']);
            Route::put('/trees/health-profiles/{id}', [TreeHealthProfileController::class, 'update']);
            Route::delete('/trees/health-profiles/{id}', [TreeHealthProfileController::class, 'destroy']);

            // Tree ZPT Routes
            Route::get('/trees/{treeId}/zpts', [TreeZptController::class, 'index']);
            Route::post('/trees/zpts', [TreeZptController::class, 'store'])->name('tree-zpts.store');
            Route::get('/trees/zpts/{id}', [TreeZptController::class, 'show']);
            Route::put('/trees/zpts/{id}', [TreeZptController::class, 'update']);
            Route::delete('/trees/zpts/{id}', [TreeZptController::class, 'destroy']);

            // Plantation API Routes
            Route::apiResource('/plantations', 'App\Http\Controllers\API\PlantationController');
        });

        // Detail Pengelolaan
        Route::get('/pengelolaan/detail', [PengelolaanDetailController::class, 'index'])->name('pengelolaan.detail');

        // Aerial Photo Routes
        Route::get('/aerial-photo', [AerialPhotoController::class, 'index'])->name('aerial-photo.index');
        Route::get('/aerial-photo/create', [AerialPhotoController::class, 'create'])->name('aerial-photo.create');
        Route::post('/aerial-photo', [AerialPhotoController::class, 'store'])->name('aerial-photo.store');
        Route::get('/aerial-photo/{id}/edit', [AerialPhotoController::class, 'edit'])->name('aerial-photo.edit');
        Route::put('/aerial-photo/{id}', [AerialPhotoController::class, 'update'])->name('aerial-photo.update');
        Route::delete('/aerial-photo/{id}', [AerialPhotoController::class, 'destroy'])->name('aerial-photo.destroy');

        // Tree Fertilization Routes
        Route::post('/trees/fertilization', [TreeController::class, 'storeFertilization'])->name('trees.fertilization.store');
        Route::get('/trees/fertilization/{id}/edit', [TreeController::class, 'editFertilization'])->name('trees.fertilization.edit');
        Route::put('/trees/fertilization/{id}', [TreeController::class, 'updateFertilization'])->name('trees.fertilization.update');
        Route::delete('/trees/fertilization/{id}', [TreeController::class, 'destroyFertilization'])->name('trees.fertilization.destroy');

        // Tree Pesticide Routes
        Route::post('/trees/pesticide', [TreeController::class, 'storePesticide'])->name('trees.pesticide.store');
        Route::get('/trees/pesticide/{id}/edit', [TreeController::class, 'editPesticide'])->name('trees.pesticide.edit');
        Route::put('/trees/pesticide/{id}', [TreeController::class, 'updatePesticide'])->name('trees.pesticide.update');
        Route::delete('/trees/pesticide/{id}', [TreeController::class, 'destroyPesticide'])->name('trees.pesticide.destroy');

        // Harvest Routes
        Route::post('/trees/harvest', [TreeController::class, 'storeHarvest'])->name('trees.harvest.store');
        Route::get('/trees/harvest/{id}/edit', [TreeController::class, 'editHarvest'])->name('trees.harvest.edit');
        Route::put('/trees/harvest/{id}', [TreeController::class, 'updateHarvest'])->name('trees.harvest.update');
        Route::delete('/trees/harvest/{id}', [TreeController::class, 'destroyHarvest'])->name('trees.harvest.destroy');
    });

    // Tree Health Profile Routes
    Route::delete('/tree/health/{id}', [TreeHealthProfileController::class, 'destroy'])->name('tree.health.destroy');

    // Rute untuk Riwayat ZPT
    Route::get('/tree/zpt/{treeId}', [TreeZptController::class, 'index'])->name('tree.zpt.index');
    Route::post('/tree/zpt', [TreeZptController::class, 'store'])->name('tree.zpt.store');
    Route::put('/tree/zpt/{id}', [TreeZptController::class, 'update'])->name('tree.zpt.update');
    Route::delete('/tree/zpt/{id}', [TreeZptController::class, 'destroy'])->name('tree.zpt.destroy');

    // Rute untuk Riwayat Pertumbuhan
    Route::get('/tree/growth/show/{id}', [TreeGrowthController::class, 'show'])->name('tree.growth.show');
    Route::get('/tree/growth/{treeId}', [TreeGrowthController::class, 'index'])->name('tree.growth.index');
    Route::post('/tree/growth', [TreeGrowthController::class, 'store'])->name('tree.growth.store');
    Route::put('/tree/growth/{id}', [TreeGrowthController::class, 'update'])->name('tree.growth.update');
    Route::delete('/tree/growth/{id}', [TreeGrowthController::class, 'destroy'])->name('tree.growth.destroy');

    // Tree Growth Routes
    Route::delete('/tree/growth/{id}', [TreeGrowthController::class, 'destroy'])->name('tree.growth.destroy');

    // Digitasi Pohon Routes
    Route::get('/digitasi', [DigitasiController::class, 'index'])->name('digitasi.index');
    Route::get('/digitasi/create', [DigitasiController::class, 'create'])->name('digitasi.create');
    Route::post('/digitasi', [DigitasiController::class, 'store'])->name('digitasi.store');
    Route::get('/digitasi/{digitasi}', [DigitasiController::class, 'show'])->name('digitasi.show');
    Route::get('/digitasi/{digitasi}/edit', [DigitasiController::class, 'edit'])->name('digitasi.edit');
    Route::put('/digitasi/{digitasi}', [DigitasiController::class, 'update'])->name('digitasi.update');
    Route::delete('/digitasi/{digitasi}', [DigitasiController::class, 'destroy'])->name('digitasi.destroy');
    Route::post('/digitasi/{digitasi}/import-to-trees', [DigitasiController::class, 'importToTrees'])->name('digitasi.import-to-trees');

    // Rute untuk halaman YOLO Inference
    Route::get('/yolo', [App\Http\Controllers\YoloInferenceController::class, 'index'])->name('yolo.index');

    // Rute ekspor data
    Route::get('/export/trees', [ExportController::class, 'exportTrees'])->name('export.trees');
    Route::get('/export/plantations', [ExportController::class, 'exportPlantations'])->name('export.plantations');
    Route::get('/export/kegiatan', [ExportController::class, 'exportKegiatan'])->name('export.kegiatan');
    Route::get('/export/tree-history', [ExportController::class, 'exportTreeHistory'])->name('export.tree-history');
});

Route::prefix('api')->group(function () {
    Route::post('/create-aerial-photo', [AerialPhotoController::class, 'store'])->name('aerial-photo.api.store');
    Route::put('/update-aerial-photo/{id}', [AerialPhotoController::class, 'update'])->name('aerial-photo.api.update');
    Route::get('/latest-aerial-photo', [AerialPhotoController::class, 'getLatest'])->name('aerial-photo.latest');

    Route::get('/plantations-by-aerial-photo/{aerialPhotoId}', [DigitasiController::class, 'getPlantationsByAerialPhoto'])
        ->name('digitasi.plantations-by-aerial-photo');
});

// Menambahkan route untuk shapefile
Route::resource('shapefile', ShapefileController::class)->middleware(['auth']);
Route::post('/shapefile/{shapefile}/process', [ShapefileController::class, 'process'])->name('shapefile.process')->middleware(['auth']);
Route::post('/shapefile/{shapefile}/reset', [ShapefileController::class, 'resetShapefile'])->name('shapefile.reset')->middleware(['auth']);

// API route untuk shapefile
Route::prefix('api')->middleware(['auth'])->group(function () {
    Route::get('/shapefiles', [ShapefileController::class, 'getAll'])->name('api.shapefiles');
    Route::get('/shapefiles/{id}', [ShapefileController::class, 'getById'])->name('api.shapefiles.show');

    // API untuk pohon durian detection
    // Route::get('/plantations-by-aerial-photo/{id}', [ShapefileController::class, 'getPlantationsByAerialPhoto'])->name('api.plantations.by-aerial');
    Route::post('/detect-trees', [ShapefileController::class, 'detectTrees'])->name('api.detect-trees');
    Route::post('/save-tree-detection', [ShapefileController::class, 'saveTreeDetection'])->name('api.save-tree-detection');
    Route::post('/export-tree-shapefile', [ShapefileController::class, 'exportTreeShapefile'])->name('api.export-tree-shapefile');
});

// Tree Detection Routes
Route::resource('tree-detection', TreeDetectionController::class);

// API Routes for Tree Detection
Route::post('/api/detect-trees', [TreeDetectionController::class, 'detectTrees'])->name('api.detect-trees');
Route::post('/api/save-tree-detection', [TreeDetectionController::class, 'saveDetection'])->name('api.save-tree-detection');
Route::post('/api/export-tree-shapefile', [TreeDetectionController::class, 'exportShapefile'])->name('api.export-tree-shapefile');

// API Routes for Tree Detection - CSRF disabled untuk debugging
Route::post('/api/detect-trees', [TreeDetectionController::class, 'detectTrees'])
    ->name('api.detect-trees')
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

Route::post('/api/save-detection', [TreeDetectionController::class, 'saveDetection'])
    ->name('api.save-detection')
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

Route::post('/api/export-shapefile', [TreeDetectionController::class, 'exportShapefile'])
    ->name('api.export-shapefile')
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

// Route untuk menjalankan server YOLO
Route::post('/api/start-yolo-server', [TreeDetectionController::class, 'startYoloServer'])
    ->name('api.start-yolo-server')
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

// API untuk aerial-photos dari AerialPhotoController (harus di luar auth middleware)
Route::get('/api/aerial-photos', [AerialPhotoController::class, 'getAll'])->name('api.aerial-photos');

// Pindahkan ke luar middleware auth
// API untuk preview dan deteksi pohon
Route::get('/aerial-photo/{id}/preview', [DigitasiController::class, 'getAerialPhotoPreview'])->name('aerial-photo.preview');
Route::get('/aerial-photo-image/{id}', [App\Http\Controllers\TreeDetectionController::class, 'showAerialImage'])->name('aerial.photo.image');

// Tambahkan route baru untuk akses gambar langsung dengan CORS
Route::get('/aerial-photo-direct/{id}', function($id) {
    try {
        $aerialPhoto = \App\Models\AerialPhoto::findOrFail($id);
        $imagePath = storage_path('app/public/' . $aerialPhoto->path);

        if (!file_exists($imagePath)) {
            // Coba cari di direktori previews
            $previewPath = storage_path('app/public/aerial-photos/previews/preview_*_' . basename($imagePath));
            $previewFiles = glob($previewPath);

            if (!empty($previewFiles) && file_exists($previewFiles[0])) {
                $imagePath = $previewFiles[0];
            } else {
                // Jika masih tidak ada, gunakan placeholder
                $imagePath = public_path('img/sample-aerial.jpg');
            }
        }

        if (!file_exists($imagePath)) {
            return response()->json(['error' => 'Image not found'], 404);
        }

        // Deteksi tipe file dari ekstensi
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        $contentType = 'image/jpeg'; // Default

        if ($extension === 'png') {
            $contentType = 'image/png';
        } elseif ($extension === 'tif' || $extension === 'tiff') {
            $contentType = 'image/tiff';
        }

        // Baca file gambar
        $image = file_get_contents($imagePath);

        // Return dengan header yang benar
        return response($image)
            ->header('Content-Type', $contentType)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET');

    } catch (\Exception $e) {
        \Log::error('Error accessing aerial image: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
})->name('aerial.photo.direct');

Route::get('/plantation/{id}/preview', [DigitasiController::class, 'getPlantationPreview'])->name('plantation.preview');
Route::get('/plantation/list', [DigitasiController::class, 'getAllPlantations'])->name('plantation.list');
Route::post('/detect-trees', [DigitasiController::class, 'detectTrees'])->name('digitasi.detect-trees');
Route::post('/save-detection', [DigitasiController::class, 'saveDetection'])->name('digitasi.save-detection');

Route::post('/kegiatan/{id}/selesai', [PengelolaanController::class, 'selesai'])->name('kegiatan.selesai');

// Tambahkan route diagnostic untuk debugging
Route::get('/debug-aerial-image', function() {
    return response()->json([
        'message' => 'Debug info for aerial image',
        'storage_path' => storage_path('app/public/aerial-photos/1/preview.jpg'),
        'file_exists' => file_exists(storage_path('app/public/aerial-photos/1/preview.jpg')),
        'directories' => scandir(storage_path('app/public')),
        'photo_dirs' => file_exists(storage_path('app/public/aerial-photos')) ? scandir(storage_path('app/public/aerial-photos')) : [],
    ]);
});

// Route untuk membuat file aerial photo dummy
Route::get('/create-dummy-aerial', function() {
    // Pastikan direktori ada
    $directory = storage_path('app/public/aerial-photos/1');
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }

    // Buat file dummy
    $dummyFile = $directory . '/preview.jpg';

    // Salin file dummy dari public/img jika ada, atau buat file kosong
    if (file_exists(public_path('img/sample-aerial.jpg'))) {
        copy(public_path('img/sample-aerial.jpg'), $dummyFile);
        return response()->json([
            'message' => 'Dummy aerial photo created from sample',
            'path' => $dummyFile,
            'size' => filesize($dummyFile)
        ]);
    } else {
        // Buat file kosong dengan pesan
        file_put_contents($dummyFile, 'Dummy Aerial Photo');
        return response()->json([
            'message' => 'Empty dummy aerial photo created',
            'path' => $dummyFile,
            'size' => filesize($dummyFile)
        ]);
    }
});

// Tambahkan route baru untuk memperbaiki foto sample
Route::get('/fix-sample-aerial', function() {
    // URL contoh gambar aerial (ganti dengan URL gambar nyata jika diperlukan)
    $sampleImageUrl = 'https://images.unsplash.com/photo-1610368633668-cca3d488c550?q=80&w=800&auto=format&fit=crop';

    try {
        // Download gambar dari internet
        $imageContent = file_get_contents($sampleImageUrl);
        if ($imageContent === false) {
            throw new Exception('Tidak dapat mengunduh gambar dari: ' . $sampleImageUrl);
        }

        // Simpan ke file sample-aerial.jpg di public/img
        $samplePath = public_path('img/sample-aerial.jpg');
        file_put_contents($samplePath, $imageContent);

        // Salin juga ke storage/app/public/aerial-photos/1/preview.jpg
        $directory = storage_path('app/public/aerial-photos/1');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        $storagePath = $directory . '/preview.jpg';
        file_put_contents($storagePath, $imageContent);

        return response()->json([
            'success' => true,
            'message' => 'Gambar sample berhasil dibuat',
            'paths' => [
                'public' => $samplePath,
                'storage' => $storagePath
            ],
            'sizes' => [
                'public' => filesize($samplePath),
                'storage' => filesize($storagePath)
            ]
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal membuat gambar sample: ' . $e->getMessage()
        ], 500);
    }
});

// Rute untuk YOLO Python API
Route::get('/api/yolo-status', function() {
    try {
        $logs = [];
        $logs[] = ['time' => now()->format('H:i:s'), 'message' => '🚀 Memulai debug server YOLO'];

        // Cek konfigurasi
        $apiUrl = env('PYTHON_API_URL', 'http://127.0.0.1:5000/yolo-inference');
        $modelPath = env('YOLO_MODEL_PATH', 'yolo/best14.pt');

        $logs[] = ['time' => now()->format('H:i:s'), 'message' => '🌐 URL API Python: ' . $apiUrl];
        $logs[] = ['time' => now()->format('H:i:s'), 'message' => '📂 Path model YOLO: ' . $modelPath];

        // Cek file model
        $fullModelPath = base_path($modelPath);
        $logs[] = ['time' => now()->format('H:i:s'), 'message' => '🔍 Full path model YOLO: ' . $fullModelPath];
        $logs[] = ['time' => now()->format('H:i:s'), 'message' => '✅ File model ' . (file_exists($fullModelPath) ? 'ada' : 'tidak ditemukan')];

        // Cek script Python
        $yoloScript = base_path('yolo/run_yolo_server.py');
        $inferenceScript = base_path('yolo/NEW inferensi model dari gpt.py');

        $logs[] = ['time' => now()->format('H:i:s'), 'message' => '🔍 Script YOLO server: ' . $yoloScript];
        $logs[] = ['time' => now()->format('H:i:s'), 'message' => '✅ File server script ' . (file_exists($yoloScript) ? 'ada' : 'tidak ditemukan')];

        $logs[] = ['time' => now()->format('H:i:s'), 'message' => '🔍 Script inferensi: ' . $inferenceScript];
        $logs[] = ['time' => now()->format('H:i:s'), 'message' => '✅ File inferensi script ' . (file_exists($inferenceScript) ? 'ada' : 'tidak ditemukan')];

        // Coba buat koneksi ke server
        $client = new \GuzzleHttp\Client();

        $logs[] = ['time' => now()->format('H:i:s'), 'message' => '🔄 Mencoba koneksi ke server Python...'];

        $response = $client->get(str_replace('/yolo-inference', '', $apiUrl), [
            'timeout' => 5,
            'connect_timeout' => 3
        ]);

        return response()->json([
            'status' => 'connected',
            'message' => 'Server Python YOLO aktif',
            'logs' => $logs,
            'version' => json_decode($response->getBody()->getContents(), true)['version'] ?? 'unknown'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'disconnected',
            'message' => 'Server Python YOLO tidak aktif: ' . $e->getMessage(),
            'logs' => $logs ?? [],
            'error_detail' => [
                'message' => $e->getMessage(),
                'type' => get_class($e),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString())[0]
            ],
            'help' => 'Jalankan server dengan perintah: cd yolo && python run_yolo_server.py'
        ], 500);
    }
})->name('api.yolo-status');

// Endpoint untuk debugging geometri plantation
Route::get('/debug/plantation/{id}', function($id) {
    try {
        $plantation = \App\Models\Plantation::find($id);
        if (!$plantation) {
            return response()->json(['error' => 'Plantation not found'], 404);
        }

        // Ambil geometri asli
        $originalGeometry = $plantation->geometry;

        // Coba metode 1: Konversi langsung
        $geojson1 = null;
        try {
            $result1 = DB::select("SELECT ST_AsGeoJSON(ST_GeomFromText('$originalGeometry')) as geojson");
            $geojson1 = $result1[0]->geojson ?? null;
        } catch (\Exception $e) {
            $error1 = $e->getMessage();
        }

        // Coba metode 2: Ambil langsung dari database
        $geojson2 = null;
        try {
            $result2 = DB::select("SELECT ST_AsGeoJSON(geometry) as geojson FROM plantations WHERE id = ?", [$id]);
            $geojson2 = $result2[0]->geojson ?? null;
        } catch (\Exception $e) {
            $error2 = $e->getMessage();
        }

        return response()->json([
            'plantation_id' => $id,
            'original_geometry' => $originalGeometry,
            'original_substr' => substr($originalGeometry, 0, 100) . '...',
            'method1' => [
                'success' => !empty($geojson1),
                'error' => $error1 ?? null,
                'geojson' => $geojson1
            ],
            'method2' => [
                'success' => !empty($geojson2),
                'error' => $error2 ?? null,
                'geojson' => $geojson2
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Error debugging plantation geometry: ' . $e->getMessage()
        ], 500);
    }
});

// Route untuk update video bantuan (hanya untuk super admin)
Route::middleware(['auth', 'role:Superadmin'])->group(function () {
    Route::put('/bantuan/update-video', [App\Http\Controllers\BantuanController::class, 'updateVideo'])->name('bantuan.update-video');
});

// Route untuk user data (dari api.php)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// YOLO Inference API routes (dari api.php)
Route::prefix('yolo')->middleware('auth:sanctum')->group(function () {
    Route::post('/inference', [YoloInferenceController::class, 'process']);
    Route::get('/digitasi/{plantationId}', [YoloInferenceController::class, 'getDigitasiGeoJson']);
});


















