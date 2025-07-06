<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tree;
use App\Models\Plantation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Get plantation_id from request, default to null (all plantations)
        $plantationId = $request->input('plantation_id');

        // Get current year and last year
        $currentYear = Carbon::now()->year;
        $lastYear = $currentYear - 1;

        // Get all plantations for filter dropdown
        $plantations = Plantation::orderBy('name')->get();

        // Base query builder for Tree
        $treeQuery = Tree::query();

        // Filter by plantation if requested
        if ($plantationId) {
            $treeQuery->where('plantation_id', $plantationId);
        }

        // Get total trees count with filter if applied
        $totalTrees = $treeQuery->count();

        // For last year comparison, apply the same plantation filter
        $lastYearTreeQuery = Tree::whereYear('created_at', $lastYear);
        if ($plantationId) {
            $lastYearTreeQuery->where('plantation_id', $plantationId);
        }
        $lastYearTrees = $lastYearTreeQuery->count();

        // Pastikan perubahan persentase dihitung dengan benar
        if ($lastYearTrees > 0) {
            $treeGrowthRate = round((($totalTrees - $lastYearTrees) / $lastYearTrees) * 100, 1);
        } elseif ($totalTrees > 0) {
            // Jika tidak ada data tahun lalu tapi ada data tahun ini, persentase pertumbuhan adalah 100%
            $treeGrowthRate = 100;
        } else {
            // Jika tidak ada data sama sekali
            $treeGrowthRate = 0;
        }

        // Get total area with filter if applied
        if ($plantationId) {
            $totalArea = Plantation::where('id', $plantationId)->sum('luas_area');
        } else {
            $totalArea = Plantation::sum('luas_area');
        }

        // Base query for harvests with filter by plantation if needed
        $harvestQuery = DB::table('harvests')
            ->whereYear('tanggal_panen', $currentYear);

        if ($plantationId) {
            // Join with trees table to filter by plantation_id
            $harvestQuery->join('trees', 'harvests.tree_id', '=', 'trees.id')
                        ->where('trees.plantation_id', $plantationId);
        }

        // Calculate total production
        $totalProduction = $harvestQuery->sum('total_weight');

        // Calculate last year's total production with same filter
        $lastYearHarvestQuery = DB::table('harvests')
            ->whereYear('tanggal_panen', $lastYear);

        if ($plantationId) {
            $lastYearHarvestQuery->join('trees', 'harvests.tree_id', '=', 'trees.id')
                                ->where('trees.plantation_id', $plantationId);
        }

        $lastYearProduction = $lastYearHarvestQuery->sum('total_weight');

        // Calculate production growth rate dengan lebih akurat
        if ($lastYearProduction > 0) {
            $productionGrowth = round((($totalProduction - $lastYearProduction) / $lastYearProduction) * 100, 1);
        } elseif ($totalProduction > 0) {
            // Jika tidak ada produksi tahun lalu tapi ada produksi tahun ini
            $productionGrowth = 100;
        } else {
            // Jika tidak ada produksi sama sekali
            $productionGrowth = 0;
        }

        // Placeholder for productivity distribution (since productivity column is removed)
        $productivityDistribution = [
            'Belum Panen' => 0,
            'Rendah' => 0,
            'Sedang' => 0,
            'Tinggi' => 0
        ];

        // Placeholder for average productivity (since productivity column is removed)
        $avgProductivity = 0;

        // Placeholder for productivity growth (since productivity column is removed)
        $productivityGrowth = 0;

        // Calculate healthy tree percentage based on filter
        $healthyTreeQuery = clone $treeQuery;
        $healthyTrees = $healthyTreeQuery->where('health_status', 'Sehat')->count();
        $healthyTreePercentage = $totalTrees > 0 ? round(($healthyTrees / $totalTrees) * 100, 1) : 0;

        // Prepare tree growth data (dynamic range with one preceding year)
        $treeGrowthData = [];
        $treeGrowthLabels = [];
        $minTreeYearQuery = Tree::query();
        if ($plantationId) {
            $minTreeYearQuery->where('plantation_id', $plantationId);
        }
        $actualMinTreeYear = $minTreeYearQuery->min(DB::raw('EXTRACT(YEAR FROM created_at)'));

        // Determine the effective start year for the data series (data start or current year)
        $effectiveStartYearTree = $actualMinTreeYear ? (int)$actualMinTreeYear : $currentYear;
        // Ensure this effective start year is not after the current year
        $effectiveStartYearTree = min($effectiveStartYearTree, $currentYear);

        // Set the loop to start one year before the effective start year
        $loopStartYearTree = $effectiveStartYearTree - 1;

        for ($year = $loopStartYearTree; $year <= $currentYear; $year++) {
            $treeGrowthLabels[] = (string)$year;
            $yearlyTreeQuery = Tree::whereYear('created_at', $year);
            if ($plantationId) {
                $yearlyTreeQuery->where('plantation_id', $plantationId);
            }
            $treeGrowthData[] = $yearlyTreeQuery->count();
        }

        if (empty($treeGrowthLabels)) {
            // Fallback if the loop didn't run (e.g., $loopStartYearTree > $currentYear, highly unlikely with valid currentYear)
            // Ensure at least previous and current year are shown.
            $treeGrowthLabels[] = (string)($currentYear - 1);
            $treeGrowthData[] = 0;
            $treeGrowthLabels[] = (string)$currentYear;
            $treeGrowthData[] = 0;
        }

        // Prepare production data (dynamic range with one preceding year)
        $productivityData = [];
        $productivityLabels = [];
        $minProdYearQuery = DB::table('harvests');
        if ($plantationId) {
            $minProdYearQuery->join('trees', 'harvests.tree_id', '=', 'trees.id')
                           ->where('trees.plantation_id', $plantationId);
        }
        $actualMinProdYear = $minProdYearQuery->min(DB::raw('EXTRACT(YEAR FROM tanggal_panen)'));

        // Determine the effective start year for the data series (data start or current year)
        $effectiveStartYearProd = $actualMinProdYear ? (int)$actualMinProdYear : $currentYear;
        // Ensure this effective start year is not after the current year
        $effectiveStartYearProd = min($effectiveStartYearProd, $currentYear);

        // Set the loop to start one year before the effective start year
        $loopStartYearProd = $effectiveStartYearProd - 1;

        for ($year = $loopStartYearProd; $year <= $currentYear; $year++) {
            $productivityLabels[] = (string)$year;
            $yearlyProductionQuery = DB::table('harvests')
                ->whereYear('tanggal_panen', $year);
            if ($plantationId) {
                $yearlyProductionQuery->join('trees', 'harvests.tree_id', '=', 'trees.id')
                                     ->where('trees.plantation_id', $plantationId);
            }
            $yearlyProduction = $yearlyProductionQuery->sum('total_weight');
            $productivityData[] = round($yearlyProduction, 1);
        }

        if (empty($productivityLabels)) {
            // Fallback if the loop didn't run
            $productivityLabels[] = (string)($currentYear - 1);
            $productivityData[] = 0;
            $productivityLabels[] = (string)$currentYear;
            $productivityData[] = 0;
        }

        // Prepare Fase Tanaman data with filter
        $faseTanamanQuery = clone $treeQuery; // Menggunakan clone dari $treeQuery yang sudah ada filternya
        $faseTanamanCounts = $faseTanamanQuery
                                ->select('fase', DB::raw('count(*) as total'))
                                ->whereIn('fase', ['Generatif', 'Vegetatif']) // Hanya ambil Generatif dan Vegetatif
                                ->groupBy('fase')
                                ->pluck('total', 'fase');

        $faseTanamanLabels = ['Generatif', 'Vegetatif'];
        $faseTanamanData = [
            $faseTanamanCounts->get('Generatif', 0),
            $faseTanamanCounts->get('Vegetatif', 0),
        ];

        // Prepare health status data with filter
        $healthStatusData = [
            $treeQuery->clone()->where('health_status', 'Sehat')->count(),
            $treeQuery->clone()->where('health_status', 'Stres')->count(),
            $treeQuery->clone()->where('health_status', 'Sakit')->count(),
            $treeQuery->clone()->where('health_status', 'Mati')->count()
        ];

        // Prepare age distribution data with filter
        $ageDistributionData = [];
        $ageDistributionLabels = [];
        $currentDate = Carbon::now();

        // NEW LOGIC FOR DYNAMIC AGE DISTRIBUTION
        $treesWithAgeQuery = Tree::selectRaw('EXTRACT(YEAR FROM CURRENT_DATE) - tahun_tanam AS age');
        if ($plantationId) {
            $treesWithAgeQuery->where('plantation_id', $plantationId);
        }
        $treeAges = $treesWithAgeQuery->pluck('age')->filter(function ($age) {
            return $age !== null && $age >= 0; // Filter out null or negative ages
        });

        if ($treeAges->isNotEmpty()) {
            $minActualAge = $treeAges->min();
            $maxActualAge = $treeAges->max();

            if ($maxActualAge <= 10) { // Combined logic for max age up to 10 years
                $loopStartAge = ($minActualAge > 0) ? $minActualAge - 1 : 0;

                for ($year = $loopStartAge; $year <= $maxActualAge; $year++) {
                    $ageDistributionLabels[] = "$year tahun";
                    $count = $treeAges->filter(function ($age) use ($year) {
                        return $age == $year;
                    })->count();
                    $ageDistributionData[] = $count;
                }

                // Ensure there is at least one label if loop didn't run but ages exist (e.g. maxActualAge is 0)
                if (empty($ageDistributionLabels) && $maxActualAge == 0 && $minActualAge == 0) {
                     $ageDistributionLabels[] = "0 tahun";
                     $ageDistributionData[] = $treeAges->count();
                }

            } else { // maxActualAge > 10 (use 5-year bins)
                // Current logic for 5-year bins already starts from "0-5 tahun",
                // which covers the "one period before" if minActualAge is higher.
                // So, this part remains largely the same as your existing refined version.
                $range_query_start = 0;
                $range_query_end = 0;

                for ($i = 0; ; $i += 5) {
                    $current_range_start_for_label = $i;
                    $current_range_end_for_label = $i + 4;

                    if ($current_range_start_for_label == 0) {
                        $label = "0-5 tahun";
                        $range_query_start = 0;
                        $range_query_end = 5;
                    } elseif ($current_range_start_for_label >= 21 && $label !== "20+ tahun") { // Ensure 20+ is the last label
                        $label = "20+ tahun";
                        $range_query_start = 21;
                        $range_query_end = 999; // Represents infinity for query
                    } else {
                        $label_end = $current_range_end_for_label + 1;
                        $label = "{$current_range_start_for_label}-{$label_end} tahun";
                        $range_query_start = $current_range_start_for_label;
                        $range_query_end = $label_end;
                    }

                    // Specific overrides for standard ranges if not 20+
                    if ($label === "6-10 tahun") { $range_query_start = 6; $range_query_end = 10; }
                    if ($label === "11-15 tahun") { $range_query_start = 11; $range_query_end = 15; }
                    if ($label === "16-20 tahun") { $range_query_start = 16; $range_query_end = 20; }

                    $ageDistributionLabels[] = $label;
                    $count = $treeAges->filter(function ($age) use ($range_query_start, $range_query_end, $label) {
                        if ($label === "20+ tahun") {
                            return $age >= $range_query_start;
                        }
                        return $age >= $range_query_start && $age <= $range_query_end;
                    })->count();
                    $ageDistributionData[] = $count;

                    if ($label === "20+ tahun" || $range_query_end >= $maxActualAge) {
                        // If the current range covers or exceeds maxActualAge, and we are not in the initial 0-5 range while maxActualAge is small
                        if ($range_query_end >= $maxActualAge || $current_range_start_for_label > $maxActualAge) break;
                        if ($label === "20+ tahun" && $maxActualAge >=21) break;
                        if ($label !== "20+ tahun" && $range_query_end >= $maxActualAge && $maxActualAge > 5) break;
                    }
                     // Safety break if loop runs too long (e.g. maxActualAge is very large, though 20+ should catch it)
                    if ($current_range_start_for_label > $maxActualAge && $current_range_start_for_label > 25) break;
                }
            }

            // Ensure labels and data are not empty if there are trees
            if ($treeAges->isNotEmpty() && empty($ageDistributionLabels)) {
                // Fallback to a single range if specific logic doesn't produce labels
                $ageDistributionLabels[] = $minActualAge . "-" . $maxActualAge . " tahun";
                $ageDistributionData[] = $treeAges->count();
            }

        } else {
            // Default if no trees or no age data
            $ageDistributionLabels = ['Tidak ada data usia'];
            $ageDistributionData[] = [0];
        }
        // END OF NEW LOGIC

        // Prepare varietas distribution data with filter
        $varietasQuery = Tree::select(
            DB::raw('LOWER(varietas) as varietas_lower'),
            'varietas',
            DB::raw('count(*) as total')
        );

        if ($plantationId) {
            $varietasQuery->where('plantation_id', $plantationId);
        }

        $varietasDistribution = $varietasQuery->groupBy('varietas_lower', 'varietas')
            ->orderBy('total', 'desc')
            ->get();

        // Gabungkan varietas yang sama dengan case berbeda
        $varietasMap = [];
        $varietasLabels = [];
        $varietasData = [];

        foreach ($varietasDistribution as $varietas) {
            $key = $varietas->varietas_lower ?: 'tidak diketahui';

            if (!isset($varietasMap[$key])) {
                // Gunakan versi asli untuk label dengan ucwords untuk kapitalisasi awal kata
                $originalLabel = $varietas->varietas ?: 'Tidak Diketahui';
                $formattedLabel = ucwords(strtolower($originalLabel));

                $varietasMap[$key] = [
                    'label' => $formattedLabel,
                    'total' => $varietas->total
                ];
            } else {
                // Tambahkan total jika varietas dengan case berbeda
                $varietasMap[$key]['total'] += $varietas->total;
            }
        }

        // Konversi ke array untuk chart dan urutkan berdasarkan total (terbesar ke terkecil)
        arsort($varietasMap);

        foreach ($varietasMap as $data) {
            $varietasLabels[] = $data['label'];
            $varietasData[] = $data['total'];
        }

        // Get environmental data
        $environmentalData = [
            'rainfall' => [100, 120, 150, 130, 110, 140],
            'temperature' => [30, 32, 31, 29, 28, 30],
            'humidity' => [70, 75, 72, 68, 65, 70],
            'years' => ['2020', '2021', '2022', '2023', '2024', '2025']
        ];

        return view('pages.dashboard', compact(
            'plantations',
            'plantationId',
            'totalTrees',
            'treeGrowthRate',
            'totalArea',
            'totalProduction',
            'productionGrowth',
            'healthyTreePercentage',
            'treeGrowthData',
            'treeGrowthLabels',
            'productivityData',
            'productivityLabels',
            'healthStatusData',
            'ageDistributionData',
            'ageDistributionLabels',
            'environmentalData',
            'productivityDistribution',
            'varietasLabels',
            'varietasData',
            'faseTanamanLabels',
            'faseTanamanData'
        ));
    }
}
