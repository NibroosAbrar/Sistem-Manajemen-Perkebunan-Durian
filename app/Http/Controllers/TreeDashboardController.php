<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tree;
use App\Models\TreeFertilization;
use App\Models\TreePesticide;
use App\Models\Harvest;
use App\Models\TreeGrowth;
use App\Models\TreeHealthProfile;
use App\Models\TreeZpt;

class TreeDashboardController extends Controller
{
    public function index(Request $request)
    {
        $treeId = $request->input('id');

        if (!$treeId) {
            return redirect()->route('webgis')->with('error', 'ID pohon tidak ditemukan');
        }

        $tree = Tree::find($treeId);

        if (!$tree) {
            return redirect()->route('webgis')->with('error', 'Pohon tidak ditemukan');
        }

        // Get fertilization history
        $fertilizations = TreeFertilization::where('tree_id', $treeId)
            ->orderBy('tanggal_pemupukan', 'desc')
            ->get();

        // Get pesticide history
        $pesticides = TreePesticide::where('tree_id', $treeId)
            ->orderBy('tanggal_pestisida', 'desc')
            ->get();

        // Get harvest history
        $harvests = Harvest::where('tree_id', $treeId)
            ->orderBy('tanggal_panen', 'desc')
            ->get();

        // Get growth history
        $growths = TreeGrowth::where('tree_id', $treeId)
            ->orderBy('tanggal', 'desc')
            ->get();

        // Get health profile history
        $healthProfiles = TreeHealthProfile::where('tree_id', $treeId)
            ->orderBy('tanggal_pemeriksaan', 'desc')
            ->get();

        // Get ZPT history
        $zptRecords = TreeZpt::where('tree_id', $treeId)
            ->orderBy('tanggal_aplikasi', 'desc')
            ->get();

        // Calculate total harvest weight
        $totalHarvest = $harvests->sum('total_weight');

        return view('pages.tree-dashboard', compact(
            'tree',
            'fertilizations',
            'pesticides',
            'harvests',
            'growths',
            'healthProfiles',
            'zptRecords',
            'totalHarvest'
        ));
    }

    public function destroyFertilization($id)
    {
        try {
            $fertilization = TreeFertilization::findOrFail($id);
            $fertilization->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data pemupukan berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data pemupukan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyPesticide($id)
    {
        try {
            $pesticide = TreePesticide::findOrFail($id);
            $pesticide->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data pestisida berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data pestisida: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyHarvest($id)
    {
        try {
            $harvest = Harvest::findOrFail($id);
            $harvest->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data panen berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data panen: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyGrowth($id)
    {
        try {
            $growth = TreeGrowth::findOrFail($id);
            $growth->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data pertumbuhan berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data pertumbuhan: ' . $e->getMessage()
            ], 500);
        }
    }
}
