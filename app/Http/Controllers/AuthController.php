<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Password;


class AuthController extends Controller {
    /**
     * Menampilkan halaman login dengan kredensial akun guest yang sudah terisi otomatis
     */
    public function showLoginForm()
    {
        // Kredensial akun guest
        $guestCredentials = [
            'login' => 'guest',
            'password' => 'guest123'
        ];

        return view('auth.login', compact('guestCredentials'));
    }

    /**
     * Handle login request
     */
    public function login(Request $request) {
        // Jika input login adalah email, gunakan field email untuk autentikasi
        // Jika tidak, coba gunakan username
        $loginField = filter_var($request->input('login'), FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $loginField => $request->input('login'),
            'password' => $request->input('password')
        ];

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Pastikan role diambil sebagai object
            $role = is_object($user->role) ? $user->role->name : 'No Role Assigned';

            Log::info('Login successful', ['user_id' => $user->id, 'role' => $role]);

            return redirect()->route('dashboard');
        }

        // Jika login gagal dan input adalah email, tidak perlu mencoba lagi dengan cara lain
        if ($loginField === 'email') {
            \Log::error('Login failed with email', ['login' => $request->input('login')]);
            return back()->withErrors(['login' => 'Email atau kata sandi salah']);
        }

        // Jika login dengan username gagal, coba dengan tabel users dengan kolom username
        $userByUsername = User::where('username', $request->input('login'))->first();
        
        // Jika pengguna ditemukan dengan username tersebut
        if ($userByUsername) {
            // Coba autentikasi dengan password
            if (Hash::check($request->input('password'), $userByUsername->password)) {
                Auth::login($userByUsername);
                
                $role = is_object($userByUsername->role) ? $userByUsername->role->name : 'No Role Assigned';
                Log::info('Login successful dengan username', ['user_id' => $userByUsername->id, 'role' => $role]);
                
                return redirect()->route('dashboard');
            }
        }

        // Log error jika login gagal
        \Log::error('Login failed', ['login' => $request->input('login')]);

        return back()->withErrors(['login' => 'Username/Email atau kata sandi salah']);
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
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Set role_id default ke Guest (sesuaikan ID role Guest di database)
        $defaultRoleId = 4; // Ganti dengan ID role "Guest" di database

        // Buat akun baru dengan role Guest
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $defaultRoleId,
        ]);

        // Kirim email verifikasi
        // event(new Registered($user));


        return redirect()->route('login')->with('success', 'Akun berhasil dibuat. Silakan cek email Anda untuk verifikasi!');
    }

    /**
     * Menampilkan form untuk meminta reset password
     */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Mengirim link reset password ke email pengguna
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __('Link reset password telah dikirim ke email Anda.')])
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Menampilkan form untuk reset password
     */
    public function showResetPasswordForm($token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    /**
     * Reset password pengguna
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ]);

                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __('Password berhasil direset!'))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
