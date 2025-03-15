<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kegiatan;
use App\Models\Tree;
use App\Models\Fertilization;
use App\Models\Pesticide;
use App\Models\Production;
use App\Models\TreeHealthProfile;

class PengelolaanController extends Controller
{
    public function index() {
        $kegiatan = Kegiatan::orderBy('id', 'asc')->get();
        $trees = Tree::orderBy('id', 'asc')->get();
        $fertilizations = Fertilization::orderBy('id', 'asc')->get();
        $pesticides = Pesticide::orderBy('id', 'asc')->get();
        $productions = Production::orderBy('id', 'asc')->get();
        $health_profiles = TreeHealthProfile::orderBy('id', 'asc')->get();

        return view('pages.pengelolaan', compact('kegiatan', 'trees', 'fertilizations', 'pesticides', 'productions', 'health_profiles'));
    }

    public function store(Request $request) {
        $request->validate([
            'tanggal' => 'required|date',
            'jenis_kegiatan' => 'required|string',
            'deskripsi' => 'required|string',
            'petugas' => 'required|string',
        ]);

        Kegiatan::create($request->all());
        return redirect()->route('pengelolaan')->with('success', 'Kegiatan berhasil ditambahkan!');
    }

    public function destroy($id) {
        Kegiatan::findOrFail($id)->delete();
        return redirect()->route('pengelolaan')->with('success', 'Kegiatan berhasil dihapus!');
    }

    public function edit($id) {
        $kegiatan = Kegiatan::findOrFail($id);
        return view('pages.pengelolaan_edit', compact('kegiatan'));
    }

    public function update(Request $request, $id) {
        $request->validate([
            'tanggal' => 'required|date',
            'jenis_kegiatan' => 'required|string',
            'deskripsi' => 'required|string',
            'petugas' => 'required|string',
        ]);

        $kegiatan = Kegiatan::findOrFail($id);
        $kegiatan->update($request->all());

        return redirect()->route('pengelolaan')->with('success', 'Kegiatan berhasil diperbarui!');
    }
}
