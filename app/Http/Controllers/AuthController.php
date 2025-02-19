<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Auth\Events\Registered;


class AuthController extends Controller {
    /**
     * Handle login request
     */
    public function login(Request $request) {
        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Pastikan role diambil sebagai object
            $role = is_object($user->role) ? $user->role->name : 'No Role Assigned';

            Log::info('Login successful', ['user_id' => $user->id, 'role' => $role]);

            return redirect()->route('webgis');
        }




        // Log error jika login gagal
        \Log::error('Login failed', ['email' => $request->email]);

        return back()->withErrors(['email' => 'Email atau kata sandi salah']);
    }

    /**
     * Handle user logout
     */
    public function logout() {
        Auth::logout();
        return redirect()->route('login')->with('status', 'Anda telah keluar.');
    }

    /**
     * Handle registration request
     */
    public function register(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Set role_id default ke Guest (sesuaikan ID role Guest di database)
        $defaultRoleId = 4; // Ganti dengan ID role "Guest" di database

        // Buat akun baru dengan role Guest
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $defaultRoleId,
        ]);

        // Kirim email verifikasi
        // event(new Registered($user));


        return redirect()->route('login')->with('success', 'Akun berhasil dibuat. Silakan cek email Anda untuk verifikasi!');
    }
}
