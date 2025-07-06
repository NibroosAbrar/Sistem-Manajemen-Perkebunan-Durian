<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Tailwind -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ url ('assets/css/style.css') }}">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link rel="stylesheet" href="{{ url ('static/L.Control.Sidebar.css') }}">
    <link rel="stylesheet" href="{{ url('static/L.Control.MousePosition.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-easybutton@2/src/easy-button.css">
    <link rel="stylesheet" href='https://cdn.jsdelivr.net/npm/@geoman-io/leaflet-geoman-free@latest/dist/leaflet-geoman.css'/>

    <!-- AlpineJS -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <style>
        [x-cloak] { display: none !important; }
        .modal-backdrop {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 99999;
        }
        .modal-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 100000;
            width: 100%;
            max-width: 450px;
        }
        /* Perbaikan untuk sidebar dan overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
            z-index: 900; /* Di bawah sidebar tetapi di atas map */
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .sidebar-menu {
            z-index: 901 !important; /* Pastikan di atas overlay tapi di bawah leaflet controls */
        }
        .leaflet-control-container,
        .leaflet-top,
        .leaflet-bottom,
        .leaflet-control-sidebar,
        .leaflet-popup {
            z-index: 950 !important;
        }
        /* Perbaikan untuk WebGIS di mobile */
        @media (max-width: 768px) {
            /* Perbaikan header untuk halaman webgis */
            body.webgis-page .bg-header {
                height: 60px;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
            }

            /* Atur konten agar tidak tertutup header di halaman webgis */
            body.webgis-page #content {
                padding-top: 60px;
            }
        }
        /* Warna emerald manual jika tidak tersedia di CDN */
        .bg-emerald-50 { background-color: #ecfdf5; }
        .bg-emerald-100 { background-color: #d1fae5; }
        .bg-emerald-200 { background-color: #a7f3d0; }
        .bg-emerald-300 { background-color: #6ee7b7; }
        .bg-emerald-400 { background-color: #34d399; }
        .bg-emerald-500 { background-color: #10b981; }
        .bg-emerald-600 { background-color: #059669; }
        .bg-emerald-700 { background-color: #047857; }
        .bg-emerald-800 { background-color: #065f46; }
        .bg-emerald-900 { background-color: #064e3b; }

        .text-emerald-50 { color: #ecfdf5; }
        .text-emerald-100 { color: #d1fae5; }
        .text-emerald-200 { color: #a7f3d0; }
        .text-emerald-300 { color: #6ee7b7; }
        .text-emerald-400 { color: #34d399; }
        .text-emerald-500 { color: #10b981; }
        .text-emerald-600 { color: #059669; }
        .text-emerald-700 { color: #047857; }
        .text-emerald-800 { color: #065f46; }
        .text-emerald-900 { color: #064e3b; }

        /* Hover states */
        .hover\:bg-emerald-50:hover { background-color: #ecfdf5; }
        .hover\:bg-emerald-100:hover { background-color: #d1fae5; }
        .hover\:bg-emerald-200:hover { background-color: #a7f3d0; }
        .hover\:bg-emerald-300:hover { background-color: #6ee7b7; }
        .hover\:bg-emerald-400:hover { background-color: #34d399; }
        .hover\:bg-emerald-500:hover { background-color: #10b981; }
        .hover\:bg-emerald-600:hover { background-color: #059669; }
        .hover\:bg-emerald-700:hover { background-color: #047857; }
        .hover\:bg-emerald-800:hover { background-color: #065f46; }
        .hover\:bg-emerald-900:hover { background-color: #064e3b; }

        /* Style untuk header saat modal terbuka */
        body.modal-open header.bg-header {
            filter: grayscale(100%);
            opacity: 0.3;
            pointer-events: none;
            z-index: 10 !important;
        }
        
        /* Style untuk modal */
        .modal-overlay {
            z-index: 1000;
        }
        
        .modal-container {
            z-index: 1001;
        }
    </style>

    @stack('styles')
</head>
<body class="bg-gray-100 font-family-karla"
      x-data="{
          isSidebarOpen: false,
          isProfileOpen: false,
          showLogoutModal: false,
          toggleSidebar() {
              this.isSidebarOpen = !this.isSidebarOpen;
              // Aktifkan overlay jika sidebar terbuka
              const overlay = document.querySelector('.sidebar-overlay');
              if (overlay) {
                  if (this.isSidebarOpen) {
                      overlay.classList.add('active');
                  } else {
                      overlay.classList.remove('active');
                  }
              }
          }
      }"
      onload="hideLoadingScreen()">

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" @click="toggleSidebar()"></div>

    <!-- Sidebar Menu -->
    <nav class="sidebar-menu" :class="{ 'active': isSidebarOpen }">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <div class="w-8"><!-- Spacer untuk balancing --></div>
                <span class="text-emerald-500 font-bold text-2xl mx-auto">Symadu</span>
                <button class="close-btn text-gray-500 hover:text-gray-700 focus:outline-none transition-colors duration-200" @click="toggleSidebar()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="menu-items">
            <a href="{{ route('dashboard') }}" class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard Kebun</span>
            </a>
            <a href="{{ route('webgis') }}" class="menu-item {{ request()->routeIs('webgis') ? 'active' : '' }}">
                <i class="fas fa-map-marked-alt"></i>
                <span>Peta</span>
            </a>
            <a href="{{ route('pengelolaan') }}" class="menu-item {{ request()->routeIs('pengelolaan') ? 'active' : '' }}">
                <i class="fas fa-tasks"></i>
                <span>Kegiatan Pengelolaan</span>
            </a>

            @if(Auth::check() && Auth::user()->role_id == 1)
                <a href="{{ route('stok') }}" class="menu-item {{ request()->routeIs('stok') ? 'active' : '' }}">
                    <i class="fas fa-box"></i>
                    <span>Manajemen Stok</span>
                </a>
                <a href="{{ route('akun') }}" class="menu-item {{ request()->routeIs('akun') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>Manajemen Pengguna</span>
                </a>
            @elseif(Auth::check() && Auth::user()->role_id == 2)
                <a href="{{ route('stok') }}" class="menu-item {{ request()->routeIs('stok') ? 'active' : '' }}">
                    <i class="fas fa-box"></i>
                    <span>Manajemen Stok</span>
                </a>
            @endif
        </div>

        <!-- Footer sidebar -->
        <div class="sidebar-footer">
            <p>&copy; Muhammad Nibroos Abrar 2025</p>
        </div>
    </nav>

    <!-- Header -->
    <header class="bg-header text-black flex items-center justify-between py-4 px-6 relative w-full z-50" x-bind:class="{'z-10': document.body.classList.contains('modal-open')}">
        <div class="relative flex items-center">
            <button @click="toggleSidebar()" class="hamburger-button w-10 h-10 flex items-center justify-center rounded-full bg-white border-2 border-emerald-500 text-gray-900 hover:bg-emerald-50 focus:outline-none transition-all duration-200 shadow-sm hover:shadow-md">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <h1 class="text-xl font-semibold">@yield('header-title')</h1>

        <!-- Profile Dropdown -->
        @if(Auth::check())
        <div class="relative flex items-center profile-dropdown-container">
            <button @click="isProfileOpen = !isProfileOpen"
                    class="profile-button relative z-10 w-10 h-10 rounded-full overflow-hidden border-2 border-emerald-500 hover:border-emerald-600 focus:border-emerald-600 focus:outline-none transition-all duration-200 shadow-sm hover:shadow-md">
                <img src="{{ asset('static/profile.png') }}" alt="Profile" class="w-full h-full object-cover">
            </button>

            <!-- Dropdown Menu -->
            <div x-show="isProfileOpen"
                 @click.away="isProfileOpen = false"
                 class="profile-dropdown absolute top-full right-0 mt-3 py-2 w-48 bg-white rounded-md shadow-xl z-[1000] origin-top-right">
                <a href="{{ route('akun.profil') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-green-100">
                    Profil
                </a>
                <a href="{{ route('bantuan') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-green-100">
                    Bantuan
                </a>
                <button @click="showLogoutModal = true; isProfileOpen = false"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-green-100">
                    Keluar
                </button>
            </div>
        </div>
        @else
        <div class="relative flex items-center">
            <a href="{{ route('login') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white py-2 px-4 rounded-lg shadow-sm transition-all duration-200">
                Login
            </a>
        </div>
        @endif
    </header>

    <!-- Modal Konfirmasi Logout - Disederhanakan & Z-index Lebih Tinggi -->
    @if(Auth::check())
    <template x-if="showLogoutModal">
        <div x-cloak class="fixed inset-0 overflow-y-auto flex items-center justify-center" style="z-index: 999999;">
            <!-- Modal Backdrop -->
            <div class="modal-backdrop fixed inset-0 bg-black bg-opacity-50" @click="showLogoutModal = false" style="z-index: 999999;"></div>

            <!-- Modal Content -->
            <div class="modal-container bg-white rounded-lg shadow-xl overflow-hidden relative" style="z-index: 1000000;">
                <div class="p-5 flex items-start space-x-4">
                    <div class="flex-shrink-0 bg-red-100 rounded-full p-2">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Konfirmasi Keluar</h3>
                        <p class="mt-2 text-gray-600">Apakah Anda yakin ingin keluar dari aplikasi?</p>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3 flex justify-end space-x-3">
                    <button @click="showLogoutModal = false"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded font-medium">
                        Batal
                    </button>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit"
                                class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded font-medium">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </template>
    @endif

    <!-- Content Wrapper -->
    <div id="content" class="w-full">
        @yield('content')
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="{{ url('static/L.Control.Sidebar.js') }}"></script>
    <script src="{{ url('static/L.Control.MousePosition.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet-easybutton@2/src/easy-button.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/js/all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.14.1/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@geoman-io/leaflet-geoman-free@latest/dist/leaflet-geoman.min.js"></script>
    <script src="{{ url('static/leaflet.ajax.js') }}"></script>
    <!-- Tambahkan Turf.js untuk perhitungan area -->
    <script src="https://unpkg.com/@turf/turf@6/turf.min.js"></script>
    <script src="{{ url('assets/js/scripts.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

    @stack('scripts')
</body>
</html>
