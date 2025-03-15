<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tree;
use App\Models\Plantation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Get current year and last year
        $currentYear = Carbon::now()->year;
        $lastYear = $currentYear - 1;

        // Get total trees count
        $totalTrees = Tree::count();
        $lastYearTrees = Tree::whereYear('created_at', $lastYear)->count();
        $treeGrowthRate = $lastYearTrees > 0 ? round((($totalTrees - $lastYearTrees) / $lastYearTrees) * 100, 1) : 0;

        // Get total area
        $totalArea = Plantation::sum('area_size');

        // Calculate total production (total weight from all harvests)
        $totalProduction = DB::table('harvests')->sum('total_weight');

        // Calculate last year's total production
        $lastYearProduction = DB::table('harvests')
            ->whereYear('created_at', $lastYear)
            ->sum('total_weight');

        // Calculate production growth rate
        $productionGrowth = $lastYearProduction > 0 ?
            round((($totalProduction - $lastYearProduction) / $lastYearProduction) * 100, 1) : 0;

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

        // Calculate healthy tree percentage
        $healthyTrees = Tree::where('health_status', 'Sehat')->count();
        $healthyTreePercentage = $totalTrees > 0 ? round(($healthyTrees / $totalTrees) * 100, 1) : 0;

        // Prepare tree growth data (last 6 years)
        $treeGrowthData = [];
        $treeGrowthLabels = [];
        for ($i = 5; $i >= 0; $i--) {
            $year = $currentYear - $i;
            $treeGrowthLabels[] = (string)$year;
            $treeGrowthData[] = Tree::whereYear('created_at', $year)->count();
        }

        // Prepare production data (last 12 months)
        $productivityData = [];
        $productivityLabels = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $productivityLabels[] = $date->format('M Y');

            // Get total production for each month
            $monthlyProduction = DB::table('harvests')
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('total_weight');

            $productivityData[] = round($monthlyProduction, 1);
        }

        // Prepare health status data
        $healthStatusData = [
            Tree::where('health_status', 'Sehat')->count(),
            Tree::where('health_status', 'Stres')->count(),
            Tree::where('health_status', 'Terinfeksi')->count(),
            Tree::where('health_status', 'Mati')->count()
        ];

        // Prepare age distribution data
        $ageRanges = [
            '0-5 tahun' => [0, 5],
            '6-10 tahun' => [6, 10],
            '11-15 tahun' => [11, 15],
            '15-20 tahun' => [16, 20],
            '20+ tahun' => [21, 999]
        ];

        $ageDistributionData = [];
        $ageDistributionLabels = [];
        $currentDate = Carbon::now();

        foreach ($ageRanges as $label => $range) {
            $ageDistributionLabels[] = $label;
            $count = Tree::whereRaw('(EXTRACT(YEAR FROM CURRENT_DATE) - tahun_tanam) BETWEEN ? AND ?', [$range[0], $range[1]])->count();
            $ageDistributionData[] = $count;
        }

        // Get environmental data
        $environmentalData = [
            'rainfall' => [100, 120, 150, 130, 110, 140],
            'temperature' => [30, 32, 31, 29, 28, 30],
            'humidity' => [70, 75, 72, 68, 65, 70],
            'years' => ['2020', '2021', '2022', '2023', '2024', '2025']
        ];

        return view('pages.dashboard', compact(
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
            'productivityDistribution'
        ));
    }
}
