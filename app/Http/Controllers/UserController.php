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
    public function index()
    {
        $users = User::orderBy('id', 'asc')->get(); // Tetap urut berdasarkan ID
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
        return view('pages.profil', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        // Update nama
        $user->name = $request->name;

        // Jika password diisi, update password
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('akun.profil')->with('success', 'Profil berhasil diperbarui.');
    }
}
