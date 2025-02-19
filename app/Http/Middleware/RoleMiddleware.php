<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware {
    public function handle(Request $request, Closure $next, ...$roles) {
        if (!Auth::check()) {
            return redirect()->route('login'); // Redirect ke login jika tidak login
        }

        // Ambil role pengguna
        $userRole = Auth::user()->role;
        //role = role_id

        // Periksa apakah role pengguna sesuai dengan yang diizinkan
        if (!in_array($userRole, $roles)) {
            abort(403, 'Unauthorized access'); // Error 403 jika akses tidak diizinkan
        }

        return $next($request);
    }
}





