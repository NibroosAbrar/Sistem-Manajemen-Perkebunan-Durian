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

// Redirect ke halaman login jika tidak ada rute
Route::get('/', function () {
    return redirect()->route('login');
});

// Login & Logout Routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Register dengan verifikasi email
Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::post('/register', [AuthController::class, 'register'])->name('register');

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
        Route::put('/stok/{stock}', [StockController::class, 'update'])->name('stok.update');
        Route::delete('/stok/{stock}', [StockController::class, 'destroy'])->name('stok.destroy');
    });

    // Profil
    Route::middleware(['auth'])->group(function () {
        Route::get('/akun/profil', [UserController::class, 'profile'])->name('akun.profil');
        Route::put('/akun/profil/update', [UserController::class, 'updateProfile'])->name('akun.profil.update');
    });


    Route::middleware(['auth', 'role:Superadmin,Manajer,Operasional'])->group(function () {
        Route::get('/pengelolaan', [PengelolaanController::class, 'index'])->name('pengelolaan');
        Route::post('/pengelolaan', [PengelolaanController::class, 'store'])->name('pengelolaan.store');
        Route::delete('/pengelolaan/{id}', [PengelolaanController::class, 'destroy'])->name('pengelolaan.destroy');
        Route::get('/pengelolaan/{id}/edit', [PengelolaanController::class, 'edit'])->name('pengelolaan.edit');
        Route::put('/pengelolaan/{id}', [PengelolaanController::class, 'update'])->name('pengelolaan.update');

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

            // Plantation API Routes
            Route::apiResource('/plantations', 'App\Http\Controllers\API\PlantationController');
        });

        // Detail Pengelolaan
        Route::get('/pengelolaan/detail', [PengelolaanDetailController::class, 'index'])->name('pengelolaan.detail');

        // Aerial Photo Routes
        Route::prefix('api')->group(function () {
            Route::post('/create-aerial-photo', [AerialPhotoController::class, 'store'])->name('aerial-photo.store');
            Route::put('/update-aerial-photo/{id}', [AerialPhotoController::class, 'update'])->name('aerial-photo.update');
            Route::get('/latest-aerial-photo', [AerialPhotoController::class, 'getLatest'])->name('aerial-photo.latest');
        });

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

    Route::get('/aerial-photo/create', [AerialPhotoController::class, 'create'])->name('aerial-photo.create');
    Route::post('/aerial-photo', [AerialPhotoController::class, 'store'])->name('aerial-photo.store');
    Route::get('/aerial-photo/edit', [AerialPhotoController::class, 'edit'])->name('aerial-photo.edit');

});


















