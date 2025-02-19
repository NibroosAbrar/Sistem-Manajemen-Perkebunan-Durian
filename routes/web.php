<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StockController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
});


















