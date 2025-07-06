<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{
    /**
     * Menampilkan daftar pengguna dan role.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter berdasarkan role
        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        // Pencarian berdasarkan nama atau username (case insensitive)
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $searchTerm = strtolower($request->search);
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . $searchTerm . '%'])
                  ->orWhereRaw('LOWER(username) LIKE ?', ['%' . $searchTerm . '%']);
            });
        }

        $users = $query->orderBy('id', 'asc')->get();
        $roles = Role::all();
        return view('pages.akun', compact('users', 'roles'));
    }

    /**
     * Mengupdate role pengguna.
     */
    public function update(Request $request, $id)
    {
        // Pastikan hanya Superadmin yang bisa mengubah role
        if (Auth::user()->role_id !== 1) {
            return redirect()->route('akun')->with('error', 'Anda tidak memiliki izin untuk mengubah role pengguna.');
        }

        // Validasi request
        $request->validate([
            'role_id' => 'required|exists:roles,id', // Role ID harus valid di tabel roles
        ]);

        // Cek apakah user yang diedit ada
        $user = User::findOrFail($id);

        // Cegah Superadmin menurunkan dirinya sendiri menjadi Guest
        if ($user->id == Auth::id() && $request->role_id != 1) {
            return redirect()->route('akun')->with('error', 'Anda tidak bisa mengubah role Anda sendiri menjadi lebih rendah.');
        }

        // Update role_id
        $user->role_id = $request->role_id;

        // Ambil nama role berdasarkan role_id
        $roleName = Role::where('id', $request->role_id)->value('name');

        // Update juga kolom role
        $user->role = $roleName;

        // Simpan perubahan
        $user->save();

        return redirect()->route('akun')->with('success', 'Role pengguna berhasil diperbarui.');
    }

    /**
     * Menghapus pengguna.
     */
    public function destroy($id)
    {
        // Pastikan hanya Superadmin yang bisa menghapus user
        if (Auth::user()->role_id !== 1) {
            return redirect()->route('akun')->with('error', 'Anda tidak memiliki izin untuk menghapus pengguna.');
        }

        // Pastikan user yang dihapus ada
        $user = User::findOrFail($id);

        // Cegah Superadmin menghapus dirinya sendiri
        if ($user->id == Auth::id()) {
            return redirect()->route('akun')->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        // Hapus user
        $user->delete();
        return redirect()->route('akun')->with('success', 'Pengguna berhasil dihapus.');
    }

    public function profile()
    {
        $user = Auth::user()->load('role'); // Load relasi role
        
        // Simpan URL sebelumnya ke session, kecuali jika URL sebelumnya adalah halaman update profil
        $previous = url()->previous();
        $updateProfileUrl = route('akun.profil.update');
        
        // Jika URL sebelumnya bukan URL update profil dan bukan URL profil itu sendiri
        if (!str_contains($previous, 'akun/profil')) {
            session(['url.intended.after.profile' => $previous]);
        }
        
        return view('pages.profil', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        try {
            $request->validate([
                'name' => 'nullable|string|max:255',
                'username' => 'nullable|string|max:255|unique:users,username,' . $user->id,
                'email' => 'nullable|string|email|max:255|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:6|confirmed',
            ]);

            // Validasi tambahan: setidaknya name atau username harus diisi
            if (empty($request->name) && empty($request->username)) {
                return redirect()->route('akun.profil')
                    ->withInput()
                    ->withErrors(['name' => 'Setidaknya nama lengkap atau username harus diisi.']);
            }

            // Update nama dan username jika diisi
            if ($request->filled('name')) {
                $user->name = $request->name;
            }

            if ($request->filled('username')) {
                $user->username = $request->username;
            }
            
            // Update email jika diisi
            if ($request->filled('email') && $request->email !== $user->email) {
                $user->email = $request->email;
            }

            // Jika password diisi, update password
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            // Simpan URL kembali dalam flash session untuk digunakan setelah redirect
            return redirect()->route('akun.profil')->with('success', 'Profil berhasil diperbarui.');
        } catch (\Exception $e) {
            \Log::error('Gagal memperbarui profil: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'request' => $request->except('password', 'password_confirmation')
            ]);

            return redirect()->route('akun.profil')
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan perubahan: ' . $e->getMessage());
        }
    }
}
