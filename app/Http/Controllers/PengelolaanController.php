<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kegiatan;
use App\Models\Tree;
use App\Models\Fertilization;
use App\Models\Pesticide;
use App\Models\Production;
use App\Models\TreeHealthProfile;
use App\Models\Plantation;
use Illuminate\Support\Facades\Auth;

class PengelolaanController extends Controller
{
    public function index(Request $request) {
        // Query dasar untuk kegiatan
        $kegiatanQuery = Kegiatan::query();

        // Filter berdasarkan status jika ada dari request
        if ($request->filled('filter')) {
            $filter = $request->filter;
            if ($filter === 'belum_berjalan') {
                $kegiatanQuery->where('status', 'Belum Berjalan');
            } elseif ($filter === 'sedang_berjalan') {
                $kegiatanQuery->where('status', 'Sedang Berjalan');
            } elseif ($filter === 'selesai') {
                $kegiatanQuery->where('status', 'Selesai');
            }
        }

        // Pencarian berdasarkan nama kegiatan
        if ($request->filled('search_kegiatan')) {
            $searchKegiatan = $request->search_kegiatan;
            $kegiatanQuery->where('nama_kegiatan', 'ILIKE', "%{$searchKegiatan}%");
        }

        // Urutkan berdasarkan tanggal mulai atau ID jika perlu
        $kegiatan = $kegiatanQuery->orderBy('tanggal_mulai', 'asc')->orderBy('id', 'asc')->get();

        // Ambil data pohon dengan paginasi
        $perPage = $request->input('per_page', 50);
        $treesQuery = Tree::query();

        // Pencarian berdasarkan ID Pohon
        if ($request->filled('search_tree_id')) {
            $searchTreeId = $request->search_tree_id;
            // Karena ID bisa mengandung huruf, kita cari yang mirip atau sama persis (case-insensitive)
            $treesQuery->whereRaw('UPPER(id) ILIKE ?', ["%" . strtoupper($searchTreeId) . "%"]);
        }

        if ($request->filled('varietas')) {
            $treesQuery->where('varietas', $request->varietas);
        }
        if ($request->filled('tahun_tanam')) {
            $treesQuery->where('tahun_tanam', $request->tahun_tanam);
        }
        if ($request->filled('health_status')) {
            $treesQuery->where('health_status', $request->health_status);
        }
        if ($request->filled('fase')) {
            $treesQuery->where('fase', $request->fase);
        }
        if ($request->filled('blok')) {
            $plantation = Plantation::where('name', $request->blok)->first();
            if ($plantation) {
                $treesQuery->where('plantation_id', $plantation->id);
            }
        }
        $treesQuery->orderByRaw("regexp_replace(UPPER(id), '[0-9]', '', 'g') ASC");
        $treesQuery->orderByRaw("CAST(regexp_replace(UPPER(id), '[^0-9]', '', 'g') AS INTEGER) ASC");

        if ($perPage === 'all') {
            $trees = $treesQuery->get();
            $trees = new \Illuminate\Pagination\LengthAwarePaginator(
                $trees,
                $trees->count(),
                PHP_INT_MAX,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $perPage = (int) $perPage;
            if (!in_array($perPage, [50, 100, 200, 500, 1000])) {
                $perPage = 50;
            }
            $trees = $treesQuery->paginate($perPage)->withQueryString();
        }
        $currentPage = $trees->currentPage();
        $trees->getCollection()->transform(function($item) {
            $item->id = strtoupper($item->id);
            return $item;
        });

        // Jadwal kegiatan (kegiatan yang belum selesai dan tanggal selesainya di masa depan atau hari ini)
        $schedules = Kegiatan::where('status', '!=', 'Selesai')
            ->whereDate('tanggal_selesai', '>=', now()->toDateString())
            ->orderBy('tanggal_selesai', 'asc')
            ->get();

        $fertilizations = Fertilization::orderBy('id', 'asc')->get();
        $pesticides = Pesticide::orderBy('id', 'asc')->get();
        $productions = Production::orderBy('id', 'asc')->get();
        $health_profiles = TreeHealthProfile::orderBy('id', 'asc')->get();

        // Mengambil data blok kebun
        $plantationsQuery = Plantation::query()->withCount('trees'); // Ganti menjadi query builder

        // Pencarian berdasarkan ID Blok Kebun
        if ($request->filled('search_blok_id')) {
            $searchBlokId = $request->search_blok_id;
            $plantationsQuery->where('id', 'ILIKE', "%{$searchBlokId}%"); // Asumsi ID adalah string atau bisa dicari dengan ILIKE
        }

        $plantations = $plantationsQuery->orderBy('id', 'asc')->get(); // Execute query untuk $plantations jika masih dibutuhkan

        $perPageBlok = (int) $request->input('per_page_blok', 50);
        if (!in_array($perPageBlok, [50, 100, 200])) {
            $perPageBlok = 50;
        }
        // Gunakan $plantationsQuery yang sudah ada filter pencariannya untuk paginasi
        $plantations_paged = $plantationsQuery->orderBy('id', 'asc')->paginate($perPageBlok)->withQueryString();

        $allTreeVarietas = Tree::distinct()->pluck('varietas')->sort()->values();
        $allPlantationNames = Plantation::distinct()->pluck('name')->sort()->values();
        $allTreeFases = Tree::distinct()->whereNotNull('fase')->pluck('fase')->sort()->values();

        // Untuk tab Riwayat Kegiatan
        $treeGrowthRecords = collect();
        $treeHealthRecords = collect();
        $treeFertilizationRecords = collect();
        $treePesticideRecords = collect();
        $treeZptRecords = collect();
        $treeHarvestRecords = collect();

        // Selalu memuat data riwayat kegiatan terlepas dari tab yang aktif
        $perPage = 15;

        // Filter yang sama untuk semua jenis riwayat
        $treeIdFilter = $request->search_tree_id;
        $varietasFilter = $request->varietas;
        $blokFilter = $request->blok;
        $tahunTanamFilter = $request->tahun_tanam;

        // Query Builder untuk TreeGrowth
        $treeGrowthQuery = \App\Models\TreeGrowth::with(['tree.plantation'])
            ->when($treeIdFilter, function($query) use ($treeIdFilter) {
                return $query->whereHas('tree', function($q) use ($treeIdFilter) {
                    $q->where('id', 'like', "%{$treeIdFilter}%");
                });
            })
            ->when($varietasFilter, function($query) use ($varietasFilter) {
                return $query->whereHas('tree', function($q) use ($varietasFilter) {
                    $q->where('varietas', $varietasFilter);
                });
            })
            ->when($tahunTanamFilter, function($query) use ($tahunTanamFilter) {
                return $query->whereHas('tree', function($q) use ($tahunTanamFilter) {
                    $q->where('tahun_tanam', $tahunTanamFilter);
                });
            })
            ->when($blokFilter, function($query) use ($blokFilter) {
                return $query->whereHas('tree.plantation', function($q) use ($blokFilter) {
                    $q->where('name', $blokFilter);
                });
            })
            ->orderBy('tanggal', 'desc');

        // Query Builder untuk TreeHealthProfile
        $treeHealthQuery = \App\Models\TreeHealthProfile::with(['tree.plantation'])
            ->when($treeIdFilter, function($query) use ($treeIdFilter) {
                return $query->whereHas('tree', function($q) use ($treeIdFilter) {
                    $q->where('id', 'like', "%{$treeIdFilter}%");
                });
            })
            ->when($varietasFilter, function($query) use ($varietasFilter) {
                return $query->whereHas('tree', function($q) use ($varietasFilter) {
                    $q->where('varietas', $varietasFilter);
                });
            })
            ->when($tahunTanamFilter, function($query) use ($tahunTanamFilter) {
                return $query->whereHas('tree', function($q) use ($tahunTanamFilter) {
                    $q->where('tahun_tanam', $tahunTanamFilter);
                });
            })
            ->when($blokFilter, function($query) use ($blokFilter) {
                return $query->whereHas('tree.plantation', function($q) use ($blokFilter) {
                    $q->where('name', $blokFilter);
                });
            })
            ->orderBy('tanggal_pemeriksaan', 'desc');

        // Query Builder untuk TreeFertilization
        $treeFertilizationQuery = \App\Models\TreeFertilization::with(['tree.plantation'])
            ->when($treeIdFilter, function($query) use ($treeIdFilter) {
                return $query->whereHas('tree', function($q) use ($treeIdFilter) {
                    $q->where('id', 'like', "%{$treeIdFilter}%");
                });
            })
            ->when($varietasFilter, function($query) use ($varietasFilter) {
                return $query->whereHas('tree', function($q) use ($varietasFilter) {
                    $q->where('varietas', $varietasFilter);
                });
            })
            ->when($tahunTanamFilter, function($query) use ($tahunTanamFilter) {
                return $query->whereHas('tree', function($q) use ($tahunTanamFilter) {
                    $q->where('tahun_tanam', $tahunTanamFilter);
                });
            })
            ->when($blokFilter, function($query) use ($blokFilter) {
                return $query->whereHas('tree.plantation', function($q) use ($blokFilter) {
                    $q->where('name', $blokFilter);
                });
            })
            ->orderBy('tanggal_pemupukan', 'desc');

        // Query Builder untuk TreePesticide
        $treePesticideQuery = \App\Models\TreePesticide::with(['tree.plantation'])
            ->when($treeIdFilter, function($query) use ($treeIdFilter) {
                return $query->whereHas('tree', function($q) use ($treeIdFilter) {
                    $q->where('id', 'like', "%{$treeIdFilter}%");
                });
            })
            ->when($varietasFilter, function($query) use ($varietasFilter) {
                return $query->whereHas('tree', function($q) use ($varietasFilter) {
                    $q->where('varietas', $varietasFilter);
                });
            })
            ->when($tahunTanamFilter, function($query) use ($tahunTanamFilter) {
                return $query->whereHas('tree', function($q) use ($tahunTanamFilter) {
                    $q->where('tahun_tanam', $tahunTanamFilter);
                });
            })
            ->when($blokFilter, function($query) use ($blokFilter) {
                return $query->whereHas('tree.plantation', function($q) use ($blokFilter) {
                    $q->where('name', $blokFilter);
                });
            })
            ->orderBy('tanggal_pestisida', 'desc');

        // Query Builder untuk TreeZpt
        $treeZptQuery = \App\Models\TreeZpt::with(['tree.plantation'])
            ->when($treeIdFilter, function($query) use ($treeIdFilter) {
                return $query->whereHas('tree', function($q) use ($treeIdFilter) {
                    $q->where('id', 'like', "%{$treeIdFilter}%");
                });
            })
            ->when($varietasFilter, function($query) use ($varietasFilter) {
                return $query->whereHas('tree', function($q) use ($varietasFilter) {
                    $q->where('varietas', $varietasFilter);
                });
            })
            ->when($tahunTanamFilter, function($query) use ($tahunTanamFilter) {
                return $query->whereHas('tree', function($q) use ($tahunTanamFilter) {
                    $q->where('tahun_tanam', $tahunTanamFilter);
                });
            })
            ->when($blokFilter, function($query) use ($blokFilter) {
                return $query->whereHas('tree.plantation', function($q) use ($blokFilter) {
                    $q->where('name', $blokFilter);
                });
            })
            ->orderBy('tanggal_aplikasi', 'desc');

        // Query Builder untuk Harvest
        $treeHarvestQuery = \App\Models\Harvest::with(['tree.plantation'])
            ->when($treeIdFilter, function($query) use ($treeIdFilter) {
                return $query->whereHas('tree', function($q) use ($treeIdFilter) {
                    $q->where('id', 'like', "%{$treeIdFilter}%");
                });
            })
            ->when($varietasFilter, function($query) use ($varietasFilter) {
                return $query->whereHas('tree', function($q) use ($varietasFilter) {
                    $q->where('varietas', $varietasFilter);
                });
            })
            ->when($tahunTanamFilter, function($query) use ($tahunTanamFilter) {
                return $query->whereHas('tree', function($q) use ($tahunTanamFilter) {
                    $q->where('tahun_tanam', $tahunTanamFilter);
                });
            })
            ->when($blokFilter, function($query) use ($blokFilter) {
                return $query->whereHas('tree.plantation', function($q) use ($blokFilter) {
                    $q->where('name', $blokFilter);
                });
            })
            ->orderBy('tanggal_panen', 'desc');

        // Eksekusi query berdasarkan subtab yang aktif
        $activeSubTab = $request->subtab ?? 'pertumbuhan';

        // Load records for all tabs, but only paginate the active one
        if ($activeSubTab == 'pertumbuhan' || $request->tab === 'riwayat') {
            $treeGrowthRecords = $treeGrowthQuery->paginate($perPage)->withQueryString();
        } else {
            $treeGrowthRecords = $treeGrowthQuery->get();
        }

        if ($activeSubTab == 'kesehatan' || $request->tab === 'riwayat') {
            $treeHealthRecords = $treeHealthQuery->paginate($perPage)->withQueryString();
        } else {
            $treeHealthRecords = $treeHealthQuery->get();
        }

        if ($activeSubTab == 'pemupukan' || $request->tab === 'riwayat') {
            $treeFertilizationRecords = $treeFertilizationQuery->paginate($perPage)->withQueryString();
        } else {
            $treeFertilizationRecords = $treeFertilizationQuery->get();
        }

        if ($activeSubTab == 'pestisida' || $request->tab === 'riwayat') {
            $treePesticideRecords = $treePesticideQuery->paginate($perPage)->withQueryString();
        } else {
            $treePesticideRecords = $treePesticideQuery->get();
        }

        if ($activeSubTab == 'zpt' || $request->tab === 'riwayat') {
            $treeZptRecords = $treeZptQuery->paginate($perPage)->withQueryString();
        } else {
            $treeZptRecords = $treeZptQuery->get();
        }

        if ($activeSubTab == 'panen' || $request->tab === 'riwayat') {
            $treeHarvestRecords = $treeHarvestQuery->paginate($perPage)->withQueryString();
        } else {
            $treeHarvestRecords = $treeHarvestQuery->get();
        }

        return view('pages.pengelolaan', compact('kegiatan', 'trees', 'fertilizations', 'pesticides', 'productions', 'health_profiles', 'schedules', 'plantations', 'currentPage', 'plantations_paged', 'allTreeVarietas', 'allPlantationNames', 'allTreeFases', 'treeGrowthRecords', 'treeHealthRecords', 'treeFertilizationRecords', 'treePesticideRecords', 'treeZptRecords', 'treeHarvestRecords'));
    }

    public function store(Request $request) {
        $validationRules = [
            'nama_kegiatan' => 'required|string|max:255',
            'jenis_kegiatan' => 'required|string|max:255',
            'deskripsi_kegiatan' => 'required|string',
            'tanggal_mulai' => 'required|date',
            'status' => 'required|in:Belum Berjalan,Sedang Berjalan,Selesai',
        ];

        // Tambahkan validasi tanggal_selesai hanya jika status = Selesai
        if ($request->status == 'Selesai') {
            $validationRules['tanggal_selesai'] = 'required|date';
        } else {
            $validationRules['tanggal_selesai'] = 'nullable|date';
        }

        $request->validate($validationRules);

        $data = $request->all();
        if (Auth::check()) {
            $data['user_id'] = Auth::id();
        }

        Kegiatan::create($data);
        return redirect()->route('pengelolaan', ['tab' => 'kegiatan'])->with('success', 'Kegiatan berhasil ditambahkan!');
    }

    public function destroy($id) {
        Kegiatan::findOrFail($id)->delete();
        return redirect()->route('pengelolaan', ['tab' => 'kegiatan'])->with('success', 'Kegiatan berhasil dihapus!');
    }

    public function edit($id) {
        $kegiatan = Kegiatan::findOrFail($id);
        // Anda mungkin perlu membuat view edit baru atau menyesuaikan yang lama jika field berubah signifikan
        // Untuk sekarang, kita asumsikan pengelolaan_edit.blade.php akan diadaptasi atau sudah sesuai
        return view('pages.pengelolaan_edit', compact('kegiatan'));
    }

    public function update(Request $request, $id) {
        $validationRules = [
            'nama_kegiatan' => 'required|string|max:255',
            'jenis_kegiatan' => 'required|string|max:255',
            'deskripsi_kegiatan' => 'required|string',
            'tanggal_mulai' => 'required|date',
            'status' => 'required|in:Belum Berjalan,Sedang Berjalan,Selesai',
        ];

        // Tambahkan validasi tanggal_selesai hanya jika status = Selesai
        if ($request->status == 'Selesai') {
            $validationRules['tanggal_selesai'] = 'required|date';
        } else {
            $validationRules['tanggal_selesai'] = 'nullable|date';
        }

        $request->validate($validationRules);

        $kegiatan = Kegiatan::findOrFail($id);

        $updateData = $request->only([
            'nama_kegiatan',
            'jenis_kegiatan',
            'deskripsi_kegiatan',
            'tanggal_mulai',
            'tanggal_selesai',
            'status'
        ]);

        if (Auth::check()) {
            $updateData['user_id'] = Auth::id(); // Tetap update user_id jika perlu
        }

        $kegiatan->update($updateData);

        return redirect()->route('pengelolaan', ['tab' => 'kegiatan'])->with('success', 'Kegiatan berhasil diperbarui!');
    }

    // Metode baru untuk update status
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Belum Berjalan,Sedang Berjalan,Selesai',
            'tanggal_selesai' => 'nullable|date',
        ]);

        $kegiatan = Kegiatan::findOrFail($id);
        $kegiatan->status = $request->status;

        // Jika status diubah menjadi 'Selesai'
        if ($request->status === 'Selesai') {
            // Gunakan tanggal_selesai dari request jika ada, jika tidak gunakan tanggal hari ini
            if ($request->filled('tanggal_selesai')) {
                $kegiatan->tanggal_selesai = $request->tanggal_selesai;
            } else {
                $kegiatan->tanggal_selesai = now();
            }
        }

        $kegiatan->save();

        return redirect()->route('pengelolaan', ['tab' => 'kegiatan'])->with('success', 'Status kegiatan berhasil diperbarui!');
    }

    // Metode selesai() lama bisa dihapus atau dikomentari jika tidak digunakan lagi
    /*
    public function selesai($id) {
        $kegiatan = Kegiatan::findOrFail($id);
        $kegiatan->selesai = 1; // Kolom 'selesai' sudah tidak ada
        $kegiatan->status = 'Selesai'; // Seharusnya menggunakan ini
        $kegiatan->save();
        return redirect()->route('pengelolaan')->with('success', 'Kegiatan berhasil ditandai selesai.');
    }
    */
}
