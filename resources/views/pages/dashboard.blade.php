@extends('layouts.app')

@section('title', 'Dashboard - Symadu')

@push('styles')
<style>
    .chart-box {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
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
        color: #374151;
    }

</style>
@endpush

@section('content')
<div id="loading-screen">
    <div class="loading-spinner"></div>
</div>

<div class="w-full flex flex-col h-screen overflow-y-hidden">
    <!-- Header -->
    <header class="bg-header text-black flex items-center justify-center py-4 px-6 relative">
        <!-- Toggle Dropdown -->
        <button @click="isDropdownOpen = !isDropdownOpen" class="absolute left-6 text-3xl focus:outline-none">
            <i x-show="!isDropdownOpen" class="fas fa-bars"></i>
            <i x-show="isDropdownOpen" class="fas fa-times"></i>
        </button>
        <h1 class="text-xl font-semibold">Dashboard Kebun</h1>
        <div x-data="{ isOpen: false }" class="absolute right-6 flex justify-end">
            <button @click="isOpen = !isOpen" class="relative z-10 w-12 h-12 rounded-full overflow-hidden border-4 border-gray-400 hover:border-gray-300 focus:border-gray-300 focus:outline-none">
                <img src="{{ asset('static/profile.png') }}">
            </button>
            <button x-show="isOpen" @click="isOpen = false" class="h-full w-full fixed inset-0 cursor-default"></button>
            <div x-show="isOpen" class="account-dropdown py-2 mt-16">
                <a href="{{ route('akun.profil') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Akun</a>
                <a href="#" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Support</a>
                <div x-data="{ showModal: false }">
                    <a href="#" @click.prevent="showModal = true" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Keluar</a>

                    <!-- Modal -->
                    <div x-show="showModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
                        <div class="bg-white p-6 rounded-lg shadow-lg">
                            <h2 class="text-xl font-semibold mb-4">Konfirmasi Keluar</h2>
                            <p class="mb-4">Apakah Anda yakin ingin keluar?</p>
                            <div class="flex justify-end">
                                <button @click="showModal = false" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded mr-2">Batal</button>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                                    @csrf
                                </form>
                                <button @click="document.getElementById('logout-form').submit()" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded">Keluar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Dropdown Menu -->
    <div x-show="isDropdownOpen" class="sidebar-dropdown py-2 mt-16 absolute left-6">
        <h1 class="sidebar-header text-2xl font-bold text-center mb-4" style="color: #4aa87a;">Symadu</h1>
        <a href="{{ route('webgis') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Beranda</a>
        <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Dashboard Kebun</a>
        <a href="{{ route('pengelolaan') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Kegiatan Pengelolaan Kebun</a>

        @if(Auth::user()->role_id == 1) {{-- Superadmin --}}
            <a href="{{ route('stok') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Manajemen Stok Kebun</a>
            <a href="{{ route('akun') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Manajemen Pengguna</a>
        @elseif(Auth::user()->role_id == 2) {{-- Manajer --}}
            <a href="{{ route('stok') }}" class="block px-4 py-2 text-gray-800 account-link hover:bg-blue-500 hover:text-white">Manajemen Stok Kebun</a>
        @elseif(Auth::user()->role_id == 3) {{-- Operasional --}}
            {{-- Tidak menampilkan Manajemen Stok dan Manajemen Pengguna --}}
        @elseif(Auth::user()->role_id == 4) {{-- Guest --}}
            {{-- Sama seperti operasional, hanya bisa melihat (tambah edit dibatasi di controller) --}}
        @endif
    </div>

    <!-- Main Content -->
    <div class="w-full overflow-x-hidden border-t flex flex-col">
        <main class="w-full flex-grow p-6">
            <!-- Timestamp Card -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-4">
                <h3 class="text-lg font-semibold text-gray-700">Waktu dan Tanggal</h3>
                <p id="timestamp" class="text-xl font-bold text-gray-800"></p>
            </div>
            <!-- Scoreboard Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <!-- Total Pohon Card -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700">Total Pohon</h3>
                            <p class="text-3xl font-bold text-gray-800">{{ $totalTrees }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm {{ $treeGrowthRate >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $treeGrowthRate >= 0 ? '+' : '' }}{{ $treeGrowthRate }}%
                            </p>
                            <p class="text-xs text-gray-500">dari tahun lalu</p>
                        </div>
                    </div>
                </div>

                <!-- Total Area Card -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700">Total Area</h3>
                            <p class="text-3xl font-bold text-gray-800">{{ number_format($totalArea, 2) }} ha</p>
                        </div>
                    </div>
                </div>

                <!-- Produktivitas Card -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700">Produksi</h3>
                            <p class="text-3xl font-bold text-gray-800">{{ number_format($totalProduction, 2) }} kg</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm {{ $productionGrowth >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $productionGrowth >= 0 ? '+' : '' }}{{ $productionGrowth }}%
                            </p>
                            <p class="text-xs text-gray-500">dari tahun lalu</p>
                        </div>
                    </div>
                </div>

                <!-- Kesehatan Pohon Card -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700">Kesehatan Pohon</h3>
                            <p class="text-3xl font-bold text-gray-800">{{ $healthyTreePercentage }}%</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500">pohon sehat</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Tree Growth Chart -->
                <div class="chart-box">
                    <h3 class="chart-header">Pertumbuhan Pohon</h3>
                    <div class="chart-container">
                        <div class="chart-wrapper">
                            <canvas id="treeGrowthChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Production Chart -->
                <div class="chart-box">
                    <h3 class="chart-header">Produksi Bulanan</h3>
                    <div class="chart-container">
                        <div class="chart-wrapper">
                            <canvas id="productivityChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Health Status Chart -->
                <div class="chart-box">
                    <h3 class="chart-header">Status Kesehatan</h3>
                    <div class="chart-container">
                        <div class="chart-wrapper">
                            <canvas id="healthStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Age Distribution Chart -->
                <div class="chart-box">
                    <h3 class="chart-header">Distribusi Umur</h3>
                    <div class="chart-container">
                        <div class="chart-wrapper">
                            <canvas id="ageDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Environmental Data Charts -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="chart-box">
                    <h3 class="chart-header">Curah Hujan</h3>
                    <div class="chart-container">
                        <div class="chart-wrapper">
                            <canvas id="rainfallChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="chart-box">
                    <h3 class="chart-header">Suhu</h3>
                    <div class="chart-container">
                        <div class="chart-wrapper">
                            <canvas id="temperatureChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="chart-box">
                    <h3 class="chart-header">Kelembaban</h3>
                    <div class="chart-container">
                        <div class="chart-wrapper">
                            <canvas id="humidityChart"></canvas>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
        // Common chart options
    Chart.defaults.font.family = 'Arial, sans-serif';
    Chart.defaults.font.size = 12;
    Chart.defaults.plugins.legend.position = 'top';

    // Tree Growth Chart
    const treeGrowthCtx = document.getElementById('treeGrowthChart');
    if (treeGrowthCtx) {
        new Chart(treeGrowthCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($treeGrowthLabels) !!},
                datasets: [{
                    label: 'Jumlah Pohon',
                    data: {!! json_encode($treeGrowthData) !!},
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Jumlah Pohon'
                        }
                    }
                }
            }
        });
    }

    // Production Chart
    const productivityCtx = document.getElementById('productivityChart');
    if (productivityCtx) {
        new Chart(productivityCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($productivityLabels) !!},
                datasets: [{
                    label: 'Produksi (kg)',
                    data: {!! json_encode($productivityData) !!},
                    backgroundColor: 'rgba(54, 162, 235, 0.5)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Produksi (kg)'
                        }
                    }
                }
            }
        });
    }

    // Health Status Chart
    const healthStatusCtx = document.getElementById('healthStatusChart');
    if (healthStatusCtx) {
        new Chart(healthStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Sehat', 'Stres', 'Terinfeksi', 'Mati'],
                datasets: [{
                    data: {!! json_encode($healthStatusData) !!},
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(169, 169, 169, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: false
                    }
                }
            }
        });
    }

    // Age Distribution Chart
    const ageDistributionCtx = document.getElementById('ageDistributionChart');
    if (ageDistributionCtx) {
        new Chart(ageDistributionCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($ageDistributionLabels) !!},
                datasets: [{
                    label: 'Jumlah Pohon',
                    data: {!! json_encode($ageDistributionData) !!},
                    backgroundColor: 'rgba(153, 102, 255, 0.5)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Jumlah Pohon'
                        }
                    }
                }
            }
        });
    }

    // Environmental Charts
    const createEnvironmentalChart = (ctx, label, data, yAxisLabel, color) => {
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($environmentalData['years']) !!},
                    datasets: [{
                        label: label,
                        data: data,
                        borderColor: color,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: yAxisLabel
                            }
                        }
                    }
                }
            });
        }
    };

    const environmentalData = {!! json_encode($environmentalData) !!};

    createEnvironmentalChart(
        document.getElementById('rainfallChart'),
        'Curah Hujan',
        environmentalData.rainfall,
        'Curah Hujan (mm)',
        'rgb(54, 162, 235)'
    );

    createEnvironmentalChart(
        document.getElementById('temperatureChart'),
        'Suhu',
        environmentalData.temperature,
        'Suhu (°C)',
        'rgb(255, 99, 132)'
    );

    createEnvironmentalChart(
        document.getElementById('humidityChart'),
        'Kelembaban',
        environmentalData.humidity,
        'Kelembaban (%)',
        'rgb(75, 192, 192)'
    );
});

function updateTimestamp() {
    const now = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const dateString = now.toLocaleDateString('id-ID', options);
    const timeString = now.toLocaleTimeString('id-ID');
    document.getElementById('timestamp').innerText = `${timeString} - ${dateString}`;
}

setInterval(updateTimestamp, 1000);
updateTimestamp(); // Initial call to set the timestamp immediately
</script>
@endpush
