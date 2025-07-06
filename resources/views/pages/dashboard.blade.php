@extends('layouts.app')

@section('title', 'Dashboard - Symadu')
@section('header-title', 'Dashboard Kebun')

@push('styles')
<style>
    :root {
        --primary-color: #2c5282;
        --primary-light: #3182ce;
        --accent-color: #4299e1;
        --success-color: #38a169;
        --warning-color: #dd6b20;
        --danger-color: #e53e3e;
        --neutral-color: #718096;
        --bg-color: #f7fafc;
        --card-bg: #ffffff;
        --text-primary: #2d3748;
        --text-secondary: #4a5568;
        --text-muted: #718096;
        --border-color: #e2e8f0;

        /* Warna untuk kartu */
        --card-1-color: #ebf8ff;  /* Biru sangat muda */
        --card-2-color: #e6fffa;  /* Teal sangat muda */
        --card-3-color: #faf5ff;  /* Ungu sangat muda */
        --card-4-color: #fff5f5;  /* Merah sangat muda */

        /* Warna untuk card waktu */
        --farm-green-dark: #2F855A;
        --farm-green-medium: #38A169;
        --farm-green-light: #9AE6B4;
        --farm-brown: #97664B;
        --farm-sand: #F6E05E;
    }

    body {
        background-color: var(--bg-color);
    }

    .chart-box {
        background: var(--card-bg);
        padding: 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.06);
        margin-bottom: 1.25rem;
        border: 1px solid var(--border-color);
        transition: all 0.2s ease;
    }

    .chart-box:hover {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .chart-container {
        height: 250px;
        width: 100%;
    }

    .chart-wrapper {
        position: relative;
        height: 100%;
        width: 100%;
    }

    .chart-header {
        margin-bottom: 1rem;
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--text-primary);
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 0.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .dashboard-card {
        background: var(--card-bg);
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.06);
        padding: 1.5rem;
        transition: all 0.2s ease;
        border: 1px solid var(--border-color);
    }

    .dashboard-card:hover {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .card-1 {
        background-color: var(--card-1-color);
        border-left: 4px solid var(--primary-color);
    }

    .card-2 {
        background-color: var(--card-2-color);
        border-left: 4px solid var(--success-color);
    }

    .card-3 {
        background-color: var(--card-3-color);
        border-left: 4px solid var(--accent-color);
    }

    .card-4 {
        background-color: var(--card-4-color);
        border-left: 4px solid var(--warning-color);
    }

    .card-title {
        font-size: 1rem;
        font-weight: 500;
        color: var(--text-secondary);
        margin-bottom: 0.5rem;
    }

    .card-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .growth-positive {
        color: var(--success-color);
        font-weight: 600;
    }

    .growth-negative {
        color: var(--danger-color);
        font-weight: 600;
    }

    .timestamp-card {
        background: linear-gradient(to right, #1a365d, #2c5282);
        color: white;
        border: none;
        overflow: hidden;
        position: relative;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        border-radius: 0.5rem;
    }

    .timestamp-card::before {
        display: none;
    }

    .time-container {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        position: relative;
    }

    .time-icon-container {
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        box-shadow: none;
    }

    .digital-clock {
        font-size: 2.25rem;
        font-weight: 600;
        letter-spacing: 1px;
        text-shadow: none;
        font-family: 'Arial', sans-serif;
        line-height: 1;
        color: white;
    }

    .date-display {
        font-size: 0.95rem;
        font-weight: 400;
        opacity: 0.9;
        margin-top: 0.25rem;
        letter-spacing: 0;
    }

    .time-footer {
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .greeting {
        font-weight: 500;
        font-size: 1rem;
        letter-spacing: 0;
    }

    .season-info {
        display: flex;
        align-items: center;
        background-color: rgba(255, 255, 255, 0.1);
        padding: 0.4rem 0.75rem;
        border-radius: 4px;
        font-size: 0.85rem;
        box-shadow: none;
        letter-spacing: 0;
    }

    .season-info svg {
        margin-right: 0.35rem;
        height: 16px;
        width: 16px;
    }

    #welcome-message {
        font-size: 1.1rem;
        font-weight: 500;
        margin-bottom: 0.75rem;
        text-shadow: none;
        display: block;
        background: none;
        padding: 0;
        border-radius: 0;
        animation: none;
        letter-spacing: 0;
        border: none;
    }

    .username {
        color: #fbd38d;
        position: relative;
        display: inline;
        font-weight: 600;
    }

    .username::after {
        display: none;
    }

    .time-badge {
        background-color: rgba(255, 255, 255, 0.1);
        padding: 0.35rem 0.65rem;
        border-radius: 4px;
        border: none;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .clock-label {
        font-size: 0.7rem;
        font-weight: 400;
        opacity: 0.8;
        margin-bottom: 0.2rem;
    }

    .online-status {
        display: flex;
        align-items: center;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .status-dot {
        width: 6px;
        height: 6px;
        background-color: #68d391;
        border-radius: 50%;
        margin-right: 0.3rem;
        position: relative;
    }

    .status-dot::after {
        display: none;
    }

    .filter-container {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .filter-label {
        font-size: 0.875rem;
        color: var(--text-secondary);
        font-weight: 500;
    }

    .filter-select {
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
        border: 1px solid var(--border-color);
        background-color: white;
        font-size: 0.875rem;
        color: var(--text-primary);
        min-width: 150px;
    }

    .filter-select:focus {
        outline: none;
        border-color: var(--primary-light);
        box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
    }
</style>
@endpush

@section('content')
<div class="w-full flex flex-col h-screen overflow-y-auto">
    <!-- Main Content -->
    <div class="w-full overflow-x-hidden border-t flex flex-col">
        <main class="w-full flex-grow p-6 overflow-y-auto pb-32">
            <!-- Timestamp Card -->
            <div class="dashboard-card timestamp-card mb-6">
                <div class="time-container">
                    <div class="time-icon-container">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div>
                        <div id="welcome-message">Selamat Datang <span class="username">{{ Auth::user()->name ?? 'Pengguna' }}</span> di Symadu</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                    <div class="col-span-2">
                        <div class="digital-clock" id="digital-clock"></div>
                        <div class="date-display" id="date-display"></div>
                    </div>
                    <div class="flex justify-end items-end">
                        <div class="time-badge">
                            <div class="clock-label">Status Server</div>
                            <div class="online-status">
                                <span class="status-dot"></span>
                                <span>Online</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="time-footer">
                    <div class="greeting" id="greeting"></div>
                    <div class="season-info">
                        <svg xmlns="http://www.w3.org/2000/svg" class="text-white" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M5.5 16a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.977A4.5 4.5 0 1113.5 16h-8z" />
                        </svg>
                        <span id="season-text"></span>
                    </div>
                </div>
            </div>
            <!-- Scoreboard Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <!-- Total Pohon Card -->
                <div class="dashboard-card card-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="card-title">Total Pohon</h3>
                            <p class="card-value">{{ $totalTrees }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm {{ $treeGrowthRate >= 0 ? 'growth-positive' : 'growth-negative' }}">
                                {{ $treeGrowthRate >= 0 ? '+' : '' }}{{ number_format($treeGrowthRate, 1) }}%
                            </p>
                            <p class="text-xs text-gray-500">dari tahun lalu</p>
                        </div>
                    </div>
                </div>

                <!-- Total Area Card -->
                <div class="dashboard-card card-2">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="card-title">Total Area</h3>
                            <p class="card-value">{{ number_format($totalArea, 2) }} ha</p>
                        </div>
                    </div>
                </div>

                <!-- Produktivitas Card -->
                <div class="dashboard-card card-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="card-title">Produksi</h3>
                            <p class="card-value">{{ number_format($totalProduction, 2) }} kg</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm {{ $productionGrowth >= 0 ? 'growth-positive' : 'growth-negative' }}">
                                {{ $productionGrowth >= 0 ? '+' : '' }}{{ number_format($productionGrowth, 1) }}%
                            </p>
                            <p class="text-xs text-gray-500">dari tahun lalu</p>
                        </div>
                    </div>
                </div>

                <!-- Kesehatan Pohon Card -->
                <div class="dashboard-card card-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="card-title">Kesehatan Pohon</h3>
                            <p class="card-value">{{ $healthyTreePercentage }}%</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500">pohon sehat</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Blok -->
            <div class="dashboard-card mb-4">
                <div class="flex items-center">
                    <div class="filter-container">
                        <label for="blockFilter" class="filter-label">Filter Blok Kebun:</label>
                        <select id="blockFilter" class="filter-select">
                            <option value="">Semua Blok</option>
                            @foreach ($plantations as $plantation)
                                <option value="{{ $plantation->id }}" {{ $plantationId == $plantation->id ? 'selected' : '' }}>
                                    {{ $plantation->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- Tree Growth Chart -->
                <div class="chart-box lg:col-span-2">
                    <h3 class="chart-header">
                        <span>Jumlah Pohon</span>
                    </h3>
                    <div class="chart-container">
                        <div class="chart-wrapper">
                            <canvas id="treeGrowthChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Health Status Chart -->
                <div class="chart-box">
                    <h3 class="chart-header">
                        <span>Distribusi Kesehatan</span>
                    </h3>
                    <div class="chart-container">
                        <div class="chart-wrapper">
                            <canvas id="healthStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Varietas Distribution Chart -->
                <div class="chart-box lg:col-start-3 lg:row-start-2">
                    <h3 class="chart-header">
                        <span>Distribusi Varietas</span>
                    </h3>
                    <div class="chart-container">
                        <div class="chart-wrapper">
                            <canvas id="varietasDistributionChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Age Distribution Chart -->
                <div class="chart-box lg:col-span-2 lg:row-start-2">
                    <h3 class="chart-header">
                        <span>Distribusi Umur</span>
                    </h3>
                    <div class="chart-container">
                        <div class="chart-wrapper">
                            <canvas id="ageDistributionChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Fase Tanaman Chart (Pie Chart) -->
                <div class="chart-box lg:col-start-3 lg:row-start-3">
                     <h3 class="chart-header">Fase Tanaman</h3>
                    <div class="chart-container" style="height: 280px;">
                        <div class="chart-wrapper">
                            <canvas id="faseTanamanChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Production Chart (Line Chart) -->
                <div class="chart-box lg:col-span-2 lg:row-start-3">
                    <h3 class="chart-header">Produksi Tahunan</h3>
                    <div class="chart-container" style="height: 280px;">
                        <div class="chart-wrapper">
                            <canvas id="productivityChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Unified chart colors
    const chartColors = {
        primary: 'rgb(49, 130, 206)',     // Primary Blue
        secondary: 'rgb(113, 128, 150)',  // Gray
        success: 'rgb(56, 161, 105)',     // Green
        warning: 'rgb(221, 107, 32)',     // Orange
        danger: 'rgb(229, 62, 62)',       // Red
        accent1: 'rgb(66, 153, 225)',     // Light Blue
        accent2: 'rgb(104, 117, 245)',    // Purple
        accent3: 'rgb(154, 230, 180)',    // Light Green
    };

    const chartBackgrounds = {
        primary: 'rgba(49, 130, 206, 0.1)',
        secondary: 'rgba(113, 128, 150, 0.1)',
        success: 'rgba(56, 161, 105, 0.1)',
        warning: 'rgba(221, 107, 32, 0.1)',
        danger: 'rgba(229, 62, 62, 0.1)',
        accent1: 'rgba(66, 153, 225, 0.1)',
        accent2: 'rgba(104, 117, 245, 0.1)',
        accent3: 'rgba(154, 230, 180, 0.1)',
    };

    // Colorful chart palettes
    const colorfulPalette = [
        'rgb(66, 153, 225)',   // Blue
        'rgb(56, 161, 105)',   // Green
        'rgb(221, 107, 32)',   // Orange
        'rgb(104, 117, 245)',  // Purple
        'rgb(229, 62, 62)',    // Red
        'rgb(236, 201, 75)',   // Yellow
        'rgb(183, 148, 244)',  // Lavender
        'rgb(80, 227, 194)',   // Teal
        'rgb(245, 101, 101)',  // Light Red
        'rgb(144, 205, 244)'   // Light Blue
    ];

    // Common chart options
    Chart.defaults.font.family = 'system-ui, -apple-system, sans-serif';
    Chart.defaults.font.size = 14;  // Memperbesar ukuran font
    Chart.defaults.plugins.legend.position = 'top';
    Chart.defaults.plugins.tooltip.titleFont = { size: 15, weight: 'bold' };
    Chart.defaults.plugins.tooltip.bodyFont = { family: 'system-ui, -apple-system, sans-serif', size: 14 };
    Chart.defaults.elements.line.borderWidth = 2;
    Chart.defaults.elements.point.radius = 3;
    Chart.defaults.elements.point.hoverRadius = 5;

    // Data awal
    let originalData = {
        treeGrowth: {
            labels: {!! json_encode($treeGrowthLabels) !!},
            data: {!! json_encode($treeGrowthData) !!}
        },
        healthStatus: {
            data: {!! json_encode($healthStatusData) !!}
        },
        ageDistribution: {
            labels: {!! json_encode($ageDistributionLabels) !!},
            data: {!! json_encode($ageDistributionData) !!}
        },
        varietasDistribution: {
            labels: {!! json_encode($varietasLabels) !!},
            data: {!! json_encode($varietasData) !!}
        },
        productivity: {
            labels: {!! json_encode($productivityLabels) !!},
            data: {!! json_encode($productivityData) !!}
        },
        faseTanaman: { // Data untuk chart fase tanaman
            labels: {!! json_encode($faseTanamanLabels) !!},
            data: {!! json_encode($faseTanamanData) !!}
        }
    };

    // Variabel untuk menyimpan instance chart
    let charts = {};

    // Tree Growth Chart
    const createTreeGrowthChart = (data) => {
        const treeGrowthCtx = document.getElementById('treeGrowthChart');
        if (treeGrowthCtx) {
            if (charts.treeGrowth) {
                charts.treeGrowth.destroy();
            }

            charts.treeGrowth = new Chart(treeGrowthCtx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Jumlah Pohon',
                        data: data.data,
                        borderColor: chartColors.primary,
                        backgroundColor: chartBackgrounds.primary,
                        tension: 0.1,
                        fill: true,
                        pointBackgroundColor: chartColors.primary,
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: chartColors.primary,
                        pointHoverBorderWidth: 2,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        title: {
                            display: false
                        },
                        legend: {
                            display: false // Menghilangkan legenda
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: 'rgba(255, 255, 255, 0.2)',
                            borderWidth: 1,
                            bodyFont: {
                                size: 14
                            },
                            callbacks: {
                                label: function(context) {
                                    return `Jumlah: ${context.parsed.y} pohon`;
                                },
                                // Tambahkan lebih banyak informasi pada tooltip
                                afterLabel: function(context) {
                                    const dataset = context.dataset;
                                    const index = context.dataIndex;
                                    const prevValue = index > 0 ? dataset.data[index - 1] : null;
                                    if (prevValue !== null) {
                                        const diff = dataset.data[index] - prevValue;
                                        const sign = diff >= 0 ? '+' : '';
                                        const percentage = prevValue !== 0 ? Math.round((diff / prevValue) * 100) : 0;
                                        return `Perubahan: ${sign}${diff} pohon (${sign}${percentage}%)`;
                                    }
                                    return '';
                                }
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Jumlah Pohon',
                                font: {
                                    weight: 'bold',
                                    size: 14
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    }
                }
            });

            // Tambahkan interaksi klik pada grafik
            treeGrowthCtx.onclick = function(evt) {
                const points = charts.treeGrowth.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);

                if (points.length) {
                    const firstPoint = points[0];
                    const label = charts.treeGrowth.data.labels[firstPoint.index];
                    const value = charts.treeGrowth.data.datasets[firstPoint.datasetIndex].data[firstPoint.index];

                    // Hitung persentase perubahan jika bukan data pertama
                    let perubahanInfo = '';
                    const index = firstPoint.index;
                    const dataset = charts.treeGrowth.data.datasets[firstPoint.datasetIndex];
                    const prevValue = index > 0 ? dataset.data[index - 1] : null;

                    if (prevValue !== null) {
                        const diff = value - prevValue;
                        const sign = diff >= 0 ? '+' : '';
                        const percentage = prevValue !== 0 ? Math.round((diff / prevValue) * 100) : 0;
                        perubahanInfo = `\nPerubahan: ${sign}${diff} pohon (${sign}${percentage}%)`;
                    }

                    // Alert sementara, pada implementasi nyata bisa membuka modal dengan detail lebih lanjut
                    alert(`Detail Jumlah Pohon\nPeriode: ${label}\nJumlah: ${value} pohon${perubahanInfo}`);
                }
            };
        }
    };

    // Production Chart
    const createProductivityChart = (data) => {
        const productivityCtx = document.getElementById('productivityChart');
        if (productivityCtx) {
            if (charts.productivity) {
                charts.productivity.destroy();
            }

            charts.productivity = new Chart(productivityCtx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Produksi (kg)',
                        data: data.data,
                        borderColor: chartColors.success,
                        backgroundColor: chartBackgrounds.success,
                        tension: 0.1,
                        fill: true,
                        pointBackgroundColor: chartColors.success,
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: chartColors.success,
                        pointHoverBorderWidth: 2,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        title: {
                            display: false
                        },
                        legend: {
                            display: false // Menghilangkan legenda
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: 'rgba(255, 255, 255, 0.2)',
                            borderWidth: 1,
                            bodyFont: {
                                size: 14
                            },
                            callbacks: {
                                label: function(context) {
                                    return `Total: ${context.parsed.y.toLocaleString('id-ID')} kg`;
                                },
                                // Tambahkan lebih banyak informasi pada tooltip
                                afterLabel: function(context) {
                                    const dataset = context.dataset;
                                    const index = context.dataIndex;
                                    const prevValue = index > 0 ? dataset.data[index - 1] : null;
                                    if (prevValue !== null) {
                                        const diff = dataset.data[index] - prevValue;
                                        const sign = diff >= 0 ? '+' : '';
                                        const percentage = prevValue !== 0 ? Math.round((diff / prevValue) * 100) : 0;
                                        return `Perubahan: ${sign}${diff.toLocaleString('id-ID')} kg (${sign}${percentage}%)`;
                                    }
                                    return '';
                                }
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Produksi (kg)',
                                font: {
                                    weight: 'bold',
                                    size: 14
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    }
                }
            });

            // Tambahkan interaksi klik pada grafik
            productivityCtx.onclick = function(evt) {
                const points = charts.productivity.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);

                if (points.length) {
                    const firstPoint = points[0];
                    const label = charts.productivity.data.labels[firstPoint.index];
                    const value = charts.productivity.data.datasets[firstPoint.datasetIndex].data[firstPoint.index];

                    // Hitung persentase perubahan jika bukan data pertama
                    let perubahanInfo = '';
                    const index = firstPoint.index;
                    const dataset = charts.productivity.data.datasets[firstPoint.datasetIndex];
                    const prevValue = index > 0 ? dataset.data[index - 1] : null;

                    if (prevValue !== null) {
                        const diff = value - prevValue;
                        const sign = diff >= 0 ? '+' : '';
                        const percentage = prevValue !== 0 ? Math.round((diff / prevValue) * 100) : 0;
                        perubahanInfo = `\nPerubahan: ${sign}${diff.toLocaleString('id-ID')} kg (${sign}${percentage}%)`;
                    }

                    // Alert sementara, pada implementasi nyata bisa membuka modal dengan detail lebih lanjut
                    alert(`Detail Produksi\nPeriode: ${label}\nProduksi: ${value.toLocaleString('id-ID')} kg${perubahanInfo}`);
                }
            };
        }
    };

    // Health Status Chart
    const createHealthStatusChart = (data) => {
        const healthStatusCtx = document.getElementById('healthStatusChart');
        if (healthStatusCtx) {
            if (charts.healthStatus) {
                charts.healthStatus.destroy();
            }

            charts.healthStatus = new Chart(healthStatusCtx, {
                type: 'pie',
                data: {
                    labels: ['Sehat', 'Stres', 'Sakit', 'Mati'],
                    datasets: [{
                        data: data.data,
                        backgroundColor: [
                            colorfulPalette[1],    // Hijau untuk Sehat
                            colorfulPalette[2],    // Oranye untuk Stres
                            colorfulPalette[3],    // Ungu untuk Sakit
                            colorfulPalette[4]     // Merah untuk Mati
                        ],
                        borderColor: 'white',
                        borderWidth: 2,
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        animateRotate: true,
                        animateScale: true
                    },
                    plugins: {
                        title: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                    const percentage = Math.round((context.parsed / total) * 100);
                                    return `${context.label}: ${context.parsed} pohon (${percentage}%)`;
                                }
                            }
                        },
                        legend: {
                            position: 'right',
                            labels: {
                                font: {
                                    size: 13
                                },
                                padding: 15
                            },
                            onClick: function(e, legendItem, legend) {
                                // Custom legend behavior
                                const index = legendItem.index;
                                const ci = legend.chart;

                                // Toggle visibility
                                const meta = ci.getDatasetMeta(0);
                                const alreadyHidden = meta.data[index].hidden || false;

                                // Tampilkan semua terlebih dahulu
                                meta.data.forEach((datapoint, i) => {
                                    datapoint.hidden = false;
                                });

                                // Kemudian sembunyikan semua kecuali yang dipilih
                                if (!alreadyHidden) {
                                    meta.data.forEach((datapoint, i) => {
                                        if(i !== index) datapoint.hidden = true;
                                    });
                                }

                                ci.update();
                            }
                        }
                    }
                }
            });

            // Tambahkan interaksi klik pada grafik
            healthStatusCtx.onclick = function(evt) {
                const points = charts.healthStatus.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);

                if (points.length) {
                    const firstPoint = points[0];
                    const label = charts.healthStatus.data.labels[firstPoint.index];
                    const value = charts.healthStatus.data.datasets[firstPoint.datasetIndex].data[firstPoint.index];
                    const total = charts.healthStatus.data.datasets[firstPoint.datasetIndex].data.reduce((a, b) => a + b, 0);
                    const percentage = Math.round((value / total) * 100);

                    // Alert sementara, pada implementasi nyata bisa membuka modal dengan detail lebih lanjut
                    alert(`Detail Status Kesehatan\nStatus: ${label}\nJumlah: ${value} pohon\nPersentase: ${percentage}%`);
                }
            };
        }
    };

    // Age Distribution Chart
    const createAgeDistributionChart = (data) => {
        const ageDistributionCtx = document.getElementById('ageDistributionChart');
        if (ageDistributionCtx) {
            if (charts.ageDistribution) {
                charts.ageDistribution.destroy();
            }

            // Membuat palet warna berdasarkan usia (dari muda ke tua)
            const ageColorPalette = [
                'rgba(154, 230, 180, 0.9)',  // Hijau sangat muda untuk pohon termuda
                'rgba(104, 211, 145, 0.9)',  // Hijau muda
                'rgba(72, 187, 120, 0.9)',   // Hijau sedang
                'rgba(56, 161, 105, 0.9)',   // Hijau tua
                'rgba(39, 122, 77, 0.9)',    // Hijau sangat tua untuk pohon tertua
            ];

            // Logika untuk membuat label dinamis
            let dynamicLabels = [];
            const rawData = data.data; // Ini adalah array jumlah pohon per kategori usia
            const originalLabels = data.labels; // Ini adalah label asli dari backend

            if (rawData && rawData.length > 0) {
                // Asumsi: originalLabels[i] memberi kita informasi tentang usia untuk rawData[i]
                // Contoh: originalLabels = ["0-5 tahun", "6-10 tahun"]
                // atau jika sudah detail: originalLabels = ["1 tahun", "2 tahun", "3 tahun"]

                // Jika label asli sudah detail per tahun (misalnya, ["1 tahun", "2 tahun", ... , "5 tahun"])
                // dan jumlahnya sesuai dengan keinginan (misal max 5 tahun, ada 5 label)
                // maka kita bisa gunakan itu, atau sesuaikan.

                // Untuk contoh Anda: jika data hanya 0-5 tahun, tampilkan 1, 2, 3, 4, 5
                // Jika min-max 10 tahun, tampilkan 2, 4, 6, 8, 10

                // Mari kita coba ekstrak nilai numerik maksimum dari label yang ada
                // Ini adalah pendekatan sederhana, mungkin perlu disesuaikan berdasarkan format pasti dari $ageDistributionLabels
                let maxAge = 0;
                if (originalLabels && originalLabels.length > 0) {
                    const lastLabel = originalLabels[originalLabels.length - 1]; // e.g., "0-5 tahun" or "20+ tahun"
                    const numbersInLabel = lastLabel.match(/\d+/g); // Ekstrak angka dari string label
                    if (numbersInLabel && numbersInLabel.length > 0) {
                         // Ambil angka terakhir sebagai perkiraan maxAge, atau angka pertama jika hanya satu
                        maxAge = parseInt(numbersInLabel[numbersInLabel.length - 1]);
                    }
                }

                // Jika tidak ada data usia yang valid dari label, kita coba dari panjang data
                // Ini mengasumsikan setiap data point mewakili 1 tahun jika tidak ada label yang jelas
                if (maxAge === 0 && rawData.length > 0 && rawData.length <=5) {
                    maxAge = rawData.length;
                } else if (maxAge === 0 && rawData.length > 5) { // asumsi default jika tidak ada info
                    maxAge = originalLabels.length * 5; // Estimasi kasar jika formatnya "0-5", "6-10"
                }


                if (maxAge <= 5) {
                    for (let i = 1; i <= maxAge; i++) {
                        dynamicLabels.push(`${i} tahun`);
                    }
                     // Pastikan jumlah data sesuai dengan jumlah label baru
                    if (rawData.length < dynamicLabels.length) {
                        // Jika data lebih sedikit, kita mungkin perlu mengisi data kosong atau memotong label
                        // Untuk saat ini, kita akan cocokkan dengan panjang data asli jika lebih pendek
                        if(rawData.length < dynamicLabels.length && rawData.length > 0) {
                            dynamicLabels = dynamicLabels.slice(0, rawData.length);
                        }
                    } else if (rawData.length > dynamicLabels.length && dynamicLabels.length > 0) {
                        // Jika data lebih banyak, potong data agar sesuai label
                        // Ini mungkin bukan perilaku yang ideal, backend harus mengirim data yang sesuai
                        // rawData = rawData.slice(0, dynamicLabels.length);
                        // Sebaiknya, jika label lebih sedikit, backend harus menyesuaikan data.
                        // Atau, kita tetap gunakan originalLabels jika logikanya tidak cocok.
                        // Untuk sekarang, jika maxAge <= 5 dan ada data, kita coba sesuaikan labelnya.
                    }

                } else if (maxAge <= 10) { // Jika maxAge antara 6 dan 10
                    for (let i = 2; i <= maxAge; i += 2) {
                        dynamicLabels.push(`${i} tahun`);
                        if (i === 10 && maxAge > 10 && (maxAge - 10 < 2)) { // Handle jika ada sisa sedikit
                             dynamicLabels.push(`${maxAge} tahun`);
                        }
                    }
                     // Jika setelah loop, label terakhir belum mencapai maxAge, tambahkan maxAge
                    if (dynamicLabels.length > 0 && parseInt(dynamicLabels[dynamicLabels.length-1].match(/\d+/)[0]) < maxAge && maxAge % 2 !== 0) {
                        dynamicLabels.push(`${maxAge} tahun`);
                    }

                } else { // Untuk usia lebih dari 10 tahun, kita bisa gunakan rentang atau interval yang lebih besar
                    // Menggunakan kembali label original jika logika di atas tidak cukup
                    // atau membuat interval per 5 tahun
                    let step = 5;
                    if (maxAge > 20) step = Math.ceil(maxAge / 5); // coba buat 5 bar
                    else step = 5;

                    for (let i = step; i <= maxAge; i += step) {
                        if (i - step === 0) dynamicLabels.push(`1-${i} tahun`);
                        else dynamicLabels.push(`${(i - step) + 1}-${i} tahun`);
                    }
                    // Handle sisa jika maxAge bukan kelipatan step
                    if (maxAge % step !== 0 && dynamicLabels.length > 0) {
                         const lastValInLabel = parseInt(dynamicLabels[dynamicLabels.length-1].split('-')[1].match(/\d+/)[0]);
                         if (lastValInLabel < maxAge) {
                            dynamicLabels.push(`${lastValInLabel + 1}-${maxAge} tahun`);
                         }
                    }
                }

                // Fallback ke original labels jika dynamicLabels kosong (artinya logika di atas gagal atau tidak sesuai)
                // Atau jika jumlah data tidak cocok dengan label dinamis yang dihasilkan (kecuali kasus maxAge <=5)
                if (dynamicLabels.length === 0 || (maxAge > 5 && rawData.length !== dynamicLabels.length && originalLabels.length === rawData.length) ) {
                    dynamicLabels = originalLabels;
                }
                 // Jika maxAge <= 5, kita paksakan dynamicLabels, dan data mungkin perlu disesuaikan di backend
                 // atau kita hanya tampilkan data yang ada labelnya.
                 // Untuk skenario 0-5 tahun, dan data ada 1, label jadi ["1 tahun"]
                 if (maxAge <= 5 && dynamicLabels.length !== rawData.length && rawData.length > 0){
                    // dynamicLabels = dynamicLabels.slice(0, rawData.length); // Label disesuaikan dengan data yang ada
                    // Atau, data yang dikirim dari backend harus sudah sesuai dengan harapan label 1,2,3,4,5
                    // Jika originalLabelsnya ["0-5 tahun"] dan datanya cuma 1 (misal 200 pohon),
                    // maka chart akan menampilkan 1 bar dengan label "1 tahun" jika maxAge diset ke 1
                    // Ini berarti backend harus mengirim data yang lebih granular untuk rentang 0-5 tahun.
                    // Untuk saat ini, kita asumsikan data.labels dan data.data dari backend sudah konsisten jumlahnya.
                    // Jadi, jika kita mengubah labels, data juga harus memiliki panjang yang sama.
                    // Atau, kita modifikasi bagaimana data dipetakan ke label ini.
                    // Opsi paling aman: jika dynamicLabels tidak cocok panjangnya DENGAN DATA, KEMBALI ke originalLabels.
                    if (dynamicLabels.length !== data.data.length) {
                        dynamicLabels = originalLabels;
                    }

                }
            } else {
                dynamicLabels = originalLabels; // Gunakan label asli jika tidak ada data
            }


            charts.ageDistribution = new Chart(ageDistributionCtx, {
                type: 'bar',
                data: {
                    labels: dynamicLabels, // Menggunakan label dinamis
                    datasets: [{
                        label: 'Jumlah Pohon',
                        data: data.data, // Data tetap sama, pastikan panjangnya sesuai dengan dynamicLabels
                        backgroundColor: function(context) {
                            const index = context.dataIndex;
                            // Pilih warna berdasarkan indeks (umur)
                            return index < ageColorPalette.length ?
                                   ageColorPalette[index] :
                                   ageColorPalette[ageColorPalette.length - 1];
                        },
                        borderColor: 'rgba(255, 255, 255, 0.5)',
                        borderWidth: 1,
                        borderRadius: 4,
                        maxBarThickness: 50,
                        hoverBackgroundColor: function(context) {
                            const index = context.dataIndex;
                            const baseColor = index < ageColorPalette.length ?
                                             ageColorPalette[index] :
                                             ageColorPalette[ageColorPalette.length - 1];

                            // Buat warna hover sedikit lebih gelap
                            return baseColor.replace('0.9', '1.0');
                        }
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${context.parsed.y} pohon`;
                                },
                                afterLabel: function(context) {
                                    const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                    const percentage = Math.round((context.parsed.y / total) * 100);
                                    return `Persentase: ${percentage}% dari total`;
                                }
                            }
                        },
                        legend: {
                            display: false // Sembunyikan legenda karena warna otomatis berdasarkan usia
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Jumlah Pohon',
                                font: {
                                    weight: 'bold',
                                    size: 14
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Rentang Usia (Tahun)',
                                font: {
                                    weight: 'bold',
                                    size: 14
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    }
                }
            });

            // Tambahkan interaksi klik pada grafik
            ageDistributionCtx.onclick = function(evt) {
                const points = charts.ageDistribution.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);

                if (points.length) {
                    const firstPoint = points[0];
                    const label = charts.ageDistribution.data.labels[firstPoint.index];
                    const value = charts.ageDistribution.data.datasets[firstPoint.datasetIndex].data[firstPoint.index];
                    const total = charts.ageDistribution.data.datasets[firstPoint.datasetIndex].data.reduce((a, b) => a + b, 0);
                    const percentage = Math.round((value / total) * 100);

                    // Alert sementara, pada implementasi nyata bisa membuka modal dengan detail lebih lanjut
                    alert(`Detail Distribusi Umur\nRentang Umur: ${label}\nJumlah: ${value} pohon\nPersentase: ${percentage}% dari total`);
                }
            };
        }
    };

    // Varietas Distribution Chart
    const createVarietasDistributionChart = (data) => {
        const varietasDistributionCtx = document.getElementById('varietasDistributionChart');
        if (varietasDistributionCtx) {
            if (charts.varietasDistribution) {
                charts.varietasDistribution.destroy();
            }

            charts.varietasDistribution = new Chart(varietasDistributionCtx, {
                type: 'pie',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.data,
                        backgroundColor: colorfulPalette,
                        borderColor: 'white',
                        borderWidth: 1,
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        animateRotate: true,
                        animateScale: true
                    },
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 12,
                                font: {
                                    size: 12
                                },
                                padding: 10
                            },
                            onClick: function(e, legendItem, legend) {
                                // Custom legend behavior
                                const index = legendItem.index;
                                const ci = legend.chart;

                                // Toggle visibility
                                const meta = ci.getDatasetMeta(0);
                                const alreadyHidden = meta.data[index].hidden || false;

                                // Tampilkan semua terlebih dahulu
                                meta.data.forEach((datapoint, i) => {
                                    datapoint.hidden = false;
                                });

                                // Kemudian sembunyikan semua kecuali yang dipilih
                                if (!alreadyHidden) {
                                    meta.data.forEach((datapoint, i) => {
                                        if(i !== index) datapoint.hidden = true;
                                    });
                                }

                                ci.update();
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Tambahkan interaksi klik pada grafik
            varietasDistributionCtx.onclick = function(evt) {
                const points = charts.varietasDistribution.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);

                if (points.length) {
                    const firstPoint = points[0];
                    const label = charts.varietasDistribution.data.labels[firstPoint.index];
                    const value = charts.varietasDistribution.data.datasets[firstPoint.datasetIndex].data[firstPoint.index];
                    const total = charts.varietasDistribution.data.datasets[firstPoint.datasetIndex].data.reduce((a, b) => a + b, 0);
                    const percentage = Math.round((value / total) * 100);

                    // Alert sementara, pada implementasi nyata bisa membuka modal dengan detail lebih lanjut
                    alert(`Detail Varietas\nVarietas: ${label}\nJumlah: ${value} pohon\nPersentase: ${percentage}% dari total`);
                }
            };
        }
    };

    // Fase Tanaman Chart
    const createFaseTanamanChart = (data) => {
        const faseTanamanCtx = document.getElementById('faseTanamanChart');
        if (faseTanamanCtx) {
            if (charts.faseTanaman) {
                charts.faseTanaman.destroy();
            }

            charts.faseTanaman = new Chart(faseTanamanCtx, {
                type: 'pie',
                data: {
                    labels: data.labels, // ['Generatif', 'Vegetatif']
                    datasets: [{
                        data: data.data, // [jumlah_generatif, jumlah_vegetatif]
                        backgroundColor: [
                            'rgb(56, 161, 105)',  // Hijau Pekat (chartColors.success) untuk Generatif
                            'rgb(154, 230, 180)' // Hijau Muda (chartColors.accent3) untuk Vegetatif
                        ],
                        borderColor: 'white',
                        borderWidth: 2,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        animateRotate: true,
                        animateScale: true
                    },
                    plugins: {
                        title: {
                            display: false
                        },
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    size: 13
                                },
                                padding: 15
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                    const percentage = Math.round((context.parsed / total) * 100);
                                    return `${context.label}: ${context.parsed} pohon (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            faseTanamanCtx.onclick = function(evt) {
                const points = charts.faseTanaman.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
                if (points.length) {
                    const firstPoint = points[0];
                    const label = charts.faseTanaman.data.labels[firstPoint.index];
                    const value = charts.faseTanaman.data.datasets[firstPoint.datasetIndex].data[firstPoint.index];
                    const total = charts.faseTanaman.data.datasets[firstPoint.datasetIndex].data.reduce((a, b) => a + b, 0);
                    const percentage = Math.round((value / total) * 100);
                    alert(`Detail Fase Tanaman\nFase: ${label}\nJumlah: ${value} pohon\nPersentase: ${percentage}%`);
                }
            };
        }
    };

    // Inisialisasi semua grafik
    const initializeCharts = () => {
        createTreeGrowthChart(originalData.treeGrowth);
        createHealthStatusChart(originalData.healthStatus);
        createAgeDistributionChart(originalData.ageDistribution);
        createVarietasDistributionChart(originalData.varietasDistribution);
        createProductivityChart(originalData.productivity);
        createFaseTanamanChart(originalData.faseTanaman);
    };

    // Tambahkan event listener untuk filter
    document.getElementById('blockFilter').addEventListener('change', function() {
        const plantationId = this.value;
        // Redirect ke halaman yang sama dengan parameter plantation_id
        window.location.href = `{{ route('dashboard') }}${plantationId ? `?plantation_id=${plantationId}` : ''}`;
    });

    // Inisialisasi semua grafik
    initializeCharts();
});

function updateTimestamp() {
    const now = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const dateString = now.toLocaleDateString('id-ID', options);

    // Format jam digital dengan tampilan yang lebih menarik
    let hours = now.getHours();
    let minutes = now.getMinutes();
    let seconds = now.getSeconds();

    // Tambahkan nol di depan angka jika kurang dari 10
    hours = hours < 10 ? '0' + hours : hours;
    minutes = minutes < 10 ? '0' + minutes : minutes;
    seconds = seconds < 10 ? '0' + seconds : seconds;

    const timeString = `${hours}:${minutes}:${seconds}`;

    // Update elemen digital clock
    document.getElementById('digital-clock').innerText = timeString;
    document.getElementById('date-display').innerText = dateString;

    // Menambahkan pesan sambutan berdasarkan waktu
    const hour = now.getHours();
    let greeting = '';

    if (hour >= 3 && hour < 9) {
        greeting = 'Selamat Pagi';
    } else if (hour >= 9 && hour < 15) {
        greeting = 'Selamat Siang';
    } else if (hour >= 15 && hour < 19) {
        greeting = 'Selamat Sore';
    } else if (hour >= 19 || hour < 3) {
        greeting = 'Selamat Malam';
    }

    document.getElementById('greeting').innerText = greeting;

    // Menentukan musim berdasarkan bulan
    const month = now.getMonth(); // 0-11
    let season = '';

    if (month >= 9 && month <= 11) { // Oktober - Desember
        season = 'Musim Penghujan';
    } else if (month >= 3 && month <= 8) { // April - September
        season = 'Musim Kemarau';
    } else { // Januari - Maret
        season = 'Musim Pancaroba';
    }

    document.getElementById('season-text').innerText = season;
}

setInterval(updateTimestamp, 1000);
updateTimestamp(); // Initial call to set the timestamp immediately
</script>
@endpush
