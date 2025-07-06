function hideLoadingScreen() {
    document.getElementById('loading-screen').style.display = 'none';
}

// Pastikan loading screen selalu dihapus saat halaman dimuat
window.addEventListener('DOMContentLoaded', function() {
    hideLoadingScreen();
});

// Deklarasi variabel global
var baseLayers;
var overlays;

// Initialize the Leaflet map
var satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 23,
    attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
});

// Tambahkan layer Label (Nama Tempat)
var labels = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 23,
    attribution: 'Tiles &copy; Esri &mdash; Source: Esri'
});

// Function to get URL parameters
function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

// Gabungkan kedua layer
var map = L.map('map', {
    attributionControl: false,
    center: [-7.073780, 106.773950],
    zoom: 18,
    layers: [satellite, labels], // Gabungkan layer satelit dan label
    zoomControl: false
});

// Buat pane khusus untuk layer plantation (dengan z-index rendah)
map.createPane('plantationsPane');
map.getPane('plantationsPane').style.zIndex = 400;

// Buat pane khusus untuk layer pohon (dengan z-index lebih tinggi)
map.createPane('treesPane');
map.getPane('treesPane').style.zIndex = 650;

// Only add welcome marker if no tree ID in URL
var marker;
if (!getUrlParameter('id')) {
    // Add a marker
    marker = L.marker([-7.073780, 106.773950], 23).addTo(map); // Koordinat untuk Bogor
    marker.bindPopup('<b>Welcome to Symadu</b>').openPopup();

    // Atur posisi peta agar fokus pada marker
    map.setView([-7.073780, 106.773950], 23); // Zoom level 13

    // Hapus marker dengan efek fade-out setelah 5 detik
    setTimeout(function() {
        // Dapatkan elemen DOM marker
        var markerElement = marker.getElement();

        // Tambahkan kelas CSS untuk efek fade-out
        markerElement.classList.add('leaflet-marker-fade');

        // Tunggu 1 detik (sesuai durasi animasi fade-out), lalu hapus marker dari peta
        setTimeout(function() {
            map.removeLayer(marker);
        }, 1000);
    }, 5000);
}

// Trigger map ready event
document.dispatchEvent(new Event('map:ready'));

// Coba inisialisasi Leaflet-Geoman setelah peta siap
document.addEventListener('map:ready', function() {
    console.log('Map is ready, initializing Geoman...');

    // Periksa apakah Geoman sudah diinisialisasi
    if (map && map.pm && map._hasGeomanCreateHandler) {
        console.log('Geoman is already initialized, skipping initialization in scripts.js');
        return;
    }

    // Jika belum diinisialisasi, coba inisialisasi langsung
    console.log('Geoman not initialized yet, trying to initialize directly...');

    // Coba inisialisasi langsung tanpa delay
    if (typeof L !== 'undefined' && typeof L.PM !== 'undefined') {
        initGeomanDirectly();
    } else {
        // Jika Geoman belum dimuat, muat secara manual
        loadGeomanManually();
    }
});

// Tambahkan event listener untuk geoman:ready
document.addEventListener('geoman:ready', function() {
    console.log('Geoman is ready, skipping initialization in scripts.js');
    // Tidak perlu melakukan apa-apa karena Geoman sudah diinisialisasi
});

var GoogleStreets = L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
    maxZoom: 23,
    subdomains:['mt0','mt1','mt2','mt3']
});

var googleHybrid = L.tileLayer('http://mt0.google.com/vt/lyrs=y&hl=en&x={x}&y={y}&z={z}', {
    maxZoom: 23
});

// Tambahkan scale bar
L.control.scale({
    position: 'bottomleft', // Posisi scale bar
    metric: true, // Gunakan metrik (meter/kilometer)
    imperial: true // Nonaktifkan imperial (feet/mile)
}).addTo(map);

// Variabel untuk menyimpan status sidebar yang aktif
let activeSidebar = null;
let sidebarcamera, sidebarinfo;

// Inisialisasi sidebar setelah DOM selesai dimuat
document.addEventListener('DOMContentLoaded', function() {
    // Pastikan plugin L.control.sidebar tersedia
    if (L.control && L.control.sidebar) {
// SIDEBAR
        sidebarcamera = L.control.sidebar('camera', {
    position: 'left',
    autopan: false // Nonaktifkan pemindahan peta saat sidebar terbuka
}).addTo(map);

        sidebarinfo = L.control.sidebar('info', {
    position: 'left',
    autopan: false // Nonaktifkan pemindahan peta saat sidebar terbuka
}).addTo(map);

        console.log('Sidebar controls initialized');

// EasyButton untuk Info
L.easyButton('fa-info-circle', function () {
    if (sidebarcamera.isVisible()) {
        sidebarcamera.hide(); // Tutup sidebar camera jika sedang terbuka
    }
    sidebarinfo.toggle(); // Tampilkan atau sembunyikan sidebar info
}).addTo(map);

// EasyButton untuk camera
L.easyButton('fa-camera', function () {
    if (sidebarinfo.isVisible()) {
        sidebarinfo.hide(); // Tutup sidebar info jika sedang terbuka
    }
    sidebarcamera.toggle(); // Tampilkan atau sembunyikan sidebar camera

    // Trigger event camera:opened saat sidebar camera dibuka
    if (sidebarcamera.isVisible()) {
        document.dispatchEvent(new Event('camera:opened'));
    }
}).addTo(map);

// Tambahkan event listener untuk sidebar camera
sidebarcamera.on('shown', function() {
    document.dispatchEvent(new Event('camera:opened'));
});

// Mouse Position
L.control.mousePosition({position:'bottomright'}).addTo(map);

// Home button to return to default view
L.easyButton('fa-home', function() {
    // Remove any existing markers first
    if (marker) {
        map.removeLayer(marker);
    }

    // Add welcome marker
    marker = L.marker([-7.073780, 106.773950], 23).addTo(map);
    marker.bindPopup('<b>Welcome to Symadu</b>').openPopup();

    // Set view to default position
    map.setView([-7.073780, 106.773950], 23);

    // Remove marker after 5 seconds with fade effect
    setTimeout(function() {
        var markerElement = marker.getElement();
        markerElement.classList.add('leaflet-marker-fade');
        setTimeout(function() {
            map.removeLayer(marker);
        }, 1000);
    }, 5000);
}, 'Kembali ke Posisi Awal').addTo(map);
    } else {
        console.error('L.control.sidebar plugin not loaded properly');
    }
});

// Fungsi untuk mengatur buka/tutup sidebar
function toggleSidebar(sidebarToToggle, otherSidebar) {
    // Jika sidebar yang akan dibuka adalah yang aktif, maka tutup
    if (activeSidebar === sidebarToToggle) {
        sidebarToToggle.hide();
        activeSidebar = null; // Tidak ada sidebar aktif
    } else {
        // Tutup sidebar lainnya jika terbuka
        if (activeSidebar) {
            activeSidebar.hide();
        }
        // Buka sidebar yang diminta
        sidebarToToggle.show();
        activeSidebar = sidebarToToggle; // Atur sidebar ini sebagai aktif
    }
}

// Function untuk membuka modal foto dari mana saja
window.openPhotoModal = function() {
    // Dapatkan Alpine.js instance
    const alpine = document.querySelector('[x-data]').__x.$data;
    alpine.showPhotoModal = true;
};

// Function untuk menutup modal foto
window.closePhotoModal = function() {
    const alpine = document.querySelector('[x-data]').__x.$data;
    alpine.showPhotoModal = false;
};

// Initialize drawnItems FeatureGroup for the shapes
var drawnItems = new L.FeatureGroup([], {
    pane: 'treesPane' // Gunakan pane khusus untuk pohon
});
map.addLayer(drawnItems);

// Initialize plantationLayers FeatureGroup for plantation shapes
var plantationLayers = new L.FeatureGroup();
map.addLayer(plantationLayers);

// Variable to track deletion in progress
let isDeleting = false;

// Variable to track Geoman initialization status
let isGeomanInitialized = false;

// Perbaikan fungsi initGeomanControls
function initGeomanControls() {
    console.log('Initializing Geoman controls...');

    // Dapatkan role pengguna
    const userRole = window.userRole || '';

    // Jika user adalah guest atau operasional, jangan tampilkan kontrol Geoman
    if (userRole === 'Guest' || userRole === 'Operasional') {
        console.log('User role is Guest or Operational, skipping Geoman controls');
        return;
    }

    // Pastikan map dan L.PM tersedia
    if (!map || !L.PM) {
        console.error('Map or Geoman not available');
        return;
    }

    try {
        // Inisialisasi Geoman toolbar
        map.pm.addControls({
            position: 'topleft',
            drawCircle: false,
            drawCircleMarker: false,
            drawPolyline: false,
            drawRectangle: false,
            drawText: false,
            cutPolygon: false,
            dragMode: false,
            rotateMode: false,
            editMode: true,
            drawPolygon: true,
            deleteLayer: userRole !== 'Operasional' // Nonaktifkan delete untuk Operasional
        });

        // Tambahkan event handler untuk pembuatan shape
        addPmCreateHandler();

        console.log('Geoman controls initialized successfully');
    } catch (error) {
        console.error('Error initializing Geoman controls:', error);
    }
}

// Fungsi untuk mencoba inisialisasi Geoman dengan interval
function tryInitGeoman() {
    console.log('DOM loaded, trying to initialize Leaflet-Geoman');

    let attempts = 0;
    const maxAttempts = 10;

    function attemptInit() {
        attempts++;

        if (initGeomanControls()) {
            console.log('Geoman initialized successfully on attempt ' + attempts);
            return true;
        } else if (attempts < maxAttempts) {
            console.log('Trying again after a delay...');
            setTimeout(attemptInit, 1000); // Tunggu 1 detik sebelum mencoba lagi
            return false;
        } else {
            console.error('Failed to initialize Leaflet-Geoman after multiple attempts');
            // Coba cara alternatif - muat Geoman secara manual
            loadGeomanManually();
            return false;
        }
    }

    // Mulai percobaan inisialisasi
    return attemptInit();
}

// Fungsi untuk memuat Geoman secara manual
function loadGeomanManually() {
    console.log('Loading Geoman manually...');

    // Periksa apakah Geoman sudah diinisialisasi
    if (isGeomanInitialized) {
        console.log('Geoman is already initialized, skipping manual loading');
        return;
    }

    // Periksa apakah Geoman sudah dimuat
    if (typeof L !== 'undefined' && typeof L.PM !== 'undefined') {
        console.log('Geoman sudah dimuat, langsung inisialisasi...');
        setTimeout(function() {
            if (initGeomanControls()) {
                console.log('Geoman initialized successfully');
                isGeomanInitialized = true;
                // Dispatch event untuk memberi tahu bahwa Geoman sudah siap
                document.dispatchEvent(new Event('geoman:ready'));
            }
        }, 100);
        return;
    }

    // Hapus script Geoman yang mungkin sudah ada tapi bermasalah
    const existingScripts = document.querySelectorAll('script[src*="geoman"]');
    existingScripts.forEach(script => script.remove());

    // Hapus stylesheet Geoman yang mungkin sudah ada tapi bermasalah
    const existingStyles = document.querySelectorAll('link[href*="geoman"]');
    existingStyles.forEach(style => style.remove());

    // Gunakan URL yang sudah terbukti berfungsi
    const geomanCssUrl = 'https://cdn.jsdelivr.net/npm/@geoman-io/leaflet-geoman-free@latest/dist/leaflet-geoman.css';
    const geomanJsUrl = 'https://cdn.jsdelivr.net/npm/@geoman-io/leaflet-geoman-free@latest/dist/leaflet-geoman.min.js';

    // Tambahkan CSS Geoman
    console.log('Memuat CSS Geoman dari:', geomanCssUrl);
    const geomanCSS = document.createElement('link');
    geomanCSS.rel = 'stylesheet';
    geomanCSS.href = geomanCssUrl;

    // Tangani error loading CSS
    geomanCSS.onerror = function() {
        console.error('Gagal memuat CSS Geoman, menggunakan CSS inline...');
        createInlineGeomanCss();
    };

    document.head.appendChild(geomanCSS);

    // Tambahkan JS Geoman
    console.log('Memuat JS Geoman dari:', geomanJsUrl);
    const geomanScript = document.createElement('script');
    geomanScript.src = geomanJsUrl;

    // Tangani error loading JS
    geomanScript.onerror = function() {
        console.error('Gagal memuat JS Geoman, mencoba alternatif...');
        // Coba URL alternatif
        const alternativeUrl = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.pm/2.2.0/leaflet.pm.min.js';
        console.log('Mencoba URL alternatif:', alternativeUrl);
        geomanScript.src = alternativeUrl;
    };

    geomanScript.onload = function() {
        console.log('JS Geoman berhasil dimuat');

        // Tunggu sebentar untuk memastikan Geoman benar-benar siap
        setTimeout(function() {
            if (initGeomanControls()) {
                console.log('Geoman initialized successfully after manual loading');
                isGeomanInitialized = true;
                // Dispatch event untuk memberi tahu bahwa Geoman sudah siap
                document.dispatchEvent(new Event('geoman:ready'));
            } else {
                console.error('Failed to initialize Geoman even after manual loading');
            }
        }, 500);
    };

    document.body.appendChild(geomanScript);

    // Fungsi untuk membuat CSS inline sebagai fallback terakhir
    function createInlineGeomanCss() {
        console.log('Membuat CSS Geoman inline sebagai fallback');
        const style = document.createElement('style');
        style.textContent = `
            .leaflet-pm-toolbar {
                display: flex;
                flex-direction: column;
            }
            .leaflet-pm-toolbar > div {
                margin-bottom: 5px !important;
            }
            .leaflet-pm-toolbar .button-container {
                margin-right: 0 !important;
            }
            .leaflet-pm-actions-container {
                flex-direction: column;
                left: 40px !important;
                top: 0 !important;
            }
            .leaflet-pm-actions-container button {
                margin-bottom: 2px;
            }
        `;
        document.head.appendChild(style);
    }
}

// Tambahkan event listener untuk memulai inisialisasi Geoman setelah DOM dimuat
document.addEventListener('DOMContentLoaded', function() {
    // Tunggu sebentar untuk memastikan peta sudah dimuat
    setTimeout(function() {
        tryInitGeoman();
    }, 1000);
});

// Tambahkan event listener untuk map:ready jika ada
document.addEventListener('map:ready', function() {
    console.log('map:ready event received, initializing Geoman...');
    initGeomanDirectly();
});

// Fungsi untuk menginisialisasi Geoman langsung
function initGeomanDirectly() {
    console.log('Initializing Geoman directly...');

    // Periksa apakah Geoman sudah diinisialisasi
    if (isGeomanInitialized) {
        console.log('Geoman is already initialized, skipping initialization');
        return true;
    }

    // Pastikan Leaflet dan map sudah didefinisikan
    if (typeof L === 'undefined' || typeof map === 'undefined' || !map) {
        console.error('Leaflet or map is not defined yet');
        // Kurangi waktu tunggu untuk percobaan berikutnya
        setTimeout(initGeomanDirectly, 200);
        return false;
    }

    // Periksa apakah Geoman sudah dimuat
    if (typeof L.PM === 'undefined') {
        console.warn('Leaflet-Geoman plugin not ready yet, loading manually...');
        loadGeomanManually();
        return false;
    }

    // Pastikan map.pm tersedia
    if (!map.pm) {
        console.log('map.pm is not defined, creating it...');
        try {
            map.pm = new L.PM.Map(map);
        } catch (error) {
            console.error('Error creating map.pm:', error);
            // Coba lagi setelah jeda singkat
            setTimeout(initGeomanDirectly, 200);
            return false;
        }
    }

    try {
        // Tambahkan CSS untuk memperbaiki tampilan tombol Geoman
        const styleId = 'geoman-custom-style';
        if (!document.getElementById(styleId)) {
            const style = document.createElement('style');
            style.id = styleId;
            style.textContent = `
                .leaflet-pm-toolbar {
                    display: flex;
                    flex-direction: column;
                }
                .leaflet-pm-toolbar > div {
                    margin-bottom: 5px !important;
                }
                .leaflet-pm-toolbar .button-container {
                    margin-right: 0 !important;
                }
                .leaflet-pm-actions-container {
                    flex-direction: column;
                    left: 40px !important;
                    top: 0 !important;
                }
                .leaflet-pm-actions-container button {
                    margin-bottom: 2px;
                }
            `;
            document.head.appendChild(style);
        }

        // Reset kontrol Geoman jika sudah ada
        if (map.pm.controlsVisible()) {
            map.pm.removeControls();
        }

        // Tambahkan kontrol Geoman
        map.pm.addControls({
            position: 'topleft',
            drawCircle: true,
            drawCircleMarker: false,
            drawRectangle: true,
            drawPolyline: true,
            drawPolygon: true,
            drawMarker: true,
            cutPolygon: true,
            editMode: true,
            dragMode: true,
            removalMode: true
        });

        console.log('Geoman controls added successfully');

        // Pastikan hanya ada satu handler untuk pm:create
        // Hapus event handler sebelumnya jika ada
        if (pmCreateHandlerAdded) {
            console.log('Removing existing pm:create handler');
            map.off('pm:create');
            pmCreateHandlerAdded = false;
        }

        // Tambahkan event handler untuk pm:create
        addPmCreateHandler();

        // Set status inisialisasi Geoman
        isGeomanInitialized = true;

        // Dispatch event untuk memberi tahu bahwa Geoman sudah siap
        document.dispatchEvent(new Event('geoman:ready'));

        return true;
    } catch (error) {
        console.error('Error initializing Geoman controls directly:', error);
        return false;
    }
}

// Variabel untuk menyimpan status edit
let isEditMode = false;
let currentTab = 1;

// Variabel untuk melacak apakah event handler sudah ditambahkan
let pmCreateHandlerAdded = false;
let modalEventHandlersAdded = false; // Tambahkan penanda untuk event handler modal

// Variabel untuk menyimpan data shape yang baru dibuat
let currentShapeData = null; // Ubah menjadi null, bukan object yang sudah diinisialisasi
// Variabel untuk melacak tipe objek yang sedang dibuat (pohon atau blok kebun)
let currentShapeType = null; // Nilai: 'tree' atau 'plantation'

// Function to add pm:create event handler
function addPmCreateHandler() {
    if (pmCreateHandlerAdded) {
        console.log('pm:create event handler already added, skipping');
        return;
    }

    // Dapatkan role pengguna dari data yang disimpan di window
    const userRole = window.userRole || '';

    // Hanya tambahkan event handler jika bukan Guest
    if (userRole !== 'Guest') {
        console.log('Adding pm:create event handler');

        // Event listener untuk pembuatan objek (polygon, circle, marker)
        map.on('pm:create', function(e) {
            console.log('pm:create event fired', e);

            // Tutup semua modal yang mungkin terbuka
            window.dispatchEvent(new CustomEvent('close-form-selector-modal'));
            window.dispatchEvent(new CustomEvent('close-tree-modal'));
            window.dispatchEvent(new CustomEvent('close-plantation-modal'));

            // Penting: JANGAN langsung tambahkan ke drawnItems
            // Layer akan ditambahkan ke layer yang sesuai saat pengguna memilih jenis form (pohon/blok kebun)

            // Tandai layer sebagai belum disimpan
            e.layer.isSaved = false;

            // Dapatkan tipe layer yang dibuat
            const shape = e.shape;
            console.log('Shape created:', shape);

            // Dapatkan WKT dari layer
            const wkt = getWKT(e.layer);
            console.log('WKT generated:', wkt);

            if (!wkt) {
                console.error('Failed to generate WKT for the created shape');
                return;
            }

            // Simpan data shape yang baru dibuat
            currentShapeData = {
                layer: e.layer,
                wkt: wkt,
                shape: shape
            };

            console.log('Created shape data:', currentShapeData);

            // Tambahkan delay sebelum menampilkan modal
            setTimeout(() => {
                // Pastikan semua modal lain tertutup terlebih dahulu
                window.dispatchEvent(new CustomEvent('close-tree-modal'));
                window.dispatchEvent(new CustomEvent('close-plantation-modal'));

                // Tampilkan HANYA modal pemilihan form antara pohon dan blok kebun
                console.log('Dispatching open-form-selector-modal event after delay');
                try {
                    // Reset currentShapeType sebelum pemilihan
                    currentShapeType = null;
                    window.dispatchEvent(new CustomEvent('open-form-selector-modal'));
                    console.log('Event open-form-selector-modal dispatched successfully');
                } catch (error) {
                    console.error('Failed to dispatch open-form-selector-modal event:', error);
                }
            }, 100);
        });

        // Event listener untuk menghapus objek
        map.on('pm:remove', function(e) {
            console.log('pm:remove event triggered', e);

            // Periksa apakah layer memiliki treeData (sudah disimpan di database)
            if (e.layer.treeData && e.layer.treeData.id) {
                // Jika layer sudah disimpan, konfirmasi penghapusan dari database
                if (confirm('Apakah Anda yakin ingin menghapus pohon ini dari database?')) {
                    deleteTree(e.layer.treeData.id);
                } else {
                    // Jika user membatalkan, tambahkan kembali layer ke peta
                    drawnItems.addLayer(e.layer);
                }
            } else if (e.layer.plantationData && e.layer.plantationData.id) {
                // Jika layer adalah blok kebun dan sudah disimpan, konfirmasi penghapusan
                if (confirm('Apakah Anda yakin ingin menghapus blok kebun ini dari database?')) {
                    deletePlantationById(e.layer.plantationData.id);
                } else {
                    // Jika user membatalkan, tambahkan kembali layer ke peta
                    drawnItems.addLayer(e.layer);
                }
            } else {
                // Jika layer belum disimpan, cukup hapus dari peta tanpa konfirmasi
                console.log('Removing unsaved layer from map');
                drawnItems.removeLayer(e.layer);
            }
        });

        pmCreateHandlerAdded = true;
        console.log('pm:create event handler added');
    } else {
        console.log('User is Guest, pm:create event handler not added');
    }
}

// Fungsi untuk membuka form data pohon
function openTreeForm() {
    console.log('Opening tree form with currentShapeData:', currentShapeData);

            // Tutup modal pemilihan form terlebih dahulu
            window.dispatchEvent(new CustomEvent('close-form-selector-modal'));

    // Tunggu sebentar untuk memastikan modal pemilihan tertutup
            setTimeout(() => {
        // Ambil geometri dari currentShapeData
        let geometry = null;
        let shapeType = 'Polygon';

    if (currentShapeData && currentShapeData.wkt) {
            geometry = currentShapeData.wkt;
            if (typeof detectShapeTypeFromWKT === 'function') {
                shapeType = detectShapeTypeFromWKT(geometry);
            }
        }

        console.log('Dispatching open-tree-modal event with geometry:', geometry);

        // Buka modal tree form
        window.dispatchEvent(new CustomEvent('open-tree-modal', {
            detail: {
                isEdit: false,
                treeData: null,
                geometryWkt: geometry,
                shapeType: shapeType
            }
        }));
    }, 300); // Delay untuk memastikan modal pemilihan tertutup dulu
}

// Fungsi untuk membuka form data blok kebun
function openPlantationForm() {
    console.log('Opening plantation form with currentShapeData:', currentShapeData);

            // Tutup modal pemilihan form terlebih dahulu
            window.dispatchEvent(new CustomEvent('close-form-selector-modal'));

    // Tunggu sebentar untuk memastikan modal pemilihan tertutup
            setTimeout(() => {
        // Ambil geometri dari currentShapeData
        let geometry = null;

        if (currentShapeData && currentShapeData.wkt) {
            // Pastikan format WKT benar
            geometry = ensureWktFormat(currentShapeData.wkt);
        }

        console.log('Dispatching open-plantation-modal event with geometry:', geometry);

        // Buka modal plantation form
        window.dispatchEvent(new CustomEvent('open-plantation-modal', {
            detail: {
                isEdit: false,
                plantationData: null,
                geometryWkt: geometry
            }
        }));

        // Hitung area menggunakan Turf.js jika geometry tersedia dan calculateAreaFromWkt tersedia
        if (geometry && typeof calculateAreaFromWkt === 'function') {
            const area = calculateAreaFromWkt(geometry);
            console.log('Area calculated from WKT:', area, 'hectares');
        }
    }, 300); // Delay untuk memastikan modal pemilihan tertutup dulu
}

// Fungsi untuk membatalkan pemilihan jenis form
function cancelFormSelection() {
    console.log('Cancelling form selection');

    // Tutup modal pemilihan form
        window.dispatchEvent(new CustomEvent('close-form-selector-modal'));

    // Batalkan bentuk jika masih dalam proses pembuatan
    if (typeof currentShapeData !== 'undefined' && currentShapeData.layer && !currentShapeData.layer.isSaved) {
        console.log('Cancelling unsaved shape');
        cancelShape();
    }
}

// Fungsi untuk memilih form pohon
function selectTreeForm() {
    console.log('selectTreeForm called');

    // Tutup modal pemilihan
    window.dispatchEvent(new CustomEvent('close-form-selector-modal'));

    // Set tipe objek menjadi pohon
    currentShapeType = 'tree';

    // Beri delay agar modal pemilihan tertutup sepenuhnya
    setTimeout(function() {
        // Pastikan currentShapeData ada
        if (!currentShapeData) {
            console.error('No shape data available for tree form');
            alert('Error: Tidak ada data bentuk tersedia');
            return;
        }

        // Tambahkan layer ke drawnItems (layer pohon)
        if (currentShapeData.layer && !currentShapeData.layer.isSaved) {
            // Pastikan layer belum ada di drawnItems (menghindari duplikasi)
            if (!drawnItems.hasLayer(currentShapeData.layer)) {
                drawnItems.addLayer(currentShapeData.layer);
                console.log('Layer added to tree layer (drawnItems)');
            }
        }

        // Log data untuk debugging
        console.log('Current shape data for tree:', currentShapeData);

        // Ambil WKT geometry
        let geometry = currentShapeData.wkt;
        let shapeType = 'Polygon';

        // Deteksi tipe shape
        if (typeof detectShapeTypeFromWKT === 'function') {
            shapeType = detectShapeTypeFromWKT(geometry);
        }

        console.log('Opening tree modal with geometry:', geometry);

        // Dispatch event untuk membuka modal pohon
        window.dispatchEvent(new CustomEvent('open-tree-modal', {
            detail: {
                isEdit: false,
                treeData: null,
                geometryWkt: geometry,
                shapeType: shapeType
            }
        }));
    }, 300);
}

// Fungsi untuk mengirimkan form data blok kebun
function submitPlantationForm() {
    console.log('Submitting plantation form...');

    // Check if the form exists
    const plantationForm = document.getElementById('plantationForm');
    if (!plantationForm) {
        console.error('Plantation form not found!');
        return;
    }

    // Get the form fields
    const nameInput = document.getElementById('name');
    const geometryInput = document.getElementById('boundary_geometry');
    const areaInput = document.getElementById('luas_area');
    const formMethod = document.getElementById('plantation_form_method').value || 'POST';
    const plantationId = document.getElementById('plantation_id').value;

    // Validate required fields
    if (!nameInput || !nameInput.value) {
        alert('Nama blok kebun harus diisi!');
        return;
    }

    if (!geometryInput || !geometryInput.value) {
        alert('Anda harus menggambar area blok kebun terlebih dahulu!');
        return;
    }

    // Validate WKT format
    const wkt = geometryInput.value;
    if (!wkt.toUpperCase().startsWith('POLYGON') && !wkt.toUpperCase().startsWith('MULTIPOLYGON') &&
        !wkt.toUpperCase().startsWith('SRID=')) {
        alert('Format geometri tidak valid. Harus berupa POLYGON atau MULTIPOLYGON.');
        return;
    }

    // If area is empty or zero, try to calculate it
    if (!areaInput.value || parseFloat(areaInput.value) === 0) {
        console.log('Area kosong, menghitung dari geometri...');
        const calculatedArea = calculateAreaFromWkt(wkt);
        if (calculatedArea > 0) {
            console.log('Area berhasil dihitung:', calculatedArea);
            areaInput.value = calculatedArea.toFixed(4);
        } else {
            alert('Perhitungan luas area gagal. Silakan masukkan luas area secara manual.');
            areaInput.focus();
            return;
        }
    } else {
        // Pastikan format luas area konsisten dengan 4 angka desimal
        const areaValue = parseFloat(areaInput.value);
        if (!isNaN(areaValue)) {
            areaInput.value = areaValue.toFixed(4);
            console.log('Luas area diformat ulang untuk konsistensi:', areaInput.value);
        }
    }

    // Show loading screen
    if (typeof showLoading === 'function') {
        showLoading('Menyimpan data blok kebun...');
    }

    // Prepare the data
    const formData = new FormData(plantationForm);
    let url = plantationForm.getAttribute('action');

    if (formMethod.includes('PUT') || formMethod.includes('PATCH')) {
        url = `${url}/${plantationId}`;
    }

    const jsonData = {};
    formData.forEach((value, key) => {
        jsonData[key] = value;
    });

    console.log('Sending plantation data:', jsonData);

    // Prepare fetch options based on method
    const fetchOptions = {
        method: formMethod === 'PUT' || formMethod === 'PATCH' ? 'PUT' : 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(jsonData)
    };

    // Send the request
    fetch(url, fetchOptions)
    .then(response => {
        if (!response.ok) {
            // Coba ambil pesan error dari respons json jika ada
            return response.json().then(errData => {
                throw new Error(errData.message || `HTTP error! status: ${response.status}`);
            }).catch(err => {
                // Jika tidak bisa parse JSON, gunakan pesan default
                if (err instanceof SyntaxError) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                throw err;
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Plantation saved successfully:', data);

        // Hide the modal
        window.dispatchEvent(new CustomEvent('close-plantation-modal'));

        // Sembunyikan loading indicator jika ada
        if (typeof hideLoading === 'function') {
            hideLoading();
        }

        // Tampilkan pesan sukses
        alert(data.message || 'Blok kebun berhasil disimpan!');

        // Tambahkan blok kebun yang baru disimpan ke peta
        const savedPlantation = data.data;
        if (savedPlantation && savedPlantation.id) {
            console.log('Menambahkan blok kebun yang disimpan ke peta:', savedPlantation);
            try {
                // Inisialisasi layer group jika belum ada
                if (!window.plantationLayerGroup) {
                    window.plantationLayerGroup = L.layerGroup();
                    window.plantationLayerGroup.addTo(map);

                    // Tambahkan ke layer control jika ada
                    if (window.baseLayerControl) {
                        window.baseLayerControl.addOverlay(window.plantationLayerGroup, 'Blok Kebun');
                    } else if (window.layerControl) {
                        window.layerControl.addOverlay(window.plantationLayerGroup, 'Blok Kebun');
                    }
                }

                // Inisialisasi koleksi layer jika belum ada
                if (!window.plantationLayers) {
                    window.plantationLayers = {};
                }

                // Jika sedang mengedit, hapus layer lama terlebih dahulu
                if (formMethod.includes('PATCH') || formMethod.includes('PUT')) {
                    const plantationId = savedPlantation.id;
                    if (window.plantationLayers[plantationId]) {
                        // Hapus dari layer group
                        window.plantationLayerGroup.removeLayer(window.plantationLayers[plantationId]);
                        // Hapus referensi
                        delete window.plantationLayers[plantationId];
                        console.log('Layer lama berhasil dihapus');
                    }
                }

                // Extract geometri dari hasil yang disimpan
                let geometry = savedPlantation.geometry;
                console.log('Geometry dari server:', geometry.substring(0, 50) + '...');

                // Remove SRID if present
                if (geometry.toUpperCase().startsWith('SRID=')) {
                    geometry = geometry.substring(geometry.indexOf(';') + 1);
                }

                // Deteksi dan handle format hex/binary (format PostGIS yang diawali dengan '01')
                if (/^0[1-9][0-9A-Fa-f]+$/.test(geometry)) {
                    console.warn('Format WKT tidak didukung untuk plantation:', savedPlantation.id);

                    // Buat koordinat default dari latitude/longitude plantation jika tersedia
                    if (savedPlantation.latitude && savedPlantation.longitude) {
                        console.log('Menggunakan koordinat default dari data plantation:',
                            savedPlantation.latitude, savedPlantation.longitude);

                        // Buat WKT POLYGON dengan koordinat dari plantation
                        const lat = parseFloat(savedPlantation.latitude);
                        const lng = parseFloat(savedPlantation.longitude);

                        if (!isNaN(lat) && !isNaN(lng)) {
                            // Buat polygon kecil sekitar titik pusat
                            const size = 0.005; // ~500m
                            geometry = `POLYGON((${lng-size} ${lat-size}, ${lng+size} ${lat-size}, ${lng+size} ${lat+size}, ${lng-size} ${lat+size}, ${lng-size} ${lat-size}))`;
                            console.log('Geometry diganti dengan POLYGON default:', geometry);
                        }
                    }
                }

                // Parse the WKT to GeoJSON untuk ditampilkan di leaflet
                const geojson = parseWKTtoGeoJSON(geometry);
                if (geojson) {
                    // Buat style untuk layer
                    const plantationStyle = {
                        color: '#3388ff',
                        weight: 4,
                        opacity: 0.9,
                        fillOpacity: 0.15,
                        fillColor: '#3388ff',
                        pane: 'plantationsPane' // Gunakan pane dengan z-index rendah
                    };

                    // Buat layer
                    const newLayer = L.geoJSON(geojson, {
                        style: plantationStyle,
                        pane: 'plantationsPane', // Pastikan menggunakan pane yang benar
                        onEachFeature: function(feature, layer) {
                            // Tambahkan popup jika diperlukan
                            const popupContent = createPlantationPopupContent(savedPlantation);
                            layer.bindPopup(popupContent);

                            // Tandai sebagai disimpan
                            layer.isSaved = true;
                            layer.plantationId = savedPlantation.id;
                            layer.plantationData = savedPlantation;
                        }
                    });

                    // Tambahkan ke layer group dan simpan referensi
                    newLayer.addTo(window.plantationLayerGroup);
                    window.plantationLayers[savedPlantation.id] = newLayer;

                    console.log('Blok kebun berhasil ditambahkan ke peta');

                    // Zoom ke layer baru
                    map.fitBounds(newLayer.getBounds());

                    // Pastikan layer pohon berada di atas blok kebun
                    ensureTreeLayerOnTop();
                } else {
                    console.error('Gagal mengkonversi geometry ke GeoJSON');
                }
            } catch (err) {
                console.error('Error menambahkan blok kebun ke peta:', err);
            }
        } else {
            console.warn('Data blok kebun tidak lengkap, mencoba memuat ulang semua blok');
            // Reload all plantations if we don't have complete data
            loadExistingPlantations();
        }

    })
    .catch(error => {
        console.error('Error saving plantation:', error);

        // Sembunyikan loading indicator jika ada
        if (typeof hideLoading === 'function') {
            hideLoading();
        }

        // Tampilkan error message
        alert('Gagal menyimpan blok kebun: ' + error.message);
    });
}

// Function to load existing plantations from the server
function loadExistingPlantations() {
    console.log('Loading existing plantations from server...');

    // Inisialisasi layer group untuk blok kebun jika belum ada
    if (!window.plantationLayerGroup) {
        window.plantationLayerGroup = L.layerGroup();
        // Tambahkan ke map
        window.plantationLayerGroup.addTo(map);

        // Tambahkan ke layer control jika sudah ada
        if (window.baseLayerControl) {
            window.baseLayerControl.addOverlay(window.plantationLayerGroup, 'Blok Kebun');
            console.log('Added plantation layer to existing baseLayerControl');
        } else if (window.layerControl) {
            window.layerControl.addOverlay(window.plantationLayerGroup, 'Blok Kebun');
            console.log('Added plantation layer to existing layerControl');
        } else {
            console.log('No layer control found, skipping overlay addition');
        }
    }

    // Kosongkan layer group dan referensi
    window.plantationLayerGroup.clearLayers();

    // Objek untuk menyimpan referensi layer
    if (!window.plantationLayers) {
        window.plantationLayers = {};
    } else {
        // Reset koleksi layers
        window.plantationLayers = {};
    }

    // Objek untuk menyimpan data plantation berdasarkan ID
    if (!window.plantationData) {
        window.plantationData = {};
    }

    // Tampilkan loading jika ada
    if (typeof showLoading === 'function') {
        showLoading('Memuat data blok kebun...');
    }

    // Fetch plantations dari server
    console.log('Fetching plantations from server');

    fetch('/api/plantations?timestamp=' + new Date().getTime())
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch plantations: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Plantations data fetched:', data.success ? 'Success' : 'Failed',
                        'Count:', data.data ? data.data.length : 0);

            // Sembunyikan loading jika ada
            if (typeof hideLoading === 'function') {
                hideLoading();
            }

            if (!data.success || !data.data || !Array.isArray(data.data)) {
                console.error('Invalid plantation data format:', data);
                return;
            }

            const plantations = data.data;

            if (plantations.length === 0) {
                console.log('No plantations found');
                return;
            }

            console.log(`Processing ${plantations.length} plantations...`);

            // Simpan data plantation dalam window.plantationData
            plantations.forEach(plantation => {
                if (plantation.id) {
                    window.plantationData[plantation.id] = plantation;
                }
            });

            // Loop through each plantation untuk tampilkan di map
            plantations.forEach((plantation, index) => {
                try {
                    console.log(`[${index+1}/${plantations.length}] Processing plantation:`, plantation.id, plantation.name);

                    // Gunakan geometry yang berisi WKT daripada geometry
                    let wktGeometry = null;

                    // Coba ambil dari geometry (hasil ST_AsText)
                    if (plantation.geometry) {
                        wktGeometry = plantation.geometry;
                        console.log('Using geometry for plantation:', plantation.id);
                    }
                    // Fallback ke boundary_text jika ada
                    else if (plantation.boundary_text) {
                        wktGeometry = plantation.boundary_text;
                        console.log('Using boundary_text for plantation:', plantation.id);
                    }
                    // Fallback ke geometry biasa
                    else if (plantation.geometry) {
                        wktGeometry = plantation.geometry;
                        console.log('Using geometry for plantation:', plantation.id);
                    } else {
                        console.warn('Plantation has no geometry:', plantation.id);
                        return;
                    }

                    // Logging untuk debugging
                    console.log(`Plantation ${plantation.id} WKT:`, wktGeometry.substring(0, 50) + '...');

                    // Deteksi apakah ini format WKT standar (harus dimulai dengan keywords khusus)
                    const isValidWKT =
                        wktGeometry.toUpperCase().startsWith('POLYGON') ||
                        wktGeometry.toUpperCase().startsWith('MULTIPOLYGON') ||
                        wktGeometry.toUpperCase().startsWith('POINT') ||
                        (wktGeometry.toUpperCase().startsWith('SRID=') && (
                            wktGeometry.toUpperCase().includes('POLYGON') ||
                            wktGeometry.toUpperCase().includes('MULTIPOLYGON') ||
                            wktGeometry.toUpperCase().includes('POINT')
                        ));

                    if (!isValidWKT) {
                        console.error(`Plantation ${plantation.id} doesn't have standard WKT format:`, wktGeometry.substring(0, 50) + '...');
                        return;
                    }

                    // Simpan ID plantation yang sedang diproses untuk referensi
                    window.currentPlantationBeingEdited = plantation.id;

                    // Parse the WKT to GeoJSON untuk ditampilkan di leaflet
                    const geojson = parseWKTtoGeoJSON(wktGeometry);

                    // Reset ID plantation yang sedang diproses
                    window.currentPlantationBeingEdited = null;

                    if (!geojson) {
                        console.error('Failed to parse WKT for plantation:', plantation.id);
                        return;
                    }

                    console.log('GeoJSON created for plantation:', plantation.id, 'type:', geojson.geometry.type);

                    // Create a style for the plantation
                    const plantationStyle = {
                        color: '#3388ff',
                        weight: 4,
                        opacity: 0.9,
                        fillOpacity: 0.15,
                        fillColor: '#3388ff',
                        pane: 'plantationsPane' // Gunakan pane dengan z-index rendah
                    };

                    // Create the GeoJSON layer with the specific style
                    const plantationLayer = L.geoJSON(geojson, {
                        style: plantationStyle,
                        pane: 'plantationsPane', // Pastikan menggunakan pane yang benar
                        onEachFeature: function(feature, layer) {
                            // Add popup with plantation info
                            const popupContent = createPlantationPopupContent(plantation);
                            layer.bindPopup(popupContent);

                            // Mark as saved and store plantation data in layer
                            layer.isSaved = true;
                            layer.plantationId = plantation.id;
                            layer.plantationData = plantation;
                        }
                    });

                    // Tambahkan ke layer group
                    plantationLayer.addTo(window.plantationLayerGroup);

                    // Store the layer reference
                    window.plantationLayers[plantation.id] = plantationLayer;

                    console.log('Plantation layer added to map:', plantation.id);
                } catch (error) {
                    console.error('Error processing plantation:', plantation.id, error);
                }
            });

            // Fit map to all plantation bounds
            if (Object.keys(window.plantationLayers).length > 0) {
                // Cek jika ada parameter plantation_id di URL
                const urlParams = new URLSearchParams(window.location.search);
                const plantationIdParam = urlParams.get('plantation_id');
                const treeIdParam = urlParams.get('id');
                const zoomToTreeParam = urlParams.get('zoom_to_tree'); // Tambahkan ini

                // Jika ada parameter plantation_id, zoom ke plantation tersebut
                if (plantationIdParam) {
                    console.log('Zooming to specific plantation:', plantationIdParam);
                    const specificLayer = window.plantationLayers[plantationIdParam];

                    if (specificLayer && typeof specificLayer.getBounds === 'function') {
                        try {
                            const layerBounds = specificLayer.getBounds();
                            if (layerBounds && layerBounds.isValid()) {
                                map.fitBounds(layerBounds, {
                                    padding: [50, 50], // Tambahkan padding agar terlihat lebih baik
                                    maxZoom: 19 // Batasi zoom maksimum
                                });
                                console.log('Successfully zoomed to plantation:', plantationIdParam);
                            } else {
                                console.error('Invalid bounds for plantation:', plantationIdParam);
                            }
                        } catch (e) {
                            console.error('Error fitting bounds for specific plantation:', e);
                        }
                    } else {
                        console.error('Plantation layer not found or invalid:', plantationIdParam);
                    }
                }
                // Jangan fit to all bounds jika ada parameter tree_id atau zoom_to_tree di URL
                else if (!treeIdParam && zoomToTreeParam !== 'true') { // Modifikasi kondisi ini
                    console.log('Zooming to fit all plantations');
                    const bounds = [];

                    Object.values(window.plantationLayers).forEach(layer => {
                        try {
                            if (layer && typeof layer.getBounds === 'function') {
                                const layerBounds = layer.getBounds();
                                if (layerBounds && layerBounds.isValid()) {
                                    bounds.push(layerBounds);
                                }
                            }
                        } catch (e) {
                            console.error('Error getting bounds:', e);
                        }
                    });

                    if (bounds.length > 0) {
                        try {
                            const combinedBounds = L.latLngBounds(bounds);
                            map.fitBounds(combinedBounds);
                        } catch (e) {
                            console.error('Error fitting bounds:', e);
                        }
                    }
                } else {
                    console.log('Skipping zoom to all plantations because tree_id parameter is present in URL');
                }
            } else {
                console.warn('No valid plantation layers created, skipping map zoom');
            }

            // Pastikan layer pohon tetap di atas blok kebun
            ensureTreeLayerOnTop();
        })
        .catch(error => {
            console.error('Error loading plantations:', error);
            // Sembunyikan loading jika ada
            if (typeof hideLoading === 'function') {
                hideLoading();
            }
            alert('Gagal memuat data blok kebun: ' + error.message);
        });
}

// Function to create popup content for a plantation
function createPlantationPopupContent(plantation) {
    try {
        // Pastikan data valid
        const name = plantation.name || 'Tidak Ada Nama';
        const id = plantation.id || '0';

        // Gunakan nilai luas_area yang disimpan langsung dari database
        // tanpa menghitung ulang atau mengubah format (kecuali pembulatan)
        let luasArea = '0.00';
        if (plantation.luas_area) {
            // Pastikan pembulatan dengan 2 angka desimal
            const luasAreaFloat = parseFloat(plantation.luas_area);
            luasArea = isNaN(luasAreaFloat) ? '0.00' : luasAreaFloat.toFixed(2);
        }

        // Dapatkan role pengguna dari data yang disimpan di window
        const userRole = window.userRole || '';

        return `
            <div class="popup-content">
                <div class="mb-2">
                    <strong>ID:</strong> ${id}<br>
                    <strong>Nama:</strong> ${name}<br>
                    <strong>Luas Area:</strong> ${luasArea} ha<br>
                </div>
                ${userRole !== 'Guest' && userRole !== 'Operasional' ? `
                <div class="text-center mt-2">
                    <button onclick="editPlantation(${id})"
                            style="background-color: #f97316; color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border: none; margin-right: 5px;">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deletePlantationById(${id})"
                            style="background-color: #ef4444; color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border: none;">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                ` : ''}
            </div>
        `;
    } catch (error) {
        console.error('Error creating popup content:', error);
        return `<div class="error-popup">Error: ${error.message}</div>`;
    }
}

// Fungsi untuk mengubah/update data blok kebun
function editPlantation(plantationId) {
    console.log('Editing plantation with ID:', plantationId);

    // Tampilkan loading
    if (typeof showLoading === 'function') {
    showLoading('Memuat data blok kebun...');
    }

    // Dapatkan timestamp untuk cache busting
    const timestamp = new Date().getTime();

    // Hentikan mode edit yang mungkin sedang berjalan sebelumnya
    if (window.activePlantationEditLayer) {
        try {
            map.removeLayer(window.activePlantationEditLayer);
            window.activePlantationEditLayer = null;
        } catch (e) {
            console.error('Error removing active plantation edit layer:', e);
        }
    }

    // Ambil data plantation dari server
    fetch(`/api/plantations/${plantationId}?_=${timestamp}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch plantation: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Sembunyikan loading
            if (typeof hideLoading === 'function') {
            hideLoading();
            }

            if (data.success && data.data) {
                const plantation = data.data;

                console.log('Plantation data loaded:', plantation);

                // Gunakan geometry yang paling akurat
                let geometryToUse = null;
                if (plantation.geometry) {
                    geometryToUse = plantation.geometry;
                    console.log('Using geometry for editing:', geometryToUse.substring(0, 50) + '...');
                } else if (plantation.boundary_text) {
                    geometryToUse = plantation.boundary_text;
                    console.log('Using boundary_text for editing:', geometryToUse.substring(0, 50) + '...');
                } else if (plantation.geometry) {
                    geometryToUse = plantation.geometry;
                    console.log('Using geometry for editing:', geometryToUse.substring(0, 50) + '...');
                } else {
                    console.error('No valid geometry found for plantation:', plantationId);
                    alert('Tidak dapat mengedit: Data geometri tidak tersedia');
                    return;
                }

                // Cari layer plantation yang sudah ada di map (jika ada)
                let plantationLayer = null;
                if (window.plantationLayers && window.plantationLayers[plantationId]) {
                    plantationLayer = window.plantationLayers[plantationId];
                }

                // Update the currentShapeData
                    currentShapeData = {
                        layer: plantationLayer,
                    wkt: geometryToUse,
                    shape: detectShapeTypeFromWKT(geometryToUse)
                };

                // Trigger the modal
                window.dispatchEvent(new CustomEvent('open-plantation-modal', {
                    detail: {
                        isEdit: true,
                        plantationData: plantation,
                        geometryWkt: geometryToUse
                    }
                }));
            } else {
                console.error('Failed to load plantation data:', data.message || 'Unknown error');
                alert('Gagal memuat data blok kebun');
            }
        })
        .catch(error => {
            console.error('Error fetching plantation data:', error);

            // Sembunyikan loading
            if (typeof hideLoading === 'function') {
            hideLoading();
            }

            alert('Gagal memuat data blok kebun: ' + error.message);
        });
}

// Mendeteksi jenis bentuk dari string WKT
function detectShapeTypeFromWKT(wkt) {
    if (!wkt) return null;

    const wktUpper = wkt.toUpperCase();

    if (wktUpper.startsWith('POINT')) {
        return 'Marker';
    } else if (wktUpper.startsWith('LINESTRING')) {
        return 'Line';
    } else if (wktUpper.startsWith('POLYGON')) {
        return 'Polygon';
    } else if (wktUpper.startsWith('CIRCLE')) {
        return 'Circle';
    } else if (wktUpper.startsWith('RECTANGLE')) {
        return 'Rectangle';
    } else {
        console.warn('Unknown shape type in WKT:', wkt);
        return 'Polygon'; // Default to polygon
    }
}

// Fungsi untuk mengirim form pohon
function submitTreeForm() {
    console.log('submitTreeForm called');

    // Ambil form
    const form = document.getElementById('treeForm');
    if (!form) {
        console.error('Form not found!');
        return false;
    }

    // Validasi form
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }

    // Konversi ID pohon ke huruf kapital jika ada
    let idValue = '';
    const formMode = document.getElementById('form_mode').value;

    // Dapatkan nilai ID berdasarkan mode form (create atau update)
    if (formMode === 'create') {
    const customIdInput = document.getElementById('custom_id');
    if (customIdInput && customIdInput.value.trim() !== '') {
        // Ubah ke huruf kapital
        customIdInput.value = customIdInput.value.toUpperCase();
            idValue = customIdInput.value;
        }
    } else { // mode edit
        const displayIdInput = document.getElementById('display_id');
        if (displayIdInput && displayIdInput.value.trim() !== '') {
            // Ubah ke huruf kapital
            displayIdInput.value = displayIdInput.value.toUpperCase();
            idValue = displayIdInput.value;
        }
    }

    // Validasi format ID jika ada
    if (idValue) {
        // Validasi format ID (harus berupa angka diikuti dengan huruf)
        const idPattern = /^(\d+)([A-Z]+)$/;
        if (!idPattern.test(idValue)) {
            alert('Format ID pohon tidak valid. Gunakan format angka diikuti huruf (contoh: 1A, 2B, 10C)');
            return false;
        }

        // Jika ini mode tambah (bukan edit), verifikasi bahwa ID belum digunakan
        if (formMode === 'create') {
            // Cek dalam drawnItems apakah ada pohon dengan ID yang sama
            let isDuplicate = false;
            drawnItems.eachLayer(function(layer) {
                if (layer.treeData && layer.treeData.id === idValue) {
                    isDuplicate = true;
                }
            });

            if (isDuplicate) {
                alert('ID pohon ' + idValue + ' sudah digunakan. Silakan gunakan ID lain.');
                return false;
            }
        }
    }

    // Tampilkan loading
    showLoading('Menyimpan data pohon...');

    // Dapatkan data form
    const formData = new FormData(form);
    const method = document.getElementById('form_method').value || 'POST';
    let url = '/api/trees';

    // Simpan ID asli sebelum diubah (untuk mode edit)
    const originalTreeId = document.getElementById('tree_id').value;

    // Jika ini edit, gunakan URL dengan ID asli (bukan ID yang mungkin telah diubah)
    if (method === 'PUT') {
        url = `/api/trees/${originalTreeId}`;
    }

    // Konversi FormData ke object untuk fetch
    const data = {};
    formData.forEach((value, key) => {
        // Jika ini adalah field id, pastikan dalam format kapital
        if (key === 'id' && value) {
            data[key] = value.toString().toUpperCase();
        } else {
            data[key] = value;
        }
    });

    // Pastikan ID baru dimasukkan dalam data jika dalam mode edit
    if (formMode === 'update' && idValue) {
        data['id'] = idValue;
        console.log('Setting ID explicitly for update to:', idValue);
    }

    // Log data yang akan dikirim
    console.log('Sending tree data to server:', data);

    // Kirim data ke server
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            // Coba ambil pesan error dari respons JSON jika ada
            return response.json()
                .then(errData => {
                    throw new Error(errData.message || `HTTP error! status: ${response.status}`);
                })
                .catch(err => {
                    // Jika tidak bisa parse JSON, gunakan pesan default
                    if (err instanceof SyntaxError) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    throw err;
                });
        }
        return response.json();
    })
    .then(data => {
        // Sembunyikan loading
        hideLoading();

        if (data.success) {
            console.log('Tree saved successfully:', data);

            // Tutup modal
            window.dispatchEvent(new CustomEvent('close-tree-modal'));

            // Tandai layer sebagai tersimpan
            if (currentShapeData && currentShapeData.layer) {
                currentShapeData.layer.isSaved = true;

                // Tambahkan data pohon ke layer
                currentShapeData.layer.treeData = data.data;

                // Ubah style layer untuk menunjukkan bahwa sudah tersimpan
                let healthColor = getHealthColor(data.data.health_status);

                currentShapeData.layer.setStyle({
                    color: healthColor,
                    weight: 4,
                    opacity: 0.9,
                    fillColor: healthColor,
                    fillOpacity: 0.25
                });

                // Tambahkan popup dengan data pohon
                currentShapeData.layer.bindPopup(createPopupContent(data.data));
            }

            // Tampilkan pesan sukses
            const message = method === 'PUT' ? 'Data pohon berhasil diperbarui!' : 'Data pohon berhasil disimpan!';
            alert(message);

            // Reset currentShapeData
            currentShapeData = {
                layer: null,
                wkt: null,
                shape: null
            };

            // Reload trees tanpa refresh halaman
            setTimeout(() => {
                console.log('Reloading trees after edit/create');
                loadExistingTrees(true); // Tambahkan parameter true untuk memaksa reload
            }, 500);
        } else {
            console.error('Error saving tree:', data.message);
            alert('Gagal menyimpan data pohon: ' + data.message);
        }
    })
    .catch(error => {
        // Sembunyikan loading
        hideLoading();

        console.error('Error saving tree:', error);
        alert('Terjadi kesalahan saat menyimpan data: ' + error.message);
    });

    return false;
}

// Function to handle tree deletion
function deleteTree(treeId) {
    if (isDeleting) return; // Prevent multiple deletions
    isDeleting = true;

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Tampilkan loading
    showLoading('Menghapus data pohon...');

    fetch(`/api/trees/${treeId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': token,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => Promise.reject(err));
        }
        return response.json();
    })
    .then(data => {
        hideLoading();
        if (data.success) {
            console.log('Tree deleted successfully:', data.message);
            alert(data.message);

            // Hapus layer dari peta
            drawnItems.eachLayer(function(layer) {
                if ((layer.treeData && layer.treeData.id === treeId) ||
                    (layer.feature && layer.feature.properties && layer.feature.properties.id === treeId)) {
                    drawnItems.removeLayer(layer);
                }
            });

            // Reload data pohon
            loadExistingTrees();
        } else {
            throw new Error(data.message || 'Gagal menghapus pohon');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error deleting tree:', error);
        alert(error.message || 'Gagal menghapus data pohon');
    })
    .finally(() => {
        isDeleting = false;
    });
}

// Fungsi untuk menghapus pohon berdasarkan ID
function deleteTreeById(treeId) {
    if (confirm('Apakah Anda yakin ingin menghapus pohon ini?')) {
        deleteTree(treeId);
    }
}

// Handle layer removal
map.on('pm:remove', function(e) {
    console.log('pm:remove event triggered', e);

    // Periksa apakah layer memiliki treeData (sudah disimpan di database)
    if (e.layer.treeData && e.layer.treeData.id && !isDeleting) {
        // Jika layer sudah disimpan, konfirmasi penghapusan dari database
        if (confirm('Apakah Anda yakin ingin menghapus pohon ini dari database?')) {
            deleteTree(e.layer.treeData.id);
        } else {
            // Jika user membatalkan, tambahkan kembali layer ke peta
            drawnItems.addLayer(e.layer);
        }
    }
});

// Helper function to convert Leaflet layer to WKT
function getWKT(layer) {
    try {
        console.log('Converting layer to WKT:', layer);

        // Cek tipe layer
        let wkt = null;

        // Jika layer adalah marker (titik)
        if (layer.pm && layer.pm._shape === 'Marker') {
            console.log('Layer is a Marker');
            const latlng = layer.getLatLng();
            const lng = parseFloat(latlng.lng).toFixed(6);
            const lat = parseFloat(latlng.lat).toFixed(6);
            wkt = `POINT(${lng} ${lat})`;
            console.log('Generated Point WKT:', wkt);
            return wkt;
        }
        // Jika layer adalah lingkaran
        else if (layer.pm && layer.pm._shape === 'Circle') {
            console.log('Layer is a Circle');
            const center = layer.getLatLng();
            const radius = layer.getRadius(); // dalam meter

            // Konversi lingkaran ke polygon dengan 32 titik
            const points = [];
            for (let i = 0; i < 32; i++) {
                const angle = (i / 32) * 2 * Math.PI;
                const dx = radius * Math.cos(angle);
                const dy = radius * Math.sin(angle);

                // Konversi dx, dy (meter) ke koordinat geografis
                // Perkiraan kasar: 1 derajat = 111,000 meter di ekuator
                // Untuk latitude, 1 derajat = 111,000 meter
                // Untuk longitude, 1 derajat = 111,000 * cos(latitude) meter
                const latFactor = 1 / 111000; // faktor konversi meter ke derajat latitude
                const lngFactor = 1 / (111000 * Math.cos(center.lat * Math.PI / 180)); // faktor konversi untuk longitude

                const lat = center.lat + dy * latFactor;
                const lng = center.lng + dx * lngFactor;

                points.push(`${lng.toFixed(6)} ${lat.toFixed(6)}`);
            }

            // Tambahkan titik pertama di akhir untuk menutup polygon
            points.push(points[0]);

            wkt = `POLYGON((${points.join(',')}))`;
            console.log('Generated Circle WKT (as polygon):', wkt);
            return wkt;
        }
        // Jika layer adalah polygon dari Leaflet-Geoman
        else if (layer.pm && layer.pm._shape === 'Polygon') {
            console.log('Layer is a Leaflet-Geoman Polygon');
            const coordinates = layer.getLatLngs()[0];

            if (!coordinates || !coordinates.length) {
                console.error('No coordinates found in polygon');
                return null;
            }

            console.log('Polygon coordinates:', coordinates);

            // Pastikan polygon tertutup (titik pertama dan terakhir sama)
            let points = [];
            for (let i = 0; i < coordinates.length; i++) {
                // Format koordinat dengan presisi 6 digit desimal untuk akurasi
                const lng = parseFloat(coordinates[i].lng).toFixed(6);
                const lat = parseFloat(coordinates[i].lat).toFixed(6);
                points.push(`${lng} ${lat}`);
            }

            // Tambahkan titik pertama di akhir untuk menutup polygon jika belum tertutup
            const firstLng = parseFloat(coordinates[0].lng).toFixed(6);
            const firstLat = parseFloat(coordinates[0].lat).toFixed(6);

            // Jika titik terakhir tidak sama dengan titik pertama, tambahkan titik pertama di akhir
            const lastPoint = points[points.length - 1];
            const firstPoint = `${firstLng} ${firstLat}`;

            if (lastPoint !== firstPoint) {
                points.push(firstPoint);
            }

            // Pastikan ada minimal 4 titik (3 titik + 1 titik penutup)
            if (points.length < 4) {
                console.error('Polygon must have at least 3 points');
            return null;
        }

            wkt = `POLYGON((${points.join(',')}))`;
        }
        // Jika layer adalah polygon biasa
        else if (layer.getLatLngs) {
            console.log('Layer is a regular Leaflet Polygon');
    const coordinates = layer.getLatLngs()[0];

        if (!coordinates || !coordinates.length) {
                console.error('No coordinates found in polygon');
            return null;
        }

            console.log('Polygon coordinates:', coordinates);

        // Pastikan polygon tertutup (titik pertama dan terakhir sama)
        let points = [];
        for (let i = 0; i < coordinates.length; i++) {
            // Format koordinat dengan presisi 6 digit desimal untuk akurasi
            const lng = parseFloat(coordinates[i].lng).toFixed(6);
            const lat = parseFloat(coordinates[i].lat).toFixed(6);
            points.push(`${lng} ${lat}`);
        }

        // Tambahkan titik pertama di akhir untuk menutup polygon jika belum tertutup
        const firstLng = parseFloat(coordinates[0].lng).toFixed(6);
        const firstLat = parseFloat(coordinates[0].lat).toFixed(6);

        // Jika titik terakhir tidak sama dengan titik pertama, tambahkan titik pertama di akhir
        const lastPoint = points[points.length - 1];
        const firstPoint = `${firstLng} ${firstLat}`;

        if (lastPoint !== firstPoint) {
            points.push(firstPoint);
        }

        // Pastikan ada minimal 4 titik (3 titik + 1 titik penutup)
        if (points.length < 4) {
            console.error('Polygon must have at least 3 points');
            return null;
        }

            wkt = `POLYGON((${points.join(',')}))`;
        }
        // Jika layer tidak dikenali
        else {
            console.error('Layer type not recognized');
            return null;
        }

        console.log('Generated WKT:', wkt);

        // Validasi format WKT
        if (!wkt || !(wkt.match(/^POLYGON\s*\(\s*\(\s*.+\s*\)\s*\)$/i) || wkt.match(/^POINT\s*\(.+\)$/i))) {
            console.error('Generated WKT is not valid:', wkt);
            return null;
        }

        return wkt;
    } catch (error) {
        console.error('Error converting layer to WKT:', error);
        return null;
    }
}

// Add event listener untuk tombol close modal
document.addEventListener('DOMContentLoaded', function() {
    const closeButtons = document.querySelectorAll('[data-close-modal]');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            window.dispatchEvent(new CustomEvent('close-tree-modal'));
        });
    });
});

// Layer Control (hanya perlu didefinisikan sekali)
baseLayers = {
    "Esri Map": L.layerGroup([satellite, labels]),
    'Google Hybrid': googleHybrid,
    "Google Streets": GoogleStreets,
};

overlays = {
    "Pohon": window.treeLayerGroup || L.layerGroup([], { pane: 'treesPane', renderer: L.canvas() }).addTo(map) // Gunakan window.treeLayerGroup alih-alih drawnItems
};

// Pastikan treeLayerGroup sudah dibuat
if (!window.treeLayerGroup) {
    window.treeLayerGroup = overlays["Pohon"];
}

// Inisialisasi aerialPhotoLayerGroup untuk foto udara
window.aerialPhotoLayerGroup = L.layerGroup().addTo(map);

// Inisialisasi control_layers sekali saja
if (!window.controlLayersInitialized) {
    control_layers = L.control.layers(baseLayers, overlays).addTo(map);
    // Tambahkan layer foto udara ke control
    control_layers.addOverlay(window.aerialPhotoLayerGroup, 'Foto Udara');
    window.controlLayersInitialized = true;
}

// Function to load existing trees from the server
function loadExistingTrees(forceReload = false) {
    console.log('Loading existing trees... forceReload:', forceReload);

    // Simpan popup yang sedang terbuka
    let openPopups = [];
    drawnItems.eachLayer(function(layer) {
        if (layer.isPopupOpen()) {
            openPopups.push({
                id: layer.treeData?.id || layer.feature?.properties?.id,
                latlng: layer.getPopup()._latlng
            });
        }
    });

    // Clear existing layers
    drawnItems.clearLayers();

    // Tambahkan timestamp untuk mencegah cache
    const timestamp = new Date().getTime();

    // Tambahkan parameter forceReload ke URL jika diperlukan
    const reloadParam = forceReload ? '&force=true' : '';

    // Status indikator (tanpa animasi)
    const statusDiv = document.createElement('div');
    statusDiv.id = 'treeLoadStatus';
    statusDiv.style.cssText = 'position:fixed;bottom:10px;right:10px;background:rgba(255,255,255,0.9);padding:8px 12px;border-radius:4px;z-index:1000;font-size:13px;border:1px solid #ccc;';
    document.body.appendChild(statusDiv);

    // Simpan semua data pohon untuk referensi
    window.allTreesData = [];

    // Fetch trees data from the server
    fetch(`/trees/get-all?_=${timestamp}&nocache=true${reloadParam}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Periksa format respons
            let trees = data;
            if (data && typeof data === 'object' && data.hasOwnProperty('success')) {
                if (!data.success) {
                    console.error('Error loading trees:', data.message);
                    return;
                }
                trees = data.data;
            }

            if (!trees || !Array.isArray(trees) || trees.length === 0) {
                console.log('No trees found');
                document.body.removeChild(statusDiv);
                return;
            }

            // Simpan data semua pohon untuk referensi
            window.allTreesData = trees;

            // Simpan layer berdasarkan ID untuk membuka popup nanti
            let layersById = {};

            // Proses data dalam batch untuk menghindari browser freeze
            const batchSize = 100; // Proses 100 pohon dalam satu batch
            const totalTrees = trees.length;
            let processedCount = 0;

            // Buat layer group untuk pohon
            if (!window.treeLayerGroup) {
                window.treeLayerGroup = L.layerGroup([], { pane: 'treesPane', renderer: L.canvas() }).addTo(map);
            } else {
                window.treeLayerGroup.clearLayers(); // Tetap bersihkan layer jika sudah ada, renderer akan tetap
            }

            // Tambahkan fungsi untuk hanya menampilkan pohon yang terlihat dalam viewport
            function updateVisibleTrees() {
                if (!window.allTreesData || !window.allTreesData.length) return;

                // Dapatkan batas viewport saat ini
                const bounds = map.getBounds();

                // Status update
                statusDiv.textContent = "Memperbarui pohon yang terlihat...";

                // Hapus layer lama secara bertahap untuk menghindari blocking
                window.treeLayerGroup.eachLayer(function(layer) {
                    // Sembunyikan dulu agar tidak memakan resource
                    if (layer.options && typeof layer.setStyle === 'function') {
                        layer.setStyle({opacity: 0, fillOpacity: 0});
                    }
                });

                // Tunggu sebentar lalu bersihkan semua layer
                setTimeout(() => {
                    window.treeLayerGroup.clearLayers();

                    // Filter pohon yang ada di dalam viewport
                    let visibleTrees = [];

                    for (let i = 0; i < window.allTreesData.length; i++) {
                        const tree = window.allTreesData[i];
                        if (!tree.canopy_geometry) continue;

                        try {
                            // Parse WKT hanya untuk mendapatkan koordinat
                    const parsedGeometry = parseWKT(tree.canopy_geometry);
                            if (!parsedGeometry) continue;

                            // Untuk polygon, gunakan titik pertama sebagai referensi
                            let lat, lng;
                            if (parsedGeometry.type === 'polygon' && parsedGeometry.coordinates && parsedGeometry.coordinates.length) {
                                lat = parsedGeometry.coordinates[0][0];
                                lng = parsedGeometry.coordinates[0][1];
                            } else if (parsedGeometry.type === 'point' && parsedGeometry.coordinates) {
                                lat = parsedGeometry.coordinates[0];
                                lng = parsedGeometry.coordinates[1];
                            } else {
                                continue;
                            }

                            // Periksa apakah pohon ada dalam viewport
                            if (bounds.contains([lat, lng])) {
                                visibleTrees.push(tree);
                            }
                        } catch (e) {
                            // Abaikan error
                        }
                    }

                    // Update status
                    statusDiv.textContent = `Memuat ${visibleTrees.length} pohon dari total ${window.allTreesData.length}`;

                    // Tambahkan pohon yang terlihat ke peta
                    addVisibleTreesToMap(visibleTrees);
                }, 50);
            }

            // Fungsi untuk menambahkan pohon yang terlihat ke peta
            function addVisibleTreesToMap(visibleTrees) {
                if (!visibleTrees.length) {
                    // Semua selesai
                    setTimeout(() => {
                        if (document.body.contains(statusDiv)) {
                            document.body.removeChild(statusDiv);
                        }
                    }, 2000);
                        return;
                    }

                // Bagi menjadi batch kecil
                const batchSize = 50;
                const currentBatch = visibleTrees.splice(0, batchSize);

                // Proses batch saat ini
                currentBatch.forEach(tree => {
                    try {
                        if (!tree.canopy_geometry) return;

                        // Parse WKT to Leaflet coordinates secara efisien
                        const parsedGeometry = parseWKT(tree.canopy_geometry);
                        if (!parsedGeometry) return;

                    let layer;

                    // Buat layer berdasarkan tipe geometri
                    if (parsedGeometry.type === 'point') {
                        // Buat marker untuk POINT
                            layer = L.marker(parsedGeometry.coordinates, {
                                // Tambahkan options untuk marker yang lebih efisien
                                interactive: true,
                                pane: 'treesPane' // Tambahkan pane
                            });
                    }
                    else if (parsedGeometry.type === 'polygon') {
                            // Buat polygon untuk POLYGON dengan style minimalis
                            layer = L.polygon(parsedGeometry.coordinates, {
                                weight: 1,
                                opacity: 0.7,
                                fillOpacity: 0.1, // Kurangi fill opacity untuk meningkatkan performa rendering
                                interactive: true, // Pastikan interaktif untuk popup
                                pane: 'treesPane' // Tambahkan pane
                            });
                    }
                    else {
                        return;
                    }

                        // Hanya simpan ID pohon, bukan seluruh objek
                    layer.treeId = tree.id;

                        // Set properti minimum yang diperlukan
                        const requiredProps = {
                            id: tree.id,
                            varietas: tree.varietas,
                            tahun_tanam: tree.tahun_tanam,
                            health_status: tree.health_status,
                            fase: tree.fase
                        };
                        layer.treeData = requiredProps;

                    // Add popup with tree info
                    const popupContent = createPopupContent(tree);

                        // Gunakan popup options untuk meningkatkan performa
                        layer.bindPopup(popupContent, {
                            autoPan: false, // Mencegah panning otomatis yang berat
                            closeButton: true,
                            maxWidth: 250
                        });

                        // Set layer style based on tree health (untuk polygon)
                    if (parsedGeometry.type === 'polygon') {
                        const healthColor = getHealthColor(tree.health_status);
                        layer.setStyle({
                            fillColor: healthColor,
                                color: healthColor
                        });
                    }

                        // Add layer ke layer group khusus pohon
                        window.treeLayerGroup.addLayer(layer);

                    // Simpan layer berdasarkan ID
                    layersById[tree.id] = layer;
                } catch (error) {
                        // Abaikan error individual
                    }
                });

                // Jika masih ada pohon yang perlu diproses, jadwalkan batch berikutnya
                if (visibleTrees.length > 0) {
                    setTimeout(() => addVisibleTreesToMap(visibleTrees), 10);
                } else {
                    // Selesai memproses semua data
                    setTimeout(() => {
                        statusDiv.textContent = `${window.allTreesData.length} data pohon siap dimuat`;

            // Buka kembali popup yang sebelumnya terbuka
            openPopups.forEach(popup => {
                if (popup.id && layersById[popup.id]) {
                    layersById[popup.id].openPopup();
                }
            });

                        // Pastikan layer pohon berada di atas
                ensureTreeLayerOnTop();

                        // Hapus status setelah 2 detik
                        setTimeout(() => {
                            if (document.body.contains(statusDiv)) {
                                document.body.removeChild(statusDiv);
                            }
                        }, 2000);
                    }, 200);
                }
            }

            // Update pohon saat peta bergerak (pan/zoom)
            if (!window.treeViewUpdateAdded) {
                map.on('moveend', updateVisibleTrees);
                window.treeViewUpdateAdded = true;
            }

            // Mulai proses dengan menampilkan pohon yang terlihat
            updateVisibleTrees();
        })
        .catch(error => {
            console.error('Error loading trees:', error);
            if (document.body.contains(statusDiv)) {
                statusDiv.textContent = 'Gagal memuat data';
                statusDiv.style.backgroundColor = '#ffdddd';
                setTimeout(() => document.body.removeChild(statusDiv), 3000);
            }
        });
}

// Function to parse WKT string to Leaflet coordinates - optimized version
function parseWKT(wkt) {
    try {
        if (!wkt) return null;

        // Deteksi dan tangani format EWKT (SRID=X;WKT) dari PostGIS
        let cleanWkt = wkt;

        // Cek apakah ini format EWKT dari PostGIS (SRID=X;WKT)
        if (wkt.toUpperCase().includes('SRID=')) {
            const sridMatch = wkt.match(/SRID=(\d+);(.*)/i);
            if (sridMatch && sridMatch.length > 2) {
                cleanWkt = sridMatch[2];
            }
        }
        // Cek apakah ini hex format dari PostGIS
        else if (/^0\d/.test(cleanWkt)) {
            return {
                type: 'polygon',
                coordinates: [[0, 0], [0, 1], [1, 1], [1, 0], [0, 0]]
            };
        }

        // Membersihkan format WKT
        cleanWkt = cleanWkt.replace(/\s+/g, ' ').trim();
        const upperWkt = cleanWkt.toUpperCase();

        // Cek apakah ini POINT
        if (upperWkt.startsWith('POINT')) {
            // Format: POINT(lng lat)
            const pointMatch = cleanWkt.match(/POINT\s*\(\s*([0-9.-]+)\s+([0-9.-]+)\s*\)/i);
            if (!pointMatch || pointMatch.length < 3) {
                return null;
            }

            const lng = parseFloat(pointMatch[1]);
            const lat = parseFloat(pointMatch[2]);

            if (isNaN(lng) || isNaN(lat)) {
                return null;
            }

            return {
                type: 'point',
                coordinates: [lat, lng],
                geoJson: {
                    type: 'Point',
                    coordinates: [lng, lat]
                }
            };
        }
        // Cek apakah ini POLYGON
        else if (upperWkt.startsWith('POLYGON')) {
            // Extract coordinates string
            let coordsStr = '';
            let polygonCoords = [];

            try {
                if (upperWkt.includes('((')) {
                    // Format standar: POLYGON((lng lat, lng lat, ...))
                    const polygonMatch = cleanWkt.match(/POLYGON\s*\(\s*\((.*?)\)\s*\)/i);
                    if (polygonMatch && polygonMatch.length > 1) {
                        coordsStr = polygonMatch[1];
                } else {
                        throw new Error('Failed to match polygon coordinates');
                    }
                } else if (upperWkt.includes('(')) {
                    // Format alternatif: POLYGON(lng lat, lng lat, ...)
                    const polygonMatch = cleanWkt.match(/POLYGON\s*\(\s*(.*?)\s*\)/i);
                if (polygonMatch && polygonMatch.length > 1) {
                    coordsStr = polygonMatch[1];
                } else {
                        throw new Error('Failed to match polygon coordinates in alternative format');
                    }
                } else {
                    throw new Error('Unrecognized polygon format');
                }

                // Parse koordinat - optimized split operation
                polygonCoords = coordsStr.split(',').map(pair => {
                    const parts = pair.trim().split(/\s+/);
                    if (parts.length < 2) return null;

                const lng = parseFloat(parts[0]);
                const lat = parseFloat(parts[1]);

                    if (isNaN(lng) || isNaN(lat)) return null;

                    return [lat, lng]; // Leaflet format: [lat, lng]
                }).filter(Boolean);

            } catch (e) {
                // Fallback - coba hapus semua format dan buat array koordinat
                try {
                    const strippedWkt = cleanWkt.replace(/POLYGON|\(|\)/gi, '').trim();
                    polygonCoords = strippedWkt.split(',').map(pair => {
                        const parts = pair.trim().split(/\s+/);
                        if (parts.length < 2) return null;
                        const lng = parseFloat(parts[0]);
                        const lat = parseFloat(parts[1]);
                        if (isNaN(lng) || isNaN(lat)) return null;
                        return [lat, lng];
                    }).filter(Boolean);
                } catch (fallbackError) {
                    // Return default polygon
                    return {
                        type: 'polygon',
                        coordinates: [[0, 0], [0, 1], [1, 1], [1, 0], [0, 0]]
                    };
                }
            }

            // Validasi polygon
            if (polygonCoords.length < 3) {
                // Return default polygon
                return {
                    type: 'polygon',
                    coordinates: [[0, 0], [0, 1], [1, 1], [1, 0], [0, 0]]
                };
            }

            // Pastikan polygon tertutup (titik pertama = titik terakhir)
            if (polygonCoords.length > 0 &&
                (polygonCoords[0][0] !== polygonCoords[polygonCoords.length-1][0] ||
                 polygonCoords[0][1] !== polygonCoords[polygonCoords.length-1][1])) {
                polygonCoords.push([polygonCoords[0][0], polygonCoords[0][1]]);
            }

            return {
                type: 'polygon',
                coordinates: polygonCoords
            };
        }
        // Format WKT lain yang belum didukung
        else {
            // Buat polygon default
            return {
                type: 'polygon',
                coordinates: [[0, 0], [0, 1], [1, 1], [1, 0], [0, 0]]
            };
        }
    } catch (error) {
        // Return default polygon on error
        return {
            type: 'polygon',
            coordinates: [[0, 0], [0, 1], [1, 1], [1, 0], [0, 0]]
        };
    }
}

// Function to get color based on health status
function getHealthColor(status) {
    switch (status) {
        case 'Sehat':
            return '#66FFCC'; // Green
        case 'Stres':
            return '#FFFF33'; // Yellow
        case 'Sakit':
            return '#FF6600'; // Orange
        case 'Mati':
            return '#FF0033'; // Red
        default:
            return '#9E9E9E'; // Grey
    }
}

// Load existing trees when the page loads
document.addEventListener('DOMContentLoaded', function() {
    loadExistingTrees();
});

// ------------------------ DELETE STOCK FUNCTION ------------------------ //
function confirmDeleteStock() {
    let stockId = document.querySelector("[x-data]").__x.$data.deleteStockId;

    fetch(`/stok/${stockId}`, {
        method: "DELETE",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
            "Content-Type": "application/json",
        },
    })
    .then(response => {
        if (!response.ok) {
            throw new Error("Gagal menghapus stok");
        }
        return response.json();
    })
    .then(data => {
        alert(data.message); // Notifikasi sukses
        document.getElementById(`stok-row-${stockId}`).remove(); // Hapus baris dari tabel
        document.querySelector("[x-data]").__x.$data.showDeleteModal = false; // Tutup modal
    })
    .catch(error => {
        console.error("Error:", error);
        alert("Terjadi kesalahan saat menghapus stok.");
    });
}

// Image Info Panel Functions
function showImageInfo() {
    const panel = document.getElementById('imageInfoPanel');
    panel.classList.add('visible');

    // Fetch data terbaru
    fetch('/api/latest-aerial-photo')
        .then(response => response.json())
        .then(data => {
            if (data) {
                updateImageInfo(data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function closeImageInfo() {
    const panel = document.getElementById('imageInfoPanel');
    panel.classList.remove('visible');
}

// Function to format date
function formatDate(dateString) {
    const date = new Date(dateString);

    // Format tanggal
    const formattedDate = date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    // Format waktu dengan menyesuaikan timezone
    const hours = (date.getHours() - 7).toString().padStart(2, '0'); // Kurangi 7 jam untuk menyesuaikan dengan WIB
    const minutes = date.getMinutes().toString().padStart(2, '0');
    const formattedTime = `${hours}:${minutes}`;

    return `${formattedDate} ${formattedTime}`;
}

// Tambahkan variable global untuk aerialPhotoLayerGroup di bagian awal file (setelah deklarasi variabel global lain)
// let aerialPhotoLayerGroup = null; // Tidak diperlukan lagi karena menggunakan window.aerialPhotoLayerGroup

// Function to update image info panel
function updateImageInfo(data) {
    document.getElementById('noPhotoInfo').classList.add('hidden');
    document.getElementById('photoInfo').classList.remove('hidden');

    document.getElementById('imageResolution').textContent = data.resolution + ' cm/piksel';
    document.getElementById('imageDateTime').textContent = formatDate(data.capture_time);
    document.getElementById('droneType').textContent = data.drone_type;
    document.getElementById('flightHeight').textContent = data.height + ' meter';
    document.getElementById('imageOverlap').textContent = data.overlap + '%';

    // Removed code that changes the edit link
    // We want the link to always point to aerial.blade.php

    // Display the aerial photo on the map if available
    if (data.path && data.bounds) {
        try {
            // Try to parse bounds
            const bounds = JSON.parse(data.bounds);
            if (bounds && bounds.length === 2) {
                // Create bounds object for leaflet
                const southWest = L.latLng(bounds[0][0], bounds[0][1]);
                const northEast = L.latLng(bounds[1][0], bounds[1][1]);
                const imageBounds = L.latLngBounds(southWest, northEast);

                // Clear existing aerial photos from the layer group
                if (window.aerialPhotoLayerGroup) {
                    window.aerialPhotoLayerGroup.clearLayers();

                    // Create new image overlay and add to layer group
                    const imageOverlay = L.imageOverlay(data.path, imageBounds, {
                        opacity: 0.8,
                        interactive: true
                    });

                    // Add to global layer group
                    imageOverlay.addTo(window.aerialPhotoLayerGroup);

                    // Fit map to bounds, ONLY IF zoom_to_tree is not active
                    const urlParams = new URLSearchParams(window.location.search);
                    const zoomToTreeParam = urlParams.get('zoom_to_tree');
                    if (zoomToTreeParam !== 'true') {
                        map.fitBounds(imageBounds);
                    } else {
                        console.log('Skipping fitBounds for aerial photo due to zoom_to_tree parameter.');
                    }

                    console.log('Aerial photo overlay added with bounds:', bounds);
                } else {
                    console.warn('Aerial photo layer group not initialized');
                }
            }
        } catch (e) {
            console.error('Error displaying aerial photo:', e);
        }
    }
}

// Function to load latest aerial photo
async function loadLatestAerialPhoto() {
    try {
        console.log('Loading latest aerial photo...'); // Debug log
        const response = await fetch('/api/latest-aerial-photo', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        console.log('Latest photo data:', result); // Debug log

        if (result.success && result.data) {
            updateImageInfo(result.data);
        } else {
            document.getElementById('noPhotoInfo').classList.remove('hidden');
            document.getElementById('photoInfo').classList.add('hidden');
        }
    } catch (error) {
        console.error('Error loading aerial photo:', error);
        document.getElementById('noPhotoInfo').classList.remove('hidden');
        document.getElementById('photoInfo').classList.add('hidden');
    }
}

// Event Listeners
document.addEventListener('camera:opened', function() {
    console.log('Camera panel opened'); // Debug log
    loadLatestAerialPhoto();
});

// Jika ada flash message success, reload info panel
document.addEventListener('DOMContentLoaded', function() {
    // Selalu muat foto udara saat halaman dibuka
    loadLatestAerialPhoto();

    // Muat kembali jika ada flash message success
    if (document.body.dataset.flashMessage === 'success') {
        // reload lainnya jika diperlukan
    }
});

// Remove click event from drawnItems that was previously used for editing
drawnItems.off('click');

// Add click event to update URL and show popup when polygon/shape is clicked
drawnItems.on('click', function(e) {
    let layer = e.layer;
    if (!layer) {
        layer = e.target;
    }

    if (layer.feature && layer.feature.properties) {
        const tree = layer.feature.properties;

        // Update URL with tree ID without refreshing the page
        const newUrl = window.location.pathname + '?id=' + tree.id;
        window.history.pushState({ id: tree.id }, '', newUrl);

        // Center the map on the tree location
        const coordinates = layer.getLatLngs()[0];
        const latLng = coordinates[0]; // Assuming the first coordinate is the center
        map.setView(latLng, 23); // Adjust zoom level as needed

        // Show popup with tree information
        const popupContent = createPopupContent(tree);
        layer.bindPopup(popupContent).openPopup();
    }
});

function createPopupContent(tree) {
    // Langsung tampilkan data pohon tanpa placeholder loading

    // Dapatkan role pengguna dari data yang disimpan di window
    const userRole = window.userRole || '';

    // Hitung umur pohon
    const age = tree.tahun_tanam ? (new Date().getFullYear() - tree.tahun_tanam) : '-';

    // Reduksi styling inline untuk efisiensi
    const btnClass = "bg-opacity-90 text-white p-1 rounded-full cursor-pointer w-7 h-7 inline-flex items-center justify-center border-0";

    return `
        <div class="popup-content popup-tree-${tree.id}" style="max-width:220px;font-size:12px;">
            <div style="margin-bottom:8px">
                <strong>ID:</strong> ${tree.id}<br>
                <strong>Varietas:</strong> ${tree.varietas || '-'}<br>
                <strong>Tahun:</strong> ${tree.tahun_tanam || '-'} (${age} thn)<br>
                <strong>Status:</strong> ${tree.health_status || '-'}<br>
                <strong>Fase:</strong> ${tree.fase || '-'}
            </div>
            <div style="text-align:center">
                ${userRole !== 'Guest' ? `
                    <button onclick="editTree('${tree.id}')" class="${btnClass}" style="background:#f97316;margin-right:3px"><i class="fas fa-edit" style="color:white"></i></button>
                    ${userRole !== 'Operasional' ? `
                        <button onclick="deleteTreeById('${tree.id}')" class="${btnClass}" style="background:#ef4444"><i class="fas fa-trash" style="color:white"></i></button>
                    ` : ''}
                    <a href="${document.location.origin}/tree-dashboard?id=${tree.id}" class="${btnClass}" style="background:#3b82f6;margin-left:3px"><i class="fas fa-chart-bar" style="color:white"></i></a>
                ` : ''}
            </div>
        </div>
    `;
}

// Function to handle tree editing
function editTree(treeId) {
    console.log('Editing tree with ID:', treeId);

    // Tampilkan loading
    showLoading('Memuat data pohon...');

    // Tambahkan timestamp untuk mencegah cache
    const timestamp = new Date().getTime();

    // Fetch tree data from the server
    fetch(`/api/trees/${treeId}?_=${timestamp}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Sembunyikan loading
            hideLoading();

            if (!data.success || !data.data) {
                console.error('Failed to fetch tree data:', data);
                alert('Gagal mengambil data pohon. Silakan coba lagi.');
                return;
            }

            const tree = data.data;
            console.log('Fetched tree data:', tree);

            // Pastikan data pohon memiliki semua field yang diperlukan
            tree.plantation_id = tree.plantation_id || getDefaultPlantationId();
            tree.varietas = tree.varietas || 'Tidak Diketahui';
            tree.tahun_tanam = tree.tahun_tanam || getCurrentYear();
            tree.health_status = tree.health_status || 'Sehat';

            // Determine shape type from geometry
            let shapeType = 'Polygon'; // Default

            if (tree.canopy_geometry && tree.canopy_geometry.toUpperCase().startsWith('POINT')) {
                shapeType = 'Marker';
            } else if (tree.canopy_geometry && tree.canopy_geometry.toUpperCase().startsWith('POLYGON')) {
                shapeType = 'Polygon';
            }

            console.log('Determined shape type:', shapeType);

            // Show the tree modal with the tree data
            showTreeModal(true, tree, null, shapeType);
        })
        .catch(error => {
            // Sembunyikan loading
            hideLoading();

            console.error('Error fetching tree data:', error);
            alert('Terjadi kesalahan saat mengambil data pohon: ' + error.message);
        });
}

// Add event listener for form submit
document.addEventListener('DOMContentLoaded', function() {
    const treeForm = document.getElementById('treeForm');
    if (treeForm) {
        treeForm.addEventListener('submit', function(e) {
            // This event listener is no longer needed as form submission is handled by Alpine.js
            e.preventDefault();
        });
    }

    // Additional event listeners and functions
    // Ensure all other relevant code is wrapped in this listener
    const editTreeForm = document.getElementById('editTreeForm');
    if (editTreeForm) {
        editTreeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            // This event listener is no longer needed as form submission is handled by Alpine.js
        });
    }

    // Load existing trees when the page loads
    loadExistingTrees();
});

// Check if there's an ID in the URL and center the map accordingly
var urlParams = new URLSearchParams(window.location.search);
var treeId = urlParams.get('id');
var plantationId = urlParams.get('plantation_id');

//untuk ngontrol lokasi dan view dari id pohon
if (treeId) {
    fetch(`/api/trees/${treeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tree = data.data;
                const latLng = [tree.latitude, tree.longitude]; // Use latitude and longitude directly
                map.setView(latLng, 23); // Adjust zoom level as needed
            } else {
                console.error('Tree not found:', data.message);
            }
        })
        .catch(error => console.error('Error fetching tree data:', error));
}
//untuk ngontrol lokasi dan view dari id blok kebun
else if (plantationId) {
    fetch(`/api/plantations/${plantationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const plantation = data.data;
                try {
                    // Coba temukan polygon atau marker pada layer map
                    if (plantationLayers && plantationLayers.getLayers) {
                        const layers = plantationLayers.getLayers();
                        let found = false;

                        // Cari layer dengan ID yang sesuai
                        for (let layer of layers) {
                            if (layer.plantationId == plantationId) {
                                // Jika ditemukan, zoom ke batas layer
                                map.fitBounds(layer.getBounds());
                                found = true;
                                break;
                            }
                        }

                        if (!found && plantation.latitude && plantation.longitude) {
                            // Jika tidak ditemukan layer tapi ada koordinat, gunakan itu
                            map.setView([plantation.latitude, plantation.longitude], 18);
                        }
                    } else if (plantation.latitude && plantation.longitude) {
                        // Fallback ke koordinat langsung jika plantationLayers tidak ada
                        map.setView([plantation.latitude, plantation.longitude], 18);
                    }
                } catch (err) {
                    console.error('Error zooming to plantation:', err);
                    // Fallback ke batas peta default jika terjadi error
                    map.setView([-6.9175, 106.9277], 13);
                }
            } else {
                console.error('Plantation not found:', data.message);
            }
        })
        .catch(error => console.error('Error fetching plantation data:', error));
}

// Event listener untuk event pm:create (untuk kompatibilitas)
window.addEventListener('pm:create', function(event) {
    console.log('pm:create event received in global listener:', event);

    // Tampilkan modal
    if (event.detail) {
        showTreeModal(event.detail.isEdit || false, event.detail.treeData || null);

        // Set geometry ke hidden input
        if (event.detail.wkt) {
            const geometryInput = document.getElementById('canopy_geometry');
            if (geometryInput) {
                geometryInput.value = event.detail.wkt;
                console.log('canopy_geometry set to:', event.detail.wkt);
            } else {
                console.error('canopy_geometry input not found!');
            }
        }
    }
});

// Event listener untuk event open-tree-modal
window.addEventListener('open-tree-modal', function(event) {
    console.log('open-tree-modal event received:', event);

    // Reset form terlebih dahulu jika bukan mode edit
    const form = document.getElementById('treeForm');
    const isEditMode = event.detail?.isEdit || false;
    const treeData = event.detail?.treeData || null;
    const geometryWkt = event.detail?.geometryWkt || null;
    const shapeType = event.detail?.shapeType || 'Polygon';

    console.log('Modal data:', { isEditMode, treeData, geometryWkt, shapeType });

    if (form) {
        if (!isEditMode) {
            form.reset();
        }
    }

    // Set nilai geometri pada input hidden
    const geometryInput = document.getElementById('canopy_geometry');
    if (geometryInput) {
        if (isEditMode && treeData && (treeData.canopy_geometry_wkt || treeData.canopy_geometry) && !geometryWkt) {
            // Jika mode edit, gunakan WKT dari treeData
            geometryInput.value = treeData.canopy_geometry_wkt || treeData.canopy_geometry;
            console.log('canopy_geometry set to (from treeData):', geometryInput.value);
        } else if (geometryWkt) {
            // Gunakan geometri dari bentuk yang baru dibuat
            geometryInput.value = geometryWkt;
            console.log('canopy_geometry set to (from geometryWkt):', geometryWkt);
        }
    }

    // Set tipe bentuk pada input hidden
    const shapeTypeInput = document.getElementById('shape_type');
    if (shapeTypeInput) {
        shapeTypeInput.value = shapeType;
        console.log('shape_type set to:', shapeType);
    } else if (form) {
        // Buat input hidden untuk shape_type jika belum ada
        const input = document.createElement('input');
        input.type = 'hidden';
        input.id = 'shape_type';
        input.name = 'shape_type';
        input.value = shapeType;
        form.appendChild(input);
        console.log('shape_type input created with value:', shapeType);
    }
});

// Function to show loading indicator
function showLoading(message = 'Loading...') {
    // Cek apakah elemen loading sudah ada
    let loadingEl = document.getElementById('loadingIndicator');

    // Jika belum ada, buat elemen baru
    if (!loadingEl) {
        loadingEl = document.createElement('div');
        loadingEl.id = 'loadingIndicator';
        loadingEl.className = 'fixed top-0 left-0 w-full h-full flex items-center justify-center bg-black bg-opacity-50 z-50';
        loadingEl.innerHTML = `
            <div class="bg-white p-4 rounded-lg shadow-lg text-center">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-green-700 mx-auto mb-2"></div>
                <p id="loadingMessage" class="text-gray-700"></p>
            </div>
        `;
        document.body.appendChild(loadingEl);
    }

    // Set pesan loading
    const messageEl = document.getElementById('loadingMessage');
    if (messageEl) {
        messageEl.textContent = message;
    }

    // Tampilkan loading
    loadingEl.style.display = 'flex';
}

// Function to hide loading indicator
function hideLoading() {
    const loadingEl = document.getElementById('loadingIndicator');
    if (loadingEl) {
        loadingEl.style.display = 'none';
    }
}

// Event handler untuk menambah blok kebun baru
map.on('draw:created', function(e) {
    const layer = e.layer;
    const type = e.layerType;

    // Convert geometry to WKT
    const geoJson = layer.toGeoJSON();
    const wkt = Terraformer.WKT.convert(geoJson.geometry);

    // Dispatch event untuk membuka modal dengan informasi layer
    window.dispatchEvent(new CustomEvent('open-plantation-modal', {
        detail: {
            isEdit: false,
            geometryWkt: wkt,
            layer: layer
        }
    }));
});

// Function untuk menghitung luas area dalam hektar
function calculateAreaInHectares(layer) {
    try {
        // Get GeoJSON dari layer
        const geoJson = layer.toGeoJSON();

        // Hitung luas menggunakan turf.js
        const areaInSquareMeters = turf.area(geoJson);

        // Konversi ke hektar (1 hektar = 10000 m²)
        const areaInHectares = areaInSquareMeters / 10000;

        // Bulatkan ke 2 angka desimal
        return Math.round(areaInHectares * 100) / 100;
    } catch (error) {
        console.error('Error calculating area:', error);
        return null;
    }
}

// Function untuk menghapus plantation berdasarkan ID
function deletePlantationById(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus blok kebun ini?')) {
        return;
    }

    console.log('Deleting plantation with ID:', id);

    // Tampilkan loading
    if (typeof showLoading === 'function') {
        showLoading('Menghapus data blok kebun...');
    }

    // Kirim permintaan penghapusan ke API
    fetch(`/api/plantations/${id}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        // Coba parse respons terlebih dahulu, bahkan jika status tidak OK
        return response.json()
            .then(data => {
                // Jika response tidak OK, throw error dengan message dari API
                if (!response.ok) {
                    throw new Error(data.message || `Server error (${response.status})`);
                }
                return data;
            })
            .catch(err => {
                // Jika gagal parse JSON, gunakan error default
                if (!response.ok) {
                    throw new Error(`Network response was not ok (${response.status})`);
                }
                throw err;
            });
    })
    .then(data => {
        // Sembunyikan loading
        if (typeof hideLoading === 'function') {
            hideLoading();
        }

        if (data.success) {
            console.log('Plantation deleted successfully:', data.deleted_id || id);

            // Hapus layer dari map dan layer group - gunakan ID dari response jika tersedia
            const deletedId = data.deleted_id || id;

            // Pastikan ID dalam format yang benar (string atau number)
            const layerId = parseInt(deletedId);
            console.log('Attempting to remove layer with ID:', layerId);

            // Hapus layer dengan lebih teliti
            removeLayerById(layerId);

            // Tunggu sebentar untuk memastikan layer telah dihapus
            setTimeout(() => {
                // Tambahan: Hapus juga dari layer control jika ada
                if (window.layerControl) {
                    try {
                        console.log('Refreshing layer control after deletion');
                        if (window.plantationLayerGroup) {
                            window.layerControl.removeLayer(window.plantationLayerGroup);
                            window.layerControl.addOverlay(window.plantationLayerGroup, 'Blok Kebun');
                        }
                    } catch (lcError) {
                        console.warn('Error updating layer control after deletion:', lcError);
                    }
                }

                // Force invalidate size dan redraw map
                if (map) {
                    console.log('Forcing map redraw after deletion');
                    map.invalidateSize({ animate: false });
                }
            }, 100);

            // Tambahan: Trigger custom event untuk komponen lain yang mungkin perlu update
            window.dispatchEvent(new CustomEvent('plantation-deleted', {
                detail: { id: deletedId }
            }));

            // Hapus juga dari struktur data lain yang mungkin menyimpan referensi
            if (window.plantationLayers) {
                delete window.plantationLayers[deletedId];
                console.log('Deleted reference from plantationLayers');
            }

            if (window.plantationLayersMap) {
                delete window.plantationLayersMap[deletedId];
                console.log('Deleted reference from plantationLayersMap');
            }

            // Tambahan: Cek di koleksi layers lain
            if (map) {
                console.log('Checking all map layers for plantation ID:', deletedId);
                map.eachLayer(function(layer) {
                    if (layer.plantationData && layer.plantationData.id == deletedId) {
                        console.log('Found additional layer to remove:', layer);
                        map.removeLayer(layer);
                    }
                });
            }

            // Refresh semua plantation dari server untuk memastikan sinkronisasi data
            setTimeout(() => {
                console.log('Refreshing all plantation layers after deletion');
                refreshPlantationLayers();
            }, 300);

            // Tampilkan pesan sukses
            alert('Blok kebun berhasil dihapus!');
        } else {
            console.error('Failed to delete plantation:', data.message);
            alert('Gagal menghapus blok kebun: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error deleting plantation:', error);

        // Sembunyikan loading
        if (typeof hideLoading === 'function') {
            hideLoading();
        }

        // Tampilkan pesan error yang lebih detail
        alert('Gagal menghapus blok kebun: ' + error.message);
    });
}

// Fungsi untuk menghapus layer kebun dari peta berdasarkan ID
function removeLayerById(id) {
    console.log('Removing plantation layer by ID:', id);

    try {
        let layerRemoved = false;

        // 1. Cek di plantationLayers (objek global)
        if (window.plantationLayers && window.plantationLayers[id]) {
            const layer = window.plantationLayers[id];
            console.log('Found layer in plantationLayers:', layer);

            // Hapus dari layer group jika ada
            if (window.plantationLayerGroup && window.plantationLayerGroup.hasLayer(layer)) {
                console.log('Removing layer from plantation layer group');
                window.plantationLayerGroup.removeLayer(layer);
                layerRemoved = true;
            }

            // Hapus dari map jika langsung ditambahkan ke map
            if (map && map.hasLayer(layer)) {
                console.log('Removing layer directly from map');
                map.removeLayer(layer);
                layerRemoved = true;
            }

            // Hapus dari objek global
            delete window.plantationLayers[id];
            console.log('Deleted layer reference from plantationLayers');
        }

        // 2. Cek di plantationLayersMap (objek global lain)
        if (window.plantationLayersMap && window.plantationLayersMap[id]) {
            const layer = window.plantationLayersMap[id];
            console.log('Found layer in plantationLayersMap:', layer);

            if (map && map.hasLayer(layer)) {
                console.log('Removing layer from map via plantationLayersMap');
                map.removeLayer(layer);
                layerRemoved = true;
            }

            delete window.plantationLayersMap[id];
            console.log('Deleted layer reference from plantationLayersMap');
        }

        // 3. Iterasi semua layer di map untuk mencari layer dengan ID yang cocok
        if (map) {
            map.eachLayer(function(layer) {
                // Cek apakah layer ini memiliki properti plantationData dengan ID yang cocok
                if (layer.plantationData && (layer.plantationData.id == id || layer.plantationData.id == String(id))) {
                    console.log('Found plantation layer by plantationData.id:', layer);
                    map.removeLayer(layer);
                    layerRemoved = true;
                }

                // Cek properti options.id
                if (layer.options && layer.options.id && (layer.options.id == id || layer.options.id == String(id))) {
                    console.log('Found plantation layer by options.id:', layer);
                    map.removeLayer(layer);
                    layerRemoved = true;
                }

                // Coba mencari berdasarkan ID dalam konten popup jika ada
                if (layer._popup && layer._popup._content && layer._popup._content.includes(`id="${id}"`)) {
                    console.log('Found layer via popup content containing ID:', id);
                    map.removeLayer(layer);
                    layerRemoved = true;
                }
            });
        }

        // 4. Jika layerGroup ada, iterasi semua layer di dalamnya
        if (window.plantationLayerGroup) {
            window.plantationLayerGroup.eachLayer(function(layer) {
                if (layer.plantationData && (layer.plantationData.id == id || layer.plantationData.id == String(id))) {
                    console.log('Found plantation layer in layerGroup by plantationData.id:', layer);
                    window.plantationLayerGroup.removeLayer(layer);
                    layerRemoved = true;
                }

                if (layer.options && layer.options.id && (layer.options.id == id || layer.options.id == String(id))) {
                    console.log('Found plantation layer in layerGroup by options.id:', layer);
                    window.plantationLayerGroup.removeLayer(layer);
                    layerRemoved = true;
                }

                // Coba mendeteksi dari properties lain
                if (layer.feature && layer.feature.properties && layer.feature.properties.id == id) {
                    console.log('Found plantation layer in layerGroup by feature.properties.id:', layer);
                    window.plantationLayerGroup.removeLayer(layer);
                    layerRemoved = true;
                }
            });
        }

        // 5. Terakhir, jika layer masih belum ditemukan, coba dengan strategi darurat:
        // hapus dan rebuild seluruh plantationLayerGroup
        if (!layerRemoved && window.plantationLayerGroup) {
            console.warn('Layer not found by normal means, clearing all plantation layers');
            window.plantationLayerGroup.clearLayers();

            // Memulai ulang dari data yang ada secara sinkron, kecuali yang ID-nya cocok dengan yang dihapus
            if (window.plantationLayers) {
                for (const plantationId in window.plantationLayers) {
                    if (plantationId != id) {
                        const layer = window.plantationLayers[plantationId];
                        if (layer) {
                            layer.addTo(window.plantationLayerGroup);
                        }
                    }
                }
            }
        }

        if (!layerRemoved) {
            console.warn('No layer found to remove with ID:', id);
        } else {
            console.log('Successfully removed layer(s) for plantation ID:', id);
        }

        // 6. Update layer control jika ada
        if (window.layerControl) {
            try {
                console.log('Refreshing layer control');
                if (window.plantationLayerGroup) {
                    window.layerControl.removeLayer(window.plantationLayerGroup);
                    window.layerControl.addOverlay(window.plantationLayerGroup, 'Blok Kebun');
                }
            } catch (lcError) {
                console.warn('Error updating layer control:', lcError);
            }
        }

        // 7. Force redraw/refresh map untuk memastikan perubahan terlihat
        if (map) {
            map.invalidateSize({ animate: false });
            console.log('Map refreshed after layer removal');
        }
    } catch (error) {
        console.error('Error removing layer with ID', id, error);
    }

    // Trigger event bahwa layer telah dihapus (untuk komponen lain yang mungkin perlu update)
    window.dispatchEvent(new CustomEvent('plantation-layer:removed', {
        detail: { id: id }
    }));
}

// Load existing plantations when the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Tunggu map siap
    document.addEventListener('map:ready', function() {
        // Tunggu sebentar untuk memastikan semua komponen siap
        setTimeout(function() {
            loadExistingPlantations();
        }, 1000);
    });
});

// Event listener untuk geoman:ready
document.addEventListener('geoman:ready', function() {
    console.log('geoman:ready event received, adding event handlers');
    addPmCreateHandler();
});

// Event listener untuk open-plantation-modal
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM content loaded, adding form listeners');

    // Function untuk memeriksa dan menambahkan event listener ke tombol simpan plantation
    function addSavePlantationButtonListener() {
        const saveButton = document.getElementById('save-plantation-btn');
        if (saveButton) {
            console.log('Adding click event listener to save-plantation-btn');
            saveButton.addEventListener('click', function(e) {
                e.preventDefault();
                submitPlantationForm();
            });
            return true;
        }
        return false;
    }

    // Coba tambahkan event listener ke tombol simpan
    if (!addSavePlantationButtonListener()) {
        // Jika tombol belum ada, coba lagi setelah delay
        setTimeout(() => {
            if (!addSavePlantationButtonListener()) {
                console.log('save-plantation-btn not found after delay');
            }
        }, 500);
    }

    // Tambahkan event listener ke tombol-tombol modal pilihan form
    window.addEventListener('open-form-option-modal', function(event) {
        console.log('open-form-option-modal event received');
    });

    window.addEventListener('open-plantation-modal', function(event) {
        console.log('open-plantation-modal event received:', event.detail);

        // Set nilai di form
        setTimeout(() => {
            // Dapatkan detail dari event
        const isEditMode = event.detail?.isEdit || false;
        const plantationData = event.detail?.plantationData || null;
            const geometryWkt = event.detail?.geometryWkt || null;

            console.log('Opening plantation modal:', {
                isEditMode,
                plantationData,
                geometryWkt
            });

            // Reset form terlebih dahulu
            const form = document.getElementById('plantationForm');
            if (form) {
                console.log('Resetting form');
                form.reset();
            }

            // Perbarui method form berdasarkan mode
            const formMethodInput = document.getElementById('plantation_form_method');
            if (formMethodInput) {
                formMethodInput.value = isEditMode ? 'PUT' : 'POST';
                console.log('Form method set to:', formMethodInput.value);
            }

            // Ambil semua elemen input yang diperlukan
            const idInput = document.getElementById('plantation_id');
            const nameInput = document.getElementById('name');
            const geometryInput = document.getElementById('boundary_geometry');
            const areaInput = document.getElementById('luas_area');
            const tipeTanahInput = document.getElementById('tipe_tanah');
            const displayIdInput = document.getElementById('display_plantation_id');
            const userRole = window.userRole || '';

            // Jika mode edit, isi form dengan data yang ada
            if (isEditMode && plantationData) {
                console.log('Filling form with plantation data:', plantationData);

                // Set plantation ID
                if (idInput && plantationData.id) {
                    idInput.value = plantationData.id;
                    console.log('ID set to:', plantationData.id);

                    // Jika ada display ID, isi juga
                    if (displayIdInput) {
                        displayIdInput.value = plantationData.id;
                        if (userRole === 'Operasional') {
                            displayIdInput.readOnly = true;
                            displayIdInput.style.backgroundColor = '#f3f4f6'; // Warna abu-abu muda
                        } else {
                            displayIdInput.readOnly = false;
                            displayIdInput.style.backgroundColor = '';
                        }
                    }
                }

                // Set nama kebun
                if (nameInput && plantationData.name) {
                    nameInput.value = plantationData.name;
                    console.log('Name set to:', plantationData.name);
                }

                // Set geometry
                if (geometryInput && plantationData.geometry) {
                    geometryInput.value = plantationData.geometry;
                    console.log('Geometry set from plantation data:', plantationData.geometry.substring(0, 50) + '...');
                }

                // Set luas area - pertama gunakan nilai dari database, kemudian hitung ulang jika perlu
                if (areaInput) {
                    if (plantationData.luas_area) {
                        const luas = parseFloat(plantationData.luas_area);
                        areaInput.value = isNaN(luas) ? 0 : luas.toFixed(4);
                        console.log('Area set from database:', areaInput.value);
                    } else if (geometryInput && geometryInput.value) {
                        // Jika tidak ada nilai area, hitung dari geometri
                        const area = calculateAreaFromWkt(geometryInput.value);
                        if (area > 0) {
                            areaInput.value = area.toFixed(4);
                            console.log('Area calculated from geometry:', area.toFixed(4), 'ha');
                        }
                    }
                }

                // Set tipe tanah
                if (tipeTanahInput) {
                    tipeTanahInput.value = plantationData.tipe_tanah || '';
                    console.log('Tipe tanah set to:', plantationData.tipe_tanah || '(kosong)');
                }
            }
            // Jika mode baru
            else {
                console.log('Setting up new plantation form');

                // Reset ID
                if (idInput) {
                    idInput.value = '';
                }

                // Set geometri dari event atau currentShapeData
                if (geometryInput) {
                    let geometryValue = null;

                    if (geometryWkt) {
                        // Gunakan geometri dari event
                        geometryValue = ensureWktFormat(geometryWkt);
                        geometryInput.value = geometryValue;
                        console.log('Setting geometry from event data:', geometryValue.substring(0, 50) + '...');
                    }
                    else if (window.currentShapeData && window.currentShapeData.wkt) {
                        // Gunakan geometri dari bentuk yang dibuat dengan Geoman
                        geometryValue = ensureWktFormat(window.currentShapeData.wkt);
                        geometryInput.value = geometryValue;
                        console.log('Setting geometry from currentShapeData:', geometryValue.substring(0, 50) + '...');
                    }

                    // Log nilai geometri yang diatur
                    if (geometryInput.value) {
                        console.log('Final geometry value set in form:', geometryInput.value.substring(0, 50) + '...');

                        // Hitung dan isi luas area secara otomatis
                        if (areaInput) {
                            console.log('Calculating area from geometry input');
                            // Gunakan fungsi calculateAreaFromWkt yang telah ditingkatkan
                            const area = calculateAreaFromWkt(geometryInput.value);
                            if (area > 0) {
                                areaInput.value = area.toFixed(4);
                                console.log('Area automatically calculated and filled:', area.toFixed(4), 'hectares');
                            } else {
                                console.warn('Area calculation returned zero or error');
                                // Reset area dan beri pesan alert
                                areaInput.value = '0.0000';
                                alert('Perhitungan luas area gagal. Silakan masukkan luas area secara manual.');
                            }
                        } else {
                            console.error('Area input element not found');
                        }
                    } else {
                        console.warn('No geometry value set in form');
                    }
                } else {
                    console.error('Boundary geometry input element not found in the form');
                }
            }

            // Periksa sekali lagi jika area masih kosong, coba hitung
            if (areaInput && (!areaInput.value || parseFloat(areaInput.value) === 0) && geometryInput && geometryInput.value) {
                console.log('Area still empty or zero, calculating again');
                const area = calculateAreaFromWkt(geometryInput.value);
                if (area > 0) {
                    areaInput.value = area.toFixed(4);
                    console.log('Area recalculated:', area.toFixed(4), 'ha');
                }
            }

            // Berikan feedback visual setelah form diisi
            console.log('Plantation form ready with fields:');
            console.log('- ID:', idInput ? idInput.value : 'N/A');
            console.log('- Name:', nameInput ? nameInput.value : 'N/A');
            console.log('- Area:', areaInput ? areaInput.value : 'N/A');
            console.log('- Geometry length:', geometryInput ? geometryInput.value.length : 'N/A');
            console.log('- Tipe tanah:', tipeTanahInput ? tipeTanahInput.value : 'N/A');
        }, 300);
    });

    // Tunggu map siap
    document.addEventListener('map:ready', function() {
        // Tunggu sebentar untuk memastikan semua komponen siap
        setTimeout(function() {
            loadExistingPlantations();
        }, 1000);
    });

    // Tunggu sebentar untuk memastikan map dan Geoman sudah dimuat
    setTimeout(checkAndAddEventHandlers, 500);
});

// Function to handle tree editing

// Fungsi untuk membatalkan shape yang baru dibuat
function cancelShape() {
    console.log('Cancel shape called');

    // Tutup semua modal yang mungkin terbuka
    window.dispatchEvent(new CustomEvent('close-form-selector-modal'));
    window.dispatchEvent(new CustomEvent('close-tree-modal'));
    window.dispatchEvent(new CustomEvent('close-plantation-modal'));

    try {
        // Hapus layer dari peta jika ada
        if (currentShapeData && currentShapeData.layer) {
            console.log('Removing shape layer from map, currentShapeType:', currentShapeType);

            // Hapus dari layer group yang sesuai berdasarkan currentShapeType
            if (currentShapeType === 'plantation') {
                // Hapus dari plantationLayerGroup jika tersedia
                if (window.plantationLayerGroup && window.plantationLayerGroup.hasLayer(currentShapeData.layer)) {
                    window.plantationLayerGroup.removeLayer(currentShapeData.layer);
                    console.log('Layer removed from plantation layer group');
                }
            } else {
                // Default: Hapus dari drawnItems (layer pohon)
                if (typeof drawnItems !== 'undefined' && drawnItems.hasLayer(currentShapeData.layer)) {
                    drawnItems.removeLayer(currentShapeData.layer);
                    console.log('Layer removed from drawnItems (tree layer)');
                }
            }

            // Hapus langsung dari map sebagai backup
            if (map && map.hasLayer(currentShapeData.layer)) {
                map.removeLayer(currentShapeData.layer);
                console.log('Layer removed directly from map');
            }
        } else {
            console.log('No layer to remove or layer already removed');
        }

        // Reset currentShapeData dan currentShapeType
        currentShapeData = null;
        currentShapeType = null;
        console.log('currentShapeData and currentShapeType reset to null');

        // Re-enable tombol geoman
        if (map && map.pm) {
            map.pm.enableDraw();
        }
    } catch (error) {
        console.error('Error in cancelShape:', error);
    }
}

// Event listener untuk DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM content loaded, adding form listeners');

    // Function untuk memeriksa dan menambahkan event listener ke tombol simpan plantation
    function addSavePlantationButtonListener() {
        const saveButton = document.getElementById('save-plantation-btn');
        if (saveButton) {
            console.log('Adding click event listener to save-plantation-btn');
            saveButton.addEventListener('click', function(e) {
                e.preventDefault();
                submitPlantationForm();
            });
            return true;
        }
        return false;
    }

    // Coba tambahkan event listener ke tombol simpan
    if (!addSavePlantationButtonListener()) {
        // Jika tombol belum ada, coba lagi setelah delay
        setTimeout(() => {
            if (!addSavePlantationButtonListener()) {
                console.log('save-plantation-btn not found after delay');
            }
        }, 500);
    }

    // Tambahkan event listener ke tombol-tombol modal pilihan form
    window.addEventListener('open-form-option-modal', function(event) {
        console.log('open-form-option-modal event received');
    });
});

// Fungsi untuk memeriksa dan menambahkan event handlers
function checkAndAddEventHandlers() {
    // Periksa apakah Geoman sudah diinisialisasi
    if (isGeomanInitialized || (typeof L !== 'undefined' && typeof L.PM !== 'undefined' && map && map.pm)) {
        console.log('Geoman is ready, adding event handlers');
        addPmCreateHandler();

        // Set status inisialisasi jika belum
        if (!isGeomanInitialized) {
            isGeomanInitialized = true;
        }
    } else {
        // Coba lagi setelah jeda waktu
        console.log('Geoman not ready yet, will try again in 500ms');
        setTimeout(checkAndAddEventHandlers, 500);
    }
}

// Mulai pengecekan setelah DOM selesai dimuat
document.addEventListener('DOMContentLoaded', function() {
    // Tunggu sebentar untuk memastikan map dan Geoman sudah dimuat
    setTimeout(checkAndAddEventHandlers, 500);
});

// Tambahkan event listener untuk geoman:ready
document.addEventListener('geoman:ready', function() {
    console.log('geoman:ready event received, adding event handlers');
    addPmCreateHandler();
});

// Fungsi untuk menampilkan modal form pohon
function showTreeModal(isEdit = false, treeData = null, geometryWkt = null, shapeType = 'Polygon') {
    console.log('showTreeModal called with isEdit:', isEdit, 'treeData:', treeData, 'geometryWkt:', geometryWkt, 'shapeType:', shapeType);

    try {
        // Pastikan modal-modal lain tertutup terlebih dahulu
        window.dispatchEvent(new CustomEvent('close-form-selector-modal'));
        window.dispatchEvent(new CustomEvent('close-plantation-modal'));

        // Tunggu sebentar untuk memastikan modal lain tertutup
        setTimeout(() => {
            // Reset form jika bukan mode edit
            if (!isEdit) {
                const treeForm = document.getElementById('treeForm');
                if (treeForm) {
                    treeForm.reset();
                    console.log('Tree form reset');
                } else {
                    console.warn('Tree form not found');
                }
            }

            // Set action dan method form berdasarkan mode (edit atau tambah)
            const form = document.getElementById('treeForm');
            if (!form) {
                console.error('Form not found!');
                return;
            }

            // Dapatkan role pengguna
            const userRole = window.userRole || '';

            // Set form method dan action
            if (isEdit && treeData) {
                // Untuk edit, gunakan URL dengan ID
                form.action = `/api/trees/${treeData.id}`;

                // Set method input untuk PUT
                const methodInput = document.getElementById('form_method');
                if (methodInput) {
                    methodInput.value = 'PUT';
                    console.log('Form method set to PUT');
                }

                // Set form_mode untuk mode edit
                const formModeInput = document.getElementById('form_mode');
                if (formModeInput) {
                    formModeInput.value = 'update';
                    console.log('Form mode set to update');
                }

                // Set tree_id (hidden) untuk referensi internal
                const treeIdInput = document.getElementById('tree_id');
                if (treeIdInput) {
                    treeIdInput.value = treeData.id;
                    console.log('Tree ID set to:', treeData.id);
                }

                // Set display_id untuk tampilan
                const displayIdInput = document.getElementById('display_id');
                if (displayIdInput) {
                    displayIdInput.value = treeData.id;
                    console.log('Display ID set to:', treeData.id);
                    if (userRole === 'Operasional') {
                        displayIdInput.readOnly = true;
                        displayIdInput.style.backgroundColor = '#f3f4f6'; // Warna abu-abu muda
                    } else {
                        displayIdInput.readOnly = false;
                        displayIdInput.style.backgroundColor = '';
                    }
                }

                // Isi form dengan data pohon
                fillFormWithTreeData(treeData);
            } else {
                // Untuk tambah, gunakan URL default
                form.action = '/api/trees';

                // Set method input untuk POST
                const methodInput = document.getElementById('form_method');
                if (methodInput) {
                    methodInput.value = 'POST';
                    console.log('Form method set to POST');
                }

                // Set form_mode untuk mode create
                const formModeInput = document.getElementById('form_mode');
                if (formModeInput) {
                    formModeInput.value = 'create';
                    console.log('Form mode set to create');
                }

                // Reset tree_id
                const treeIdInput = document.getElementById('tree_id');
                if (treeIdInput) {
                    treeIdInput.value = '';
                    console.log('Tree ID reset');
                }

                // Pastikan field custom_id kosong untuk penciptaan baru
                const customIdInput = document.getElementById('custom_id');
                if (customIdInput) {
                    customIdInput.value = '';
                    if (userRole === 'Operasional') {
                        customIdInput.readOnly = true;
                        customIdInput.style.backgroundColor = '#f3f4f6'; // Warna abu-abu muda
                    } else {
                        customIdInput.readOnly = false;
                        customIdInput.style.backgroundColor = '';
                    }
                }

                // Set nilai default untuk plantation_id
                const plantationIdInput = document.getElementById('plantation_id');
                if (plantationIdInput) {
                    plantationIdInput.value = getDefaultPlantationId();
                    console.log('Default plantation_id set to:', getDefaultPlantationId());
                }

                // Set nilai default untuk tahun_tanam
                const tahunTanamInput = document.getElementById('tahun_tanam');
                if (tahunTanamInput) {
                    tahunTanamInput.value = getCurrentYear();
                    console.log('Default tahun_tanam set to:', getCurrentYear());
                }

                // Set nilai default untuk health_status
                const healthStatusInput = document.getElementById('health_status');
                if (healthStatusInput) {
                    healthStatusInput.value = 'Sehat';
                    console.log('Default health_status set to: Sehat');
                }

                // Set nilai default untuk fase
                const faseInput = document.getElementById('fase');
                if (faseInput) {
                    faseInput.value = 'Vegetatif';
                    console.log('Default fase set to: Vegetatif');
                }

                // Set geometri jika ada
                if (geometryWkt) {
                    const geometryInput = document.getElementById('canopy_geometry');
                    if (geometryInput) {
                        geometryInput.value = geometryWkt;
                        console.log('Geometry set to:', geometryWkt);
                    }
                }
            }

            // Tampilkan modal pohon
            window.dispatchEvent(new CustomEvent('open-tree-modal', {
                detail: {
                    isEdit: isEdit,
                    treeData: treeData
                }
            }));

            console.log('Tree modal opened successfully');
        }, 300); // Delay untuk memastikan modal lain benar-benar tertutup
    } catch (error) {
        console.error('Error opening tree modal:', error);
    }
}

// Helper function to get default plantation_id
function getDefaultPlantationId() {
    // Coba ambil nilai dari select plantation_id
    const plantationSelect = document.getElementById('plantation_id');
    if (plantationSelect && plantationSelect.options.length > 0) {
        return plantationSelect.options[0].value;
    }
    return '1'; // Default value jika tidak ada opsi
}

// Helper function to get current year
function getCurrentYear() {
    return new Date().getFullYear().toString();
}

// Helper function to set form value if element exists
function setFormValueIfExists(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.value = value || '';
        console.log(`Set ${id} to:`, value);
    } else {
        console.warn(`Element with id ${id} not found`);
    }
}

// Fungsi untuk menutup modal
function closeTreeModal() {
    console.log('Closing tree modal');

    // Dapatkan nilai dari input hidden canopy_geometry
    const geometryWkt = document.getElementById('canopy_geometry').value;
    const formMode = document.getElementById('form_mode').value;

    // Jika mode form adalah create (bukan edit) dan user membatalkan form
    if (formMode === 'create') {
        console.log('Form was in create mode, checking for unsaved layers');

        // Cari layer yang belum disimpan di drawnItems
        drawnItems.eachLayer(function(layer) {
            if (!layer.isSaved) {
                console.log('Found unsaved layer, removing it from map');
                drawnItems.removeLayer(layer);
            }
        });
    }

    // Kirim event untuk menutup modal
    window.dispatchEvent(new CustomEvent('close-tree-modal'));
}

// Fungsi untuk beralih antar tab
function switchTab(tabNumber) {
    console.log('switchTab called with tabNumber:', tabNumber);

    // Periksa apakah elemen tab ada sebelum mencoba mengaksesnya
    const tabContents = document.querySelectorAll('.tab-content');
    if (tabContents.length === 0) {
        console.log('No tab-content elements found, form probably has only one page');
        return; // Keluar dari fungsi jika tidak ada tab
    }

    // Variabel untuk melacak tab yang aktif
    window.currentTab = tabNumber;

    // Sembunyikan semua tab content
        tabContents.forEach(tab => {
            tab.classList.add('hidden');
        });
        console.log('All tab contents hidden');

    // Tampilkan tab yang dipilih
    const selectedTab = document.getElementById(`tab-${tabNumber}`);
    if (selectedTab) {
        selectedTab.classList.remove('hidden');
        console.log(`Tab ${tabNumber} displayed`);
    } else {
        console.log(`tab-${tabNumber} element not found, but this is okay if form has only one page`);
    }

    // Update style tombol tab
    const tabButtons = document.querySelectorAll('.tab-button');
    if (tabButtons.length > 0) {
        tabButtons.forEach(button => {
            button.classList.remove('text-green-600', 'font-semibold', 'border-b-2', 'border-green-600');
            button.classList.add('text-gray-600');
        });
        console.log('All tab buttons reset to inactive style');

    const activeTabButton = document.querySelector(`.tab-${tabNumber}`);
    if (activeTabButton) {
        activeTabButton.classList.remove('text-gray-600');
        activeTabButton.classList.add('text-green-600', 'font-semibold', 'border-b-2', 'border-green-600');
        console.log(`Tab button ${tabNumber} set to active style`);
    } else {
            console.log(`.tab-${tabNumber} button not found, but this is okay if form has only one page`);
        }
    } else {
        console.log('No tab-button elements found, but this is okay if form has only one page');
    }
}

// Buat fungsi switchTab tersedia di scope global
window.switchTab = switchTab;

// Function to fill form with tree data
function fillFormWithTreeData(tree) {
    console.log('Filling form with tree data:', tree);

    // Set tree ID
    const treeIdInput = document.getElementById('tree_id');
    if (treeIdInput) {
        treeIdInput.value = tree.id;
        console.log('Set tree_id to:', tree.id);
    } else {
        console.error('tree_id input not found!');
    }

    // Set form mode to update
    const formModeInput = document.getElementById('form_mode');
    if (formModeInput) {
        formModeInput.value = 'update';
        console.log('Set form_mode to: update');
    } else {
        console.error('form_mode input not found!');
    }

    // Set plantation ID
    setFormValueIfExists('plantation_id', tree.plantation_id);

    // Set varietas
    setFormValueIfExists('varietas', tree.varietas);

    // Set tahun tanam
    setFormValueIfExists('tahun_tanam', tree.tahun_tanam);

    // Set health status
    setFormValueIfExists('health_status', tree.health_status);

    // Set fase
    setFormValueIfExists('fase', tree.fase);

    // Set sumber bibit
    setFormValueIfExists('sumber_bibit', tree.sumber_bibit);

    // Set canopy geometry
    setFormValueIfExists('canopy_geometry', tree.canopy_geometry);

    // Set shape type
    setFormValueIfExists('shape_type', tree.shape_type || detectShapeTypeFromWKT(tree.canopy_geometry));

    console.log('Form filled with tree data');
}

// Helper function to detect shape type from WKT
function detectShapeTypeFromWKT(wkt) {
    if (!wkt) return 'Polygon';

    const upperWkt = wkt.toUpperCase();
    if (upperWkt.startsWith('POINT')) {
        return 'Marker';
    } else if (upperWkt.startsWith('POLYGON')) {
        return 'Polygon';
    }

    return 'Polygon'; // Default
}

// Fungsi untuk mengirim form pohon
function submitTreeForm() {
    console.log('submitTreeForm called');

    // Ambil form
    const form = document.getElementById('treeForm');
    if (!form) {
        console.error('Form not found!');
        return false;
    }

    // Validasi form
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }

    // Konversi ID pohon ke huruf kapital jika ada
    let idValue = '';
    const formMode = document.getElementById('form_mode').value;

    // Dapatkan nilai ID berdasarkan mode form (create atau update)
    if (formMode === 'create') {
    const customIdInput = document.getElementById('custom_id');
    if (customIdInput && customIdInput.value.trim() !== '') {
        // Ubah ke huruf kapital
        customIdInput.value = customIdInput.value.toUpperCase();
            idValue = customIdInput.value;
        }
    } else { // mode edit
        const displayIdInput = document.getElementById('display_id');
        if (displayIdInput && displayIdInput.value.trim() !== '') {
            // Ubah ke huruf kapital
            displayIdInput.value = displayIdInput.value.toUpperCase();
            idValue = displayIdInput.value;
        }
    }

    // Validasi format ID jika ada
    if (idValue) {
        // Validasi format ID (harus berupa angka diikuti dengan huruf)
        const idPattern = /^(\d+)([A-Z]+)$/;
        if (!idPattern.test(idValue)) {
            alert('Format ID pohon tidak valid. Gunakan format angka diikuti huruf (contoh: 1A, 2B, 10C)');
            return false;
        }

        // Jika ini mode tambah (bukan edit), verifikasi bahwa ID belum digunakan
        if (formMode === 'create') {
            // Cek dalam drawnItems apakah ada pohon dengan ID yang sama
            let isDuplicate = false;
            drawnItems.eachLayer(function(layer) {
                if (layer.treeData && layer.treeData.id === idValue) {
                    isDuplicate = true;
                }
            });

            if (isDuplicate) {
                alert('ID pohon ' + idValue + ' sudah digunakan. Silakan gunakan ID lain.');
                return false;
            }
        }
    }

    // Tampilkan loading
    showLoading('Menyimpan data pohon...');

    // Dapatkan data form
    const formData = new FormData(form);
    const method = document.getElementById('form_method').value || 'POST';
    let url = '/api/trees';

    // Simpan ID asli sebelum diubah (untuk mode edit)
    const originalTreeId = document.getElementById('tree_id').value;

    // Jika ini edit, gunakan URL dengan ID asli (bukan ID yang mungkin telah diubah)
    if (method === 'PUT') {
        url = `/api/trees/${originalTreeId}`;
    }

    // Konversi FormData ke object untuk fetch
    const data = {};
    formData.forEach((value, key) => {
        // Jika ini adalah field id, pastikan dalam format kapital
        if (key === 'id' && value) {
            data[key] = value.toString().toUpperCase();
        } else {
            data[key] = value;
        }
    });

    // Pastikan ID baru dimasukkan dalam data jika dalam mode edit
    if (formMode === 'update' && idValue) {
        data['id'] = idValue;
        console.log('Setting ID explicitly for update to:', idValue);
    }

    // Log data yang akan dikirim
    console.log('Sending tree data to server:', data);

    // Kirim data ke server
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            // Coba ambil pesan error dari respons JSON jika ada
            return response.json()
                .then(errData => {
                    throw new Error(errData.message || `HTTP error! status: ${response.status}`);
                })
                .catch(err => {
                    // Jika tidak bisa parse JSON, gunakan pesan default
                    if (err instanceof SyntaxError) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    throw err;
                });
        }
        return response.json();
    })
    .then(data => {
        // Sembunyikan loading
        hideLoading();

        if (data.success) {
            console.log('Tree saved successfully:', data);

            // Tutup modal
            window.dispatchEvent(new CustomEvent('close-tree-modal'));

            // Tandai layer sebagai tersimpan
            if (currentShapeData && currentShapeData.layer) {
                currentShapeData.layer.isSaved = true;

                // Tambahkan data pohon ke layer
                currentShapeData.layer.treeData = data.data;

                // Ubah style layer untuk menunjukkan bahwa sudah tersimpan
                let healthColor = getHealthColor(data.data.health_status);

                currentShapeData.layer.setStyle({
                    color: healthColor,
                    weight: 4,
                    opacity: 0.9,
                    fillColor: healthColor,
                    fillOpacity: 0.25
                });

                // Tambahkan popup dengan data pohon
                currentShapeData.layer.bindPopup(createPopupContent(data.data));
            }

            // Tampilkan pesan sukses
            const message = method === 'PUT' ? 'Data pohon berhasil diperbarui!' : 'Data pohon berhasil disimpan!';
            alert(message);

            // Reset currentShapeData
            currentShapeData = {
                layer: null,
                wkt: null,
                shape: null
            };

            // Reload trees tanpa refresh halaman
            setTimeout(() => {
                console.log('Reloading trees after edit/create');
                loadExistingTrees(true); // Tambahkan parameter true untuk memaksa reload
            }, 500);
        } else {
            console.error('Error saving tree:', data.message);
            alert('Gagal menyimpan data pohon: ' + data.message);
        }
    })
    .catch(error => {
        // Sembunyikan loading
        hideLoading();

        console.error('Error saving tree:', error);
        alert('Terjadi kesalahan saat menyimpan data: ' + error.message);
    });

    return false;
}

// Function to handle tree deletion
function deleteTree(treeId) {
    if (isDeleting) return; // Prevent multiple deletions
    isDeleting = true;

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Tampilkan loading
    showLoading('Menghapus data pohon...');

    fetch(`/api/trees/${treeId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': token,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => Promise.reject(err));
        }
        return response.json();
    })
    .then(data => {
        hideLoading();
        if (data.success) {
            console.log('Tree deleted successfully:', data.message);
            alert(data.message);

            // Hapus layer dari peta
            drawnItems.eachLayer(function(layer) {
                if ((layer.treeData && layer.treeData.id === treeId) ||
                    (layer.feature && layer.feature.properties && layer.feature.properties.id === treeId)) {
                    drawnItems.removeLayer(layer);
                }
            });

            // Reload data pohon
            loadExistingTrees();
        } else {
            throw new Error(data.message || 'Gagal menghapus pohon');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error deleting tree:', error);
        alert(error.message || 'Gagal menghapus data pohon');
    })
    .finally(() => {
        isDeleting = false;
    });
}

// Fungsi untuk menghapus pohon berdasarkan ID
function deleteTreeById(treeId) {
    if (confirm('Apakah Anda yakin ingin menghapus pohon ini?')) {
        deleteTree(treeId);
    }
}

// Handle layer removal
map.on('pm:remove', function(e) {
    console.log('pm:remove event triggered', e);

    // Periksa apakah layer memiliki treeData (sudah disimpan di database)
    if (e.layer.treeData && e.layer.treeData.id && !isDeleting) {
        // Jika layer sudah disimpan, konfirmasi penghapusan dari database
        if (confirm('Apakah Anda yakin ingin menghapus pohon ini dari database?')) {
            deleteTree(e.layer.treeData.id);
        } else {
            // Jika user membatalkan, tambahkan kembali layer ke peta
            drawnItems.addLayer(e.layer);
        }
    }
});

// Helper function to convert Leaflet layer to WKT
function getWKT(layer) {
    try {
        console.log('Converting layer to WKT:', layer);

        // Cek tipe layer
        let wkt = null;

        // Jika layer adalah marker (titik)
        if (layer.pm && layer.pm._shape === 'Marker') {
            console.log('Layer is a Marker');
            const latlng = layer.getLatLng();
            const lng = parseFloat(latlng.lng).toFixed(6);
            const lat = parseFloat(latlng.lat).toFixed(6);
            wkt = `POINT(${lng} ${lat})`;
            console.log('Generated Point WKT:', wkt);
            return wkt;
        }
        // Jika layer adalah lingkaran
        else if (layer.pm && layer.pm._shape === 'Circle') {
            console.log('Layer is a Circle');
            const center = layer.getLatLng();
            const radius = layer.getRadius(); // dalam meter

            // Konversi lingkaran ke polygon dengan 32 titik
            const points = [];
            for (let i = 0; i < 32; i++) {
                const angle = (i / 32) * 2 * Math.PI;
                const dx = radius * Math.cos(angle);
                const dy = radius * Math.sin(angle);

                // Konversi dx, dy (meter) ke koordinat geografis
                // Perkiraan kasar: 1 derajat = 111,000 meter di ekuator
                // Untuk latitude, 1 derajat = 111,000 meter
                // Untuk longitude, 1 derajat = 111,000 * cos(latitude) meter
                const latFactor = 1 / 111000; // faktor konversi meter ke derajat latitude
                const lngFactor = 1 / (111000 * Math.cos(center.lat * Math.PI / 180)); // faktor konversi untuk longitude

                const lat = center.lat + dy * latFactor;
                const lng = center.lng + dx * lngFactor;

                points.push(`${lng.toFixed(6)} ${lat.toFixed(6)}`);
            }

            // Tambahkan titik pertama di akhir untuk menutup polygon
            points.push(points[0]);

            wkt = `POLYGON((${points.join(',')}))`;
            console.log('Generated Circle WKT (as polygon):', wkt);
            return wkt;
        }
        // Jika layer adalah polygon dari Leaflet-Geoman
        else if (layer.pm && layer.pm._shape === 'Polygon') {
            console.log('Layer is a Leaflet-Geoman Polygon');
            const coordinates = layer.getLatLngs()[0];

            if (!coordinates || !coordinates.length) {
                console.error('No coordinates found in polygon');
                return null;
            }

            console.log('Polygon coordinates:', coordinates);

            // Pastikan polygon tertutup (titik pertama dan terakhir sama)
            let points = [];
            for (let i = 0; i < coordinates.length; i++) {
                // Format koordinat dengan presisi 6 digit desimal untuk akurasi
                const lng = parseFloat(coordinates[i].lng).toFixed(6);
                const lat = parseFloat(coordinates[i].lat).toFixed(6);
                points.push(`${lng} ${lat}`);
            }

            // Tambahkan titik pertama di akhir untuk menutup polygon jika belum tertutup
            const firstLng = parseFloat(coordinates[0].lng).toFixed(6);
            const firstLat = parseFloat(coordinates[0].lat).toFixed(6);

            // Jika titik terakhir tidak sama dengan titik pertama, tambahkan titik pertama di akhir
            const lastPoint = points[points.length - 1];
            const firstPoint = `${firstLng} ${firstLat}`;

            if (lastPoint !== firstPoint) {
                points.push(firstPoint);
            }

            // Pastikan ada minimal 4 titik (3 titik + 1 titik penutup)
            if (points.length < 4) {
                console.error('Polygon must have at least 3 points');
            return null;
        }

            wkt = `POLYGON((${points.join(',')}))`;
        }
        // Jika layer adalah polygon biasa
        else if (layer.getLatLngs) {
            console.log('Layer is a regular Leaflet Polygon');
    const coordinates = layer.getLatLngs()[0];

        if (!coordinates || !coordinates.length) {
                console.error('No coordinates found in polygon');
            return null;
        }

            console.log('Polygon coordinates:', coordinates);

        // Pastikan polygon tertutup (titik pertama dan terakhir sama)
        let points = [];
        for (let i = 0; i < coordinates.length; i++) {
            // Format koordinat dengan presisi 6 digit desimal untuk akurasi
            const lng = parseFloat(coordinates[i].lng).toFixed(6);
            const lat = parseFloat(coordinates[i].lat).toFixed(6);
            points.push(`${lng} ${lat}`);
        }

        // Tambahkan titik pertama di akhir untuk menutup polygon jika belum tertutup
        const firstLng = parseFloat(coordinates[0].lng).toFixed(6);
        const firstLat = parseFloat(coordinates[0].lat).toFixed(6);

        // Jika titik terakhir tidak sama dengan titik pertama, tambahkan titik pertama di akhir
        const lastPoint = points[points.length - 1];
        const firstPoint = `${firstLng} ${firstLat}`;

        if (lastPoint !== firstPoint) {
            points.push(firstPoint);
        }

        // Pastikan ada minimal 4 titik (3 titik + 1 titik penutup)
        if (points.length < 4) {
            console.error('Polygon must have at least 3 points');
            return null;
        }

            wkt = `POLYGON((${points.join(',')}))`;
        }
        // Jika layer tidak dikenali
        else {
            console.error('Layer type not recognized');
            return null;
        }

        console.log('Generated WKT:', wkt);

        // Validasi format WKT
        if (!wkt || !(wkt.match(/^POLYGON\s*\(\s*\(\s*.+\s*\)\s*\)$/i) || wkt.match(/^POINT\s*\(.+\)$/i))) {
            console.error('Generated WKT is not valid:', wkt);
            return null;
        }

        return wkt;
    } catch (error) {
        console.error('Error converting layer to WKT:', error);
        return null;
    }
}

// Add event listener untuk tombol close modal
document.addEventListener('DOMContentLoaded', function() {
    const closeButtons = document.querySelectorAll('[data-close-modal]');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            window.dispatchEvent(new CustomEvent('close-tree-modal'));
        });
    });
});

// Function to convert WKT to GeoJSON format compatible with Turf.js
function parseWKTtoGeoJSON(wkt) {
    if (!wkt) {
        console.error('WKT is null or empty');
        return null;
    }

    console.log('Parsing WKT to GeoJSON, WKT length:', wkt.length, 'Sample:', wkt.substring(0, 50));

    // Hapus SRID jika ada
    let cleanWkt = wkt;
    if (wkt.toUpperCase().startsWith('SRID=')) {
        const sridMatch = wkt.match(/SRID=(\d+);(.*)/i);
        if (sridMatch && sridMatch.length > 2) {
            cleanWkt = sridMatch[2];
            console.log('Extracted WKT without SRID, length:', cleanWkt.length);
        } else {
        cleanWkt = wkt.substring(wkt.indexOf(';') + 1);
        }
    }

    // Deteksi apakah ini adalah format standar yang didukung
    const isPolygon = cleanWkt.toUpperCase().startsWith('POLYGON');
    const isMultiPolygon = cleanWkt.toUpperCase().startsWith('MULTIPOLYGON');
    const isPoint = cleanWkt.toUpperCase().startsWith('POINT');

    // Validasi format WKT standar
    if (!isPolygon && !isMultiPolygon && !isPoint) {
        console.error('Format WKT tidak dikenali:', cleanWkt.substring(0, 30));
        throw new Error('Format WKT tidak standar');
    }

    // Normalisasi whitespace untuk parsing yang lebih konsisten
    cleanWkt = cleanWkt.replace(/\s+/g, ' ').trim();

    // Log format untuk debugging
    console.log('WKT type detection:', {
        isMultiPolygon,
        isPolygon,
        isPoint,
        length: cleanWkt.length
    });

    try {
        // Handle permasalahan geometri khusus untuk POLYGON
        if (isPolygon) {
            console.log('Processing POLYGON format');

            // Ekstrak semua ring dalam polygon
            const polygonMatch = cleanWkt.match(/POLYGON\s*\(\s*(.*)\s*\)/i);
            if (!polygonMatch || polygonMatch.length < 2) {
                throw new Error('Format POLYGON tidak valid');
            }

            const ringsContent = polygonMatch[1];
            console.log('Extracted ring content:', ringsContent.substring(0, 30) + '...');

            // Deteksi apakah ini polygon dengan lubang (multi-ring)
            // Polygon tanpa lubang: POLYGON((x1 y1, x2 y2, ...))
            // Polygon dengan lubang: POLYGON((x1 y1, x2 y2, ...), (h1x h1y, h2x h2y, ...))
            const hasMultipleRings = (ringsContent.match(/\(/g) || []).length > 1;

            if (hasMultipleRings) {
                console.log('Processing polygon with multiple rings (holes)');

                // Ekstrak semua ring: exterior dan interior (holes)
                const rings = [];
                const ringMatches = ringsContent.match(/\([^()]+\)/g);

                if (!ringMatches || ringMatches.length === 0) {
                    throw new Error('Tidak dapat menemukan ring dalam POLYGON');
                }

                for (let i = 0; i < ringMatches.length; i++) {
                    // Bersihkan kurung dari ring
                    const ringContent = ringMatches[i].replace(/^\(|\)$/g, '');
                            const coords = parseCoordinateString(ringContent);

                            // Pastikan ring tertutup
                            if (coords.length >= 3) {
                                // Cek apakah titik awal dan akhir sama
                                if (coords[0][0] !== coords[coords.length-1][0] ||
                                    coords[0][1] !== coords[coords.length-1][1]) {
                                    // Tutup polygon jika belum tertutup
                                    coords.push([coords[0][0], coords[0][1]]);
                                }
                                rings.push(coords);
                    }
                }

                if (rings.length === 0) {
                    throw new Error('Tidak ada ring yang valid dalam polygon');
                }

                console.log(`Berhasil memproses ${rings.length} ring dari polygon`);

                return {
                    type: 'Feature',
                    properties: {},
                    geometry: {
                        type: 'Polygon',
                        coordinates: rings
                    }
                };
            } else {
                // Single ring polygon
                console.log('Processing single ring polygon');

                // Bersihkan tanda kurung ekstra
                let coordsString = ringsContent;
                if (coordsString.startsWith('(') && coordsString.endsWith(')')) {
                    coordsString = coordsString.substring(1, coordsString.length - 1);
                }

                const coordinates = parseCoordinateString(coordsString);

                // Validasi
            if (coordinates.length < 3) {
                    throw new Error('Polygon harus memiliki minimal 3 titik');
            }

            // Pastikan polygon tertutup
            if (coordinates[0][0] !== coordinates[coordinates.length-1][0] ||
                coordinates[0][1] !== coordinates[coordinates.length-1][1]) {
                    // Tutup polygon jika belum tertutup
                coordinates.push([coordinates[0][0], coordinates[0][1]]);
            }

                console.log(`Processed single ring polygon with ${coordinates.length} points`);

                return {
                type: 'Feature',
                properties: {},
                geometry: {
                    type: 'Polygon',
                    coordinates: [coordinates]
                }
            };
            }
        }
        // MULTIPOLYGON handling
        else if (isMultiPolygon) {
            console.log('Processing MultiPolygon');

            // Ekstrak semua polygon dari multipolygon
            const multiPolygonMatch = cleanWkt.match(/MULTIPOLYGON\s*\(\s*(.*)\s*\)/i);
            if (!multiPolygonMatch || multiPolygonMatch.length < 2) {
                throw new Error('Format MULTIPOLYGON tidak valid');
            }

            // Log untuk debugging
            console.log('MultiPolygon match content length:',
                multiPolygonMatch[1] ? multiPolygonMatch[1].length : 0);

            // Cari semua polygon dalam MULTIPOLYGON
            const polygonParts = [];
            const regex = /\(\([^)]*\)\)/g;
            let match;
            const content = multiPolygonMatch[1];

            while ((match = regex.exec(content)) !== null) {
                polygonParts.push(match[0]);
            }

            if (polygonParts.length === 0) {
                throw new Error('Tidak dapat menemukan polygon dalam MULTIPOLYGON');
            }

            // Ambil polygon pertama saja untuk sederhananya
            const firstPolygonPart = polygonParts[0];

            // Bersihkan kurung luar
            const coordsString = firstPolygonPart.replace(/^\(\(|\)\)$/g, '');
            const coordinates = parseCoordinateString(coordsString);

            // Validasi
            if (coordinates.length < 3) {
                throw new Error('Polygon harus memiliki minimal 3 titik');
            }

            // Pastikan polygon tertutup
            if (coordinates[0][0] !== coordinates[coordinates.length-1][0] ||
                coordinates[0][1] !== coordinates[coordinates.length-1][1]) {
                // Tutup polygon jika belum tertutup
                coordinates.push([coordinates[0][0], coordinates[0][1]]);
            }

            console.log(`Processed first polygon from MultiPolygon with ${coordinates.length} points`);

                return {
                    type: 'Feature',
                    properties: {},
                    geometry: {
                    type: 'Polygon',
                    coordinates: [coordinates]
                }
            };
        }
        // POINT handling
        else if (isPoint) {
            console.log('Processing Point');

            const pointMatch = cleanWkt.match(/POINT\s*\(\s*(.*?)\s*\)/i);
            if (!pointMatch || pointMatch.length < 2) {
                throw new Error('Format POINT tidak valid');
            }

            const coordPair = pointMatch[1].trim().split(/\s+/);
            if (coordPair.length < 2) {
                throw new Error('Format koordinat POINT tidak valid');
            }

            const lng = parseFloat(coordPair[0]);
            const lat = parseFloat(coordPair[1]);

            if (isNaN(lng) || isNaN(lat)) {
                throw new Error('Koordinat POINT bukan angka valid');
            }

            // Untuk point, buat polygon kecil di sekitarnya untuk tampilan
            const radius = 0.001; // ~100m radius
            const numPoints = 8; // Buat octagon
                const coordinates = [];

            for (let i = 0; i < numPoints; i++) {
                const angle = (i / numPoints) * 2 * Math.PI;
                const dx = radius * Math.cos(angle);
                const dy = radius * Math.sin(angle);
                coordinates.push([lng + dx, lat + dy]);
            }

            // Tutup polygon
            coordinates.push([...coordinates[0]]);

            console.log(`Processed POINT (${lng}, ${lat}) as polygon with ${coordinates.length} points`);

                return {
                    type: 'Feature',
                    properties: {
                    isPoint: true,
                    center: [lng, lat],
                        radius: radius
                    },
                    geometry: {
                        type: 'Polygon',
                        coordinates: [coordinates]
                    }
                };
            }
    } catch (error) {
        console.error('Error saat parsing WKT ke GeoJSON:', error);

        // Default polygon sebagai fallback
        const defaultCenter = [0, 0];
        try {
            if (typeof map !== 'undefined' && map && map.getCenter) {
                const center = map.getCenter();
                defaultCenter[0] = center.lng;
                defaultCenter[1] = center.lat;
            }
        } catch (e) {
            console.error('Error getting map center:', e);
        }

        const size = 0.01;
        const coords = [
            [defaultCenter[0] - size, defaultCenter[1] - size],
            [defaultCenter[0] + size, defaultCenter[1] - size],
            [defaultCenter[0] + size, defaultCenter[1] + size],
            [defaultCenter[0] - size, defaultCenter[1] + size],
            [defaultCenter[0] - size, defaultCenter[1] - size]
        ];

        return {
            type: 'Feature',
            properties: {
                isDefault: true,
                note: 'Error parsing WKT: ' + error.message
            },
            geometry: {
                type: 'Polygon',
                coordinates: [coords]
            }
        };
    }
}

// Fungsi pembantu untuk mengurai string koordinat dengan lebih robust
function parseCoordinateString(coordsString) {
    if (!coordsString) {
        console.error('String koordinat kosong');
        return [];
    }

    const coordinates = [];
    const coordPairs = coordsString.split(',');

    console.log(`Memproses ${coordPairs.length} pasangan koordinat`);

    for (const pair of coordPairs) {
        const parts = pair.trim().split(/\s+/);

        if (parts.length >= 2) {
            // Ambil dua bagian pertama sebagai koordinat X dan Y
            const lng = parseFloat(parts[0]);
            const lat = parseFloat(parts[1]);

            if (!isNaN(lng) && !isNaN(lat)) {
                coordinates.push([lng, lat]);
            } else {
                console.warn('Pasangan koordinat tidak valid:', pair);
            }
        } else {
            console.warn('Format pasangan koordinat tidak valid:', pair);
        }
    }

    console.log(`Berhasil parse ${coordinates.length} koordinat`);
    return coordinates;
}

// Fungsi untuk memilih form pohon
function selectTreeForm() {
    console.log('selectTreeForm called');

    // Tutup modal pemilihan
    window.dispatchEvent(new CustomEvent('close-form-selector-modal'));

    // Set tipe objek menjadi pohon
    currentShapeType = 'tree';

    // Beri delay agar modal pemilihan tertutup sepenuhnya
    setTimeout(function() {
        // Pastikan currentShapeData ada
        if (!currentShapeData) {
            console.error('No shape data available for tree form');
            alert('Error: Tidak ada data bentuk tersedia');
            return;
        }

        // Tambahkan layer ke drawnItems (layer pohon)
        if (currentShapeData.layer && !currentShapeData.layer.isSaved) {
            // Pastikan layer belum ada di drawnItems (menghindari duplikasi)
            if (!drawnItems.hasLayer(currentShapeData.layer)) {
                drawnItems.addLayer(currentShapeData.layer);
                console.log('Layer added to tree layer (drawnItems)');
            }
        }

        // Log data untuk debugging
        console.log('Current shape data for tree:', currentShapeData);

        // Ambil WKT geometry
        let geometry = currentShapeData.wkt;
        let shapeType = 'Polygon';

        // Deteksi tipe shape
        if (typeof detectShapeTypeFromWKT === 'function') {
            shapeType = detectShapeTypeFromWKT(geometry);
        }

        console.log('Opening tree modal with geometry:', geometry);

        // Dispatch event untuk membuka modal pohon
        window.dispatchEvent(new CustomEvent('open-tree-modal', {
            detail: {
                isEdit: false,
                treeData: null,
                geometryWkt: geometry,
                shapeType: shapeType
            }
        }));
    }, 300);
}

// Fungsi untuk memilih form blok kebun
function selectPlantationForm() {
    console.log('selectPlantationForm called');

    // Tutup modal pemilihan
    window.dispatchEvent(new CustomEvent('close-form-selector-modal'));

    // Set tipe objek menjadi plantation (blok kebun)
    currentShapeType = 'plantation';

    // Beri delay agar modal pemilihan tertutup sepenuhnya
    setTimeout(function() {
        // Pastikan currentShapeData ada
        if (!currentShapeData) {
            console.error('No shape data available for plantation form');
            alert('Error: Tidak ada data bentuk tersedia');
            return;
        }

        // Tambahkan layer ke plantationLayerGroup (layer blok kebun)
        if (currentShapeData.layer && !currentShapeData.layer.isSaved) {
            // Pastikan layer group telah diinisialisasi
            if (!window.plantationLayerGroup) {
                initPlantationLayerGroup();
            }

            // Hapus layer dari drawnItems jika ada (karena mungkin sudah ditambahkan secara otomatis)
            if (drawnItems && drawnItems.hasLayer(currentShapeData.layer)) {
                drawnItems.removeLayer(currentShapeData.layer);
                console.log('Removed layer from drawnItems (tree layer)');
            }

            // Tambahkan ke layer blok kebun
            if (window.plantationLayerGroup && !window.plantationLayerGroup.hasLayer(currentShapeData.layer)) {
                window.plantationLayerGroup.addLayer(currentShapeData.layer);
                console.log('Layer added to plantation layer group');
            }
        }

        // Log data untuk debugging
        console.log('Current shape data for plantation:', currentShapeData);

        // Ambil WKT geometry dan pastikan formatnya benar
        let geometry = currentShapeData.wkt;
        if (typeof ensureWktFormat === 'function') {
            geometry = ensureWktFormat(geometry);
        }

        console.log('Opening plantation modal with geometry:', geometry);

        // Dispatch event untuk membuka modal kebun
        window.dispatchEvent(new CustomEvent('open-plantation-modal', {
            detail: {
                isEdit: false,
                plantationData: null,
                geometryWkt: geometry
            }
        }));
    }, 300);
}

// Fungsi untuk membatalkan pemilihan jenis form
function cancelFormSelection() {
    console.log('cancelFormSelection called');

    // Tutup modal pemilihan
    window.dispatchEvent(new CustomEvent('close-form-selector-modal'));

    // Batalkan shape jika masih dalam proses pembuatan
    if (currentShapeData && currentShapeData.layer) {
        cancelShape();
    }
}

// Fallback untuk Alpine.js jika tidak terdeksi event
// Letakkan ini di akhir file
document.addEventListener('DOMContentLoaded', function() {
    console.log('Setting up Alpine.js event handling fallbacks');

    // Helper function untuk membuka modal tanpa Alpine jika diperlukan
    window.forceOpenModal = function(modalId, extraClasses = 'flex') {
        console.log(`Attempting to force open modal: ${modalId}`);
        const modal = document.getElementById(modalId);
        if (modal) {
            // Hapus kelas hidden atau modal-hidden
            modal.classList.remove('hidden', 'modal-hidden');
            // Tambahkan kelas yang diperlukan
            modal.classList.add(extraClasses);
            modal.style.display = 'block';
            console.log(`Modal ${modalId} forced open`);
            return true;
        } else {
            console.error(`Modal ${modalId} not found`);
            return false;
        }
    };

    // Helper function untuk menutup modal tanpa Alpine jika diperlukan
    window.forceCloseModal = function(modalId) {
        console.log(`Attempting to force close modal: ${modalId}`);
        const modal = document.getElementById(modalId);
        if (modal) {
            // Tambahkan kelas hidden
            modal.classList.add('hidden', 'modal-hidden');
            modal.style.display = 'none';
            console.log(`Modal ${modalId} forced closed`);
            return true;
        } else {
            console.error(`Modal ${modalId} not found`);
            return false;
        }
    };

    // Tambahkan event listener tambahan untuk menangani form selector modal
    window.addEventListener('open-form-selector-modal', function() {
        // Cek apakah Alpine.js terhubung dan bekerja
        const modalElement = document.querySelector('[x-data*="showFormSelectorModal"]');
        const alpineInitialized = typeof window.Alpine !== 'undefined' && modalElement;

        if (!alpineInitialized) {
            console.warn('Alpine.js not detected or not initialized, using fallback for form selector modal');
            // Cari modal berdasarkan id atau kelas
            const modalSelector = document.querySelector('.form-selector-modal') ||
                                  document.querySelector('[x-data*="showFormSelectorModal"]').closest('.fixed');

            if (modalSelector) {
                // Manual tampilkan modal
                modalSelector.classList.remove('hidden', 'modal-hidden');
                modalSelector.style.display = 'block';
                console.log('Form selector modal opened using fallback');
            }
        }
    });

    // Tambahkan event listener tambahan untuk menangani tree modal
    window.addEventListener('open-tree-modal', function() {
        // Cek apakah Alpine.js terhubung dan bekerja
        const modalElement = document.querySelector('[x-data*="showTreeModal"]');
        const alpineInitialized = typeof window.Alpine !== 'undefined' && modalElement;

        if (!alpineInitialized) {
            console.warn('Alpine.js not detected or not initialized, using fallback for tree modal');
            // Cari modal berdasarkan id atau kelas
            const modalSelector = document.querySelector('.tree-modal') ||
                                 document.querySelector('[x-data*="showTreeModal"]').closest('.fixed');

            if (modalSelector) {
                // Manual tampilkan modal
                modalSelector.classList.remove('hidden', 'modal-hidden');
                modalSelector.style.display = 'block';
                console.log('Tree modal opened using fallback');
            }
        }
    });

    // Tambahkan event listener tambahan untuk menangani plantation modal
    window.addEventListener('open-plantation-modal', function() {
        // Cek apakah Alpine.js terhubung dan bekerja
        const modalElement = document.querySelector('[x-data*="showPlantationModal"]');
        const alpineInitialized = typeof window.Alpine !== 'undefined' && modalElement;

        if (!alpineInitialized) {
            console.warn('Alpine.js not detected or not initialized, using fallback for plantation modal');
            // Cari modal berdasarkan id atau kelas
            const modalSelector = document.querySelector('.plantation-modal') ||
                                 document.querySelector('[x-data*="showPlantationModal"]').closest('.fixed');

            if (modalSelector) {
                // Manual tampilkan modal
                modalSelector.classList.remove('hidden', 'modal-hidden');
                modalSelector.style.display = 'block';
                console.log('Plantation modal opened using fallback');
            }
        }
    });
});

// Helper function to ensure WKT is in the correct format
function ensureWktFormat(wkt) {
    if (!wkt) {
        console.error('WKT input is null or empty');
        return null;
    }

    console.log('Ensuring WKT format for input of length:', wkt.length);

    // Hapus spasi berlebih dan normalisasi
    let cleanWkt = wkt.replace(/\s+/g, ' ').trim();

    // Deteksi apakah sudah memiliki SRID
    const hasSrid = cleanWkt.toUpperCase().startsWith('SRID=');

    // Jika sudah ada SRID, ekstrak dan bersihkan
    if (hasSrid) {
        console.log('WKT already has SRID prefix');
        return cleanWkt; // Kembalikan apa adanya jika sudah memiliki SRID
    }

    // Tambahkan SRID jika belum ada
        cleanWkt = `SRID=4326;${cleanWkt}`;
    console.log('Added SRID to WKT, new length:', cleanWkt.length);

    return cleanWkt;
}

// ... existing code ...

// Tambahkan fungsi debugging
function debugWktArea(wkt) {
    console.group('Area Debug for WKT');

    try {
        if (!wkt) {
            console.warn('WKT is empty or null');
            console.groupEnd();
            return;
        }

        console.log('Input WKT length:', wkt.length);
        console.log('WKT type check:');
        console.log('- Starts with SRID=:', wkt.toUpperCase().startsWith('SRID='));
        console.log('- Contains POLYGON:', wkt.toUpperCase().includes('POLYGON'));
        console.log('- Contains MULTIPOLYGON:', wkt.toUpperCase().includes('MULTIPOLYGON'));

        // Coba hitung area
        const area = calculateAreaFromWkt(wkt);
        console.log('Calculated area (hectares):', area);

        // Coba parse ke GeoJSON untuk verifikasi
        const geojson = parseWKTtoGeoJSON(wkt);
        if (geojson) {
            console.log('GeoJSON parse successful');
            console.log('GeoJSON type:', geojson.geometry.type);
            if (geojson.geometry.type === 'Polygon') {
                console.log('Polygon rings:', geojson.geometry.coordinates.length);
                console.log('Exterior ring points:', geojson.geometry.coordinates[0].length);
            } else if (geojson.geometry.type === 'MultiPolygon') {
                console.log('Number of polygons:', geojson.geometry.coordinates.length);
            }
        } else {
            console.error('Failed to parse WKT to GeoJSON');
        }
    } catch (error) {
        console.error('Error in debugWktArea:', error);
    }

    console.groupEnd();
}

// Fungsi ini akan dihapus jika ada listener duplikat di file
function handlePlantationModalOpen(event) {
    console.log('open-plantation-modal event received:', event.detail);

    // Set nilai di form
    setTimeout(() => {
        // Dapatkan detail dari event
        const isEditMode = event.detail?.isEdit || false;
        const plantationData = event.detail?.plantationData || null;
        const geometryWkt = event.detail?.geometryWkt || null;

        console.log('Opening plantation modal:', {
            isEditMode,
            plantationData,
            geometryWkt
        });

        // Reset form terlebih dahulu
        const form = document.getElementById('plantationForm');
        if (form) {
            console.log('Resetting form');
            form.reset();
        }

        // Perbarui method form berdasarkan mode
        const formMethodInput = document.getElementById('plantation_form_method');
        if (formMethodInput) {
            formMethodInput.value = isEditMode ? 'PUT' : 'POST';
            console.log('Form method set to:', formMethodInput.value);
        }

        // Ambil semua elemen input yang diperlukan
        const idInput = document.getElementById('plantation_id');
        const nameInput = document.getElementById('name');
        const geometryInput = document.getElementById('boundary_geometry');
        const areaInput = document.getElementById('luas_area');
        const tipeTanahInput = document.getElementById('tipe_tanah');
        const displayIdInput = document.getElementById('display_plantation_id');
        const userRole = window.userRole || '';

        // Jika mode edit, isi form dengan data yang ada
        if (isEditMode && plantationData) {
            console.log('Filling form with plantation data:', plantationData);

            // Set plantation ID
            if (idInput && plantationData.id) {
                idInput.value = plantationData.id;
                console.log('ID set to:', plantationData.id);

                // Jika ada display ID, isi juga
                if (displayIdInput) {
                    displayIdInput.value = plantationData.id;
                    if (userRole === 'Operasional') {
                        displayIdInput.readOnly = true;
                        displayIdInput.style.backgroundColor = '#f3f4f6'; // Warna abu-abu muda
                    } else {
                        displayIdInput.readOnly = false;
                        displayIdInput.style.backgroundColor = '';
                    }
                }
            }

            // Set nama kebun
            if (nameInput && plantationData.name) {
                nameInput.value = plantationData.name;
                console.log('Name set to:', plantationData.name);
            }

            // Set geometry
            if (geometryInput && plantationData.geometry) {
                geometryInput.value = plantationData.geometry;
                console.log('Geometry set from plantation data:', plantationData.geometry.substring(0, 50) + '...');

                // Debug area calculation
                console.log('Debugging area calculation for edit mode:');
                if (typeof debugWktArea === 'function') {
                    debugWktArea(plantationData.geometry);
                }
            }

            // Set luas area - pertama gunakan nilai dari database, kemudian hitung ulang jika perlu
            if (areaInput) {
                if (plantationData.luas_area) {
                    const luas = parseFloat(plantationData.luas_area);
                    areaInput.value = isNaN(luas) ? 0 : luas.toFixed(4);
                    console.log('Area set from database:', areaInput.value);
                } else if (geometryInput && geometryInput.value) {
                    // Jika tidak ada nilai area, hitung dari geometri
                    const area = calculateAreaFromWkt(geometryInput.value);
                    if (area > 0) {
                        areaInput.value = area.toFixed(4);
                        console.log('Area calculated from geometry:', area.toFixed(4), 'ha');
                    }
                }
            }

            // Set tipe tanah
            if (tipeTanahInput && plantationData.tipe_tanah) {
                tipeTanahInput.value = plantationData.tipe_tanah;
                console.log('Tipe tanah set to:', plantationData.tipe_tanah);
            }
        }
        // Jika mode baru
        else {
            console.log('Setting up new plantation form');

            // Reset ID
            if (idInput) {
                idInput.value = '';
            }

            // Set geometri dari event atau currentShapeData
            if (geometryInput) {
                let geometryValue = null;

                if (geometryWkt) {
                    // Gunakan geometri dari event
                    geometryValue = ensureWktFormat(geometryWkt);
                    geometryInput.value = geometryValue;
                    console.log('Setting geometry from event data:', geometryValue.substring(0, 50) + '...');
                }
                else if (window.currentShapeData && window.currentShapeData.wkt) {
                    // Gunakan geometri dari bentuk yang dibuat dengan Geoman
                    geometryValue = ensureWktFormat(window.currentShapeData.wkt);
                    geometryInput.value = geometryValue;
                    console.log('Setting geometry from currentShapeData:', geometryValue.substring(0, 50) + '...');
                }

                // Log nilai geometri yang diatur
                if (geometryInput.value) {
                    console.log('Final geometry value set in form:', geometryInput.value.substring(0, 50) + '...');

                    // Hitung dan isi luas area secara otomatis
                    if (areaInput) {
                        console.log('Calculating area from geometry input');
                        // Gunakan fungsi calculateAreaFromWkt yang telah ditingkatkan
                        const area = calculateAreaFromWkt(geometryInput.value);
                        if (area > 0) {
                            areaInput.value = area.toFixed(4);
                            console.log('Area automatically calculated and filled:', area.toFixed(4), 'hectares');
                        } else {
                            console.warn('Area calculation returned zero or error');
                            // Reset area dan beri pesan alert
                            areaInput.value = '0.0000';
                            alert('Perhitungan luas area gagal. Silakan masukkan luas area secara manual.');
                        }
                    } else {
                        console.error('Area input element not found');
                    }
                } else {
                    console.warn('No geometry value set in form');
                }
            } else {
                console.error('Boundary geometry input element not found in the form');
            }
        }

        // Periksa sekali lagi jika area masih kosong, coba hitung
        if (areaInput && (!areaInput.value || parseFloat(areaInput.value) === 0) && geometryInput && geometryInput.value) {
            console.log('Area still empty or zero, calculating again');
            const area = calculateAreaFromWkt(geometryInput.value);
            if (area > 0) {
                areaInput.value = area.toFixed(4);
                console.log('Area recalculated:', area.toFixed(4), 'ha');
            }
        }

        // Berikan feedback visual setelah form diisi
        console.log('Plantation form ready with fields:');
        console.log('- ID:', idInput ? idInput.value : 'N/A');
        console.log('- Name:', nameInput ? nameInput.value : 'N/A');
        console.log('- Area:', areaInput ? areaInput.value : 'N/A');
        console.log('- Geometry length:', geometryInput ? geometryInput.value.length : 'N/A');
        console.log('- Tipe tanah:', tipeTanahInput ? tipeTanahInput.value : 'N/A');
    }, 300);
}

// Hapus event listener duplikat sebelum menambahkan yang baru
document.addEventListener('DOMContentLoaded', function() {
    console.log('Setting up plantation modal handlers');

    // Pendekatan 1: Gunakan variabel khusus untuk menandai apakah event listener sudah ditambahkan
    if (!window._plantationModalHandlerAdded) {
        window._plantationModalHandlerAdded = true;

        // Setup event listener untuk plantation modal open
        window.addEventListener('open-plantation-modal', handlePlantationModalOpen);
        console.log('Added primary open-plantation-modal handler for accurate area calculations');
    }
});

// ... existing code ...

// Fungsi untuk menghitung area dari WKT dengan lebih akurat
function calculateAreaFromWkt(wkt) {
    // Log untuk debugging
    console.group('Perhitungan Luas Area');
    console.log('Input WKT length:', wkt ? wkt.length : 0);

    if (!wkt) {
        console.error('WKT kosong');
        console.groupEnd();
        return 0;
    }

    try {
        // Hilangkan SRID jika ada
        let cleanWkt = wkt;
        if (cleanWkt.toUpperCase().startsWith('SRID=')) {
            const sridMatch = cleanWkt.match(/SRID=(\d+);(.*)/i);
            if (sridMatch && sridMatch.length > 2) {
                cleanWkt = sridMatch[2];
                console.log('WKT setelah menghilangkan SRID:', cleanWkt.substring(0, 50) + '...');
            } else {
                cleanWkt = cleanWkt.substring(cleanWkt.indexOf(';') + 1);
                console.log('WKT setelah menghilangkan SRID (metode alternatif):', cleanWkt.substring(0, 50) + '...');
            }
        }

        // Log detail WKT untuk debugging
        console.log('WKT Type:',
            cleanWkt.toUpperCase().startsWith('POLYGON') ? 'POLYGON' :
            cleanWkt.toUpperCase().startsWith('MULTIPOLYGON') ? 'MULTIPOLYGON' : 'UNKNOWN');

        // Metode 1: Gunakan parseWKTtoGeoJSON yang telah ditingkatkan
        console.log('Mencoba metode 1: parseWKTtoGeoJSON');
        const geojson = parseWKTtoGeoJSON(cleanWkt);

        if (geojson && geojson.geometry) {
            console.log('GeoJSON berhasil dibuat:', geojson.geometry.type);

            // Cek ketersediaan turf.js sebelum mencoba menggunakan
            if (typeof turf !== 'undefined' && typeof turf.area === 'function') {
            try {
                // Gunakan turf.js untuk menghitung luas
                const areaInSqMeters = turf.area(geojson);
                const areaInHectares = areaInSqMeters / 10000;

                console.log('Luas area (turf):', areaInSqMeters, 'm²');
                console.log('Luas area (ha):', areaInHectares, 'ha');

                if (areaInHectares > 0) {
                    console.groupEnd();
                    return areaInHectares;
                } else {
                    console.warn('Turf.js mengembalikan luas nol atau negatif');
                }
            } catch (turfError) {
                console.error('Error saat menghitung dengan turf.js:', turfError);
                }
            } else {
                console.warn('Turf.js tidak tersedia, menggunakan metode alternatif');
            }

            // Jika turf tidak tersedia atau gagal, gunakan metode alternatif
            console.log('Mencoba metode alternatif dengan manual calculation');
            const manualArea = calculateManualArea(geojson);
            if (manualArea > 0) {
                const areaInHectares = manualArea / 10000;
                console.log('Luas area (metode manual):', areaInHectares.toFixed(4), 'ha');
                console.groupEnd();
                return areaInHectares;
            }
        } else {
            console.error('Gagal membuat GeoJSON dari WKT');
        }

        // Metode 2: Gunakan L.polygon untuk menghitung luas
        console.log('Mencoba metode 2: L.polygon');
        try {
            if (typeof L !== 'undefined' && L.polygon) {
                // Jika ini adalah POLYGON
                if (cleanWkt.toUpperCase().startsWith('POLYGON')) {
                    // Parse POLYGON((lng1 lat1, lng2 lat2, ...))
                    const polygonMatch = cleanWkt.match(/POLYGON\s*\(\((.*)\)\)/i);
                    if (polygonMatch && polygonMatch.length > 1) {
                        const coordsStr = polygonMatch[1];
                        const coords = coordsStr.split(',').map(pair => {
                            const [lng, lat] = pair.trim().split(/\s+/).map(Number);
                            return [lat, lng]; // Leaflet menggunakan [lat, lng]
                        });

                        // Buat Leaflet polygon
                        const polygon = L.polygon(coords);
                        const areaSqMeters = L.GeometryUtil.geodesicArea(polygon.getLatLngs()[0]);
                        const areaHectares = areaSqMeters / 10000;

                        console.log('Luas polygon (Leaflet):', areaSqMeters, 'm²');
                        console.log('Luas polygon (ha):', areaHectares, 'ha');

                        if (areaHectares > 0) {
                            console.groupEnd();
                            return areaHectares;
                        }
                    }
                }
                // Jika ini adalah MULTIPOLYGON
                else if (cleanWkt.toUpperCase().startsWith('MULTIPOLYGON')) {
                    console.log('Mencoba parse MULTIPOLYGON untuk Leaflet');
                    // Ini memerlukan parsing lebih kompleks - pendekatan sederhana untuk saat ini
                    const mpMatch = cleanWkt.match(/MULTIPOLYGON\s*\(\(\((.*?)\)\)\)/i);
                    if (mpMatch && mpMatch.length > 1) {
                        const coordsStr = mpMatch[1];
                        const coords = coordsStr.split(',').map(pair => {
                            const [lng, lat] = pair.trim().split(/\s+/).map(Number);
                            return [lat, lng]; // Leaflet menggunakan [lat, lng]
                        });

                        const polygon = L.polygon(coords);
                        const areaSqMeters = L.GeometryUtil.geodesicArea(polygon.getLatLngs()[0]);
                        const areaHectares = areaSqMeters / 10000;

                        console.log('Luas multipolygon (Leaflet):', areaSqMeters, 'm²');
                        console.log('Luas multipolygon (ha):', areaHectares, 'ha');

                        if (areaHectares > 0) {
                            console.groupEnd();
                            return areaHectares;
                        }
                    }
                }
            } else {
                console.warn('Leaflet tidak tersedia untuk perhitungan luas');
            }
        } catch (leafletError) {
            console.error('Error saat menghitung dengan Leaflet:', leafletError);
        }

        // Metode 3: Gunakan kalkulator sederhana untuk polygon
        console.log('Mencoba metode 3: Perhitungan manual sederhana');
        try {
            // Extract koordinat dari WKT
            let coords = [];
            if (cleanWkt.toUpperCase().startsWith('POLYGON')) {
                const polygonMatch = cleanWkt.match(/POLYGON\s*\(\((.*)\)\)/i);
                if (polygonMatch && polygonMatch.length > 1) {
                    coords = polygonMatch[1].split(',').map(pair => {
                        const [lng, lat] = pair.trim().split(/\s+/).map(Number);
                        return [lng, lat];
                    });
                }
            } else if (cleanWkt.toUpperCase().startsWith('MULTIPOLYGON')) {
                // Ambil polygon pertama saja
                const mpMatch = cleanWkt.match(/MULTIPOLYGON\s*\(\(\((.*?)\)\)/i);
                if (mpMatch && mpMatch.length > 1) {
                    coords = mpMatch[1].split(',').map(pair => {
                        const [lng, lat] = pair.trim().split(/\s+/).map(Number);
                        return [lng, lat];
                    });
                }
            }

            if (coords.length >= 3) {
                // Hitung luas dengan shoelace formula
                let area = 0;
                for (let i = 0; i < coords.length; i++) {
                    const j = (i + 1) % coords.length;
                    area += coords[i][0] * coords[j][1];
                    area -= coords[j][0] * coords[i][1];
                }
                area = Math.abs(area) / 2;

                // Konversi ke meter persegi dengan pendekatan sederhana (rough)
                // 1 derajat ~ 111,319 meter pada equator
                const areaSqMeters = area * 111319 * 111319;
                const areaHectares = areaSqMeters / 10000;

                console.log('Luas polygon (manual):', areaSqMeters, 'm²');
                console.log('Luas polygon (ha):', areaHectares, 'ha');

                if (areaHectares > 0) {
                    console.groupEnd();
                    return areaHectares;
                }
            }
        } catch (manualError) {
            console.error('Error saat menghitung manual:', manualError);
        }

        // Jika semua metode gagal, kembalikan nilai default
        console.warn('Semua metode gagal menghitung luas area');
        console.groupEnd();
        return 1.0; // Nilai default 1 hektar untuk mencegah error validasi
    } catch (error) {
        console.error('Error utama saat menghitung luas:', error);
        console.groupEnd();
        return 1.0; // Nilai default untuk mencegah error validasi
    }
}

// Fungsi pembantu untuk menghitung luas dari GeoJSON secara manual
function calculateManualArea(geojson) {
    try {
        if (!geojson || !geojson.geometry || !geojson.geometry.coordinates) {
            return 0;
        }

        if (geojson.geometry.type === 'Polygon') {
            // Ambil koordinat exterior ring (index 0)
            const coords = geojson.geometry.coordinates[0];

            // Hitung luas dengan shoelace formula
            let area = 0;
            for (let i = 0; i < coords.length - 1; i++) {
                area += coords[i][0] * coords[i+1][1] - coords[i+1][0] * coords[i][1];
            }
            area = Math.abs(area) / 2;

            // Konversi ke meter persegi dengan pendekatan sederhana (rough)
            // 1 derajat ~ 111,319 meter pada equator
            const areaSqMeters = area * 111319 * 111319;
            return areaSqMeters;
        }

        if (geojson.geometry.type === 'MultiPolygon') {
            let totalArea = 0;

            // Loop melalui semua polygon
            for (const polygon of geojson.geometry.coordinates) {
                // Ambil exterior ring (index 0)
                const coords = polygon[0];

                // Hitung luas dengan shoelace formula
                let area = 0;
                for (let i = 0; i < coords.length - 1; i++) {
                    area += coords[i][0] * coords[i+1][1] - coords[i+1][0] * coords[i][1];
                }
                area = Math.abs(area) / 2;

                // Konversi ke meter persegi dan tambahkan ke total
                const areaSqMeters = area * 111319 * 111319;
                totalArea += areaSqMeters;
            }

            return totalArea;
        }

        return 0;
    } catch (error) {
        console.error('Error dalam calculateManualArea:', error);
        return 0;
    }
}

// ... existing code ...

// Tambahkan event listener untuk auto-update luas area saat geometry berubah
document.addEventListener('DOMContentLoaded', function() {
    const geometryInput = document.getElementById('boundary_geometry');
    const areaInput = document.getElementById('luas_area');

    if (geometryInput && areaInput) {
        // Tambahkan MutationObserver untuk memantau perubahan nilai
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                    // Jika nilai geometri berubah, hitung ulang area
                    const wkt = geometryInput.value;
                    if (wkt) {
                        console.log('Geometry berubah, menghitung ulang luas area');
                        const area = calculateAreaFromWkt(wkt);
                        if (area > 0) {
                            areaInput.value = area.toFixed(4);
                            console.log('Area diperbarui:', area.toFixed(4), 'ha');
                        }
                    }
                }
            });
        });

        // Konfigurasi observer dan mulai observing
        observer.observe(geometryInput, { attributes: true, attributeFilter: ['value'] });

        console.log('Observer terpasang untuk menghitung ulang luas area secara otomatis');

        // Juga tambahkan event listener untuk input langsung
        geometryInput.addEventListener('input', function() {
            const wkt = this.value;
            if (wkt) {
                console.log('Input geometry berubah, menghitung ulang luas area');
                const area = calculateAreaFromWkt(wkt);
                if (area > 0) {
                    areaInput.value = area.toFixed(4);
                    console.log('Area diperbarui dari input event:', area.toFixed(4), 'ha');
                }
            }
        });
    }
});

// Override window.addEventListener untuk open-plantation-modal untuk memastikan perhitungan area berjalan konsisten
const originalAddEventListener = window.addEventListener;
window.addEventListener = function(event, handler, options) {
    if (event === 'open-plantation-modal') {
        const enhancedHandler = function(e) {
            handler(e); // Panggil handler asli

            // Tambahkan perhitungan area dengan delay untuk memastikan form sudah terisi
            setTimeout(function() {
                const geometryInput = document.getElementById('boundary_geometry');
                const areaInput = document.getElementById('luas_area');

                if (geometryInput && geometryInput.value && areaInput) {
                    console.log('Memastikan perhitungan area setelah modal terbuka');
                    const area = calculateAreaFromWkt(geometryInput.value);
                    if (area > 0) {
                        areaInput.value = area.toFixed(4);
                        console.log('Area dipastikan diisi:', area.toFixed(4), 'ha');
                    } else {
                        console.warn('Perhitungan area gagal setelah modal terbuka');
                    }
                }
            }, 500);
        };

        return originalAddEventListener.call(this, event, enhancedHandler, options);
    }

    return originalAddEventListener.call(this, event, handler, options);
};

// ... existing code ...

// Fungsi untuk memastikan nilai WKT selalu diupdate dengan bentuk sebenarnya dari Geoman
function initGeomanWithAreaCalculation() {
    console.log('Initializing Geoman with automatic area calculation');

    if (!map || !map.pm) {
        console.error('Map or Geoman not initialized yet');
        return;
    }

    // Tambahkan event listener untuk perubahan layer
    map.on('pm:edit', function(e) {
        console.log('Shape edited with Geoman');

        const layer = e.layer;

        // Jika ini adalah layer yang sedang ditampilkan di form
        if (window.currentShapeData && window.currentShapeData.layer === layer) {
            console.log('Current edited shape is the active shape in form');

            // Dapatkan WKT baru dan perbarui
            try {
                const newWkt = getWKT(layer);
                window.currentShapeData.wkt = newWkt;

                // Update geometri form jika modal plantation sedang terbuka
                const modalElement = document.getElementById('plantationModalContainer');
                const isModalOpen = modalElement &&
                                   !modalElement.classList.contains('modal-hidden') &&
                                   getComputedStyle(modalElement).display !== 'none';

                if (isModalOpen) {
                    console.log('Plantation modal is open, updating geometry and recalculating area');

                    const geometryInput = document.getElementById('boundary_geometry');
                    const areaInput = document.getElementById('luas_area');

                    if (geometryInput) {
                        // Update nilai dengan WKT baru
                        const formattedWkt = ensureWktFormat(newWkt);
                        geometryInput.value = formattedWkt;
                        console.log('Updated geometry input with new WKT');

                        // Recalculate area
                        if (areaInput) {
                            const area = calculateAreaFromWkt(formattedWkt);
                            if (area > 0) {
                                areaInput.value = area.toFixed(4);
                                console.log('Area recalculated after shape edit:', area.toFixed(4), 'ha');
                            }
                        }
                    }
                }
            } catch (error) {
                console.error('Error updating WKT after shape edit:', error);
            }
        }
    });

    // Tambahkan event listener untuk pembuatan bentuk baru
    map.on('pm:create', function(e) {
        console.log('New shape created with Geoman');

        // Update currentShapeData dan WKT
        const layer = e.layer;
        const wkt = getWKT(layer);

        window.currentShapeData = {
            layer: layer,
            wkt: wkt
        };

        console.log('Current shape data updated with new shape');
    });

    console.log('Geoman events set up for automatic area calculation');
}

// Panggil fungsi inisialisasi pada waktu yang tepat
document.addEventListener('DOMContentLoaded', function() {
    console.log('Setting up automatic area calculation');

    // Pastikan map dan Geoman dimuat terlebih dahulu
    if (typeof map !== 'undefined' && map && typeof map.pm !== 'undefined') {
        console.log('Map and Geoman already available, initializing area calculation');
        initGeomanWithAreaCalculation();
    } else {
        console.log('Waiting for map and Geoman to be loaded');

        // Perlu menunggu event yang menandakan map dan Geoman telah siap
        document.addEventListener('map:ready', function() {
            console.log('Map ready event received, initializing area calculation');

            // Tunggu sedikit agar Geoman benar-benar siap
            setTimeout(function() {
                initGeomanWithAreaCalculation();
            }, 1000);
        });
    }
});

// Override fungsi original terkait pengaturan Geoman untuk menambahkan perhitungan area
const originalInitGeomanDirectly = window.initGeomanDirectly;
window.initGeomanDirectly = function() {
    // Panggil fungsi asli
    if (typeof originalInitGeomanDirectly === 'function') {
        originalInitGeomanDirectly();
    }

    // Tambahkan fungsionalitas perhitungan area
    setTimeout(function() {
        initGeomanWithAreaCalculation();
    }, 500);
};

// ... existing code ...

// Fungsi yang ditingkatkan untuk mengkonversi layer Leaflet ke WKT
function layerToWKT(layer) {
    console.log('Converting layer to WKT format');

    // Jika layer tidak ada
    if (!layer) {
        console.error('Layer is null or undefined');
        return null;
    }

    try {
        let wkt = null;

        // Circle
        if (layer instanceof L.Circle) {
            console.log('Layer is a Circle, converting to POLYGON');

            // Dapatkan titik pusat dan radius
            const center = layer.getLatLng();
            const radius = layer.getRadius();

            // Buat polygon dari circle dengan 32 titik
            const points = 32;
            const coordinates = [];

            for (let i = 0; i < points; i++) {
                const angle = (i / points) * Math.PI * 2;
                const lat = center.lat + (radius / 111319.9) * Math.sin(angle);
                const lng = center.lng + (radius / (111319.9 * Math.cos(center.lat * Math.PI / 180))) * Math.cos(angle);
                coordinates.push([lng, lat]);
            }

            // Tutup polygon dengan menambahkan titik pertama di akhir
            coordinates.push(coordinates[0]);

            // Buat WKT POLYGON
            wkt = 'POLYGON((' + coordinates.map(c => c[0] + ' ' + c[1]).join(',') + '))';
            console.log('Circle converted to POLYGON WKT');
        }
        // Rectangle
        else if (layer instanceof L.Rectangle) {
            console.log('Layer is a Rectangle, converting to POLYGON');

            // Dapatkan bounds dari rectangle
            const bounds = layer.getBounds();
            const ne = bounds.getNorthEast();
            const sw = bounds.getSouthWest();
            const nw = L.latLng(ne.lat, sw.lng);
            const se = L.latLng(sw.lat, ne.lng);

            // Buat polygon dari 4 titik
            const coordinates = [
                [sw.lng, sw.lat],
                [se.lng, se.lat],
                [ne.lng, ne.lat],
                [nw.lng, nw.lat],
                [sw.lng, sw.lat] // Tutup polygon
            ];

            // Buat WKT POLYGON
            wkt = 'POLYGON((' + coordinates.map(c => c[0] + ' ' + c[1]).join(',') + '))';
            console.log('Rectangle converted to POLYGON WKT');
        }
        // Polygon
        else if (layer instanceof L.Polygon) {
            console.log('Layer is a Polygon');

            // Dapatkan coordinates dari polygon
            const latlngs = layer.getLatLngs();

            // Periksa apakah ini multi-polygon atau polygon dengan holes
            if (Array.isArray(latlngs[0]) && Array.isArray(latlngs[0][0])) {
                // Multi-polygon
                console.log('Detected MultiPolygon structure');

                const polygons = [];

                // Proses setiap polygon
                for (let i = 0; i < latlngs.length; i++) {
                    const polygon = latlngs[i];

                    // Proses setiap ring dalam polygon
                    const rings = [];
                    for (let j = 0; j < polygon.length; j++) {
                        const ring = polygon[j];
                        const ringCoords = [];

                        // Proses setiap titik
                        for (let k = 0; k < ring.length; k++) {
                            ringCoords.push(ring[k].lng + ' ' + ring[k].lat);
                        }

                        // Tutup ring jika belum tertutup
                        if (ring[0].lat !== ring[ring.length-1].lat || ring[0].lng !== ring[ring.length-1].lng) {
                            ringCoords.push(ring[0].lng + ' ' + ring[0].lat);
                        }

                        rings.push('(' + ringCoords.join(',') + ')');
                    }

                    polygons.push('(' + rings.join(',') + ')');
                }

                wkt = 'MULTIPOLYGON(' + polygons.join(',') + ')';
                console.log('Created MULTIPOLYGON WKT');
            }
            else if (Array.isArray(latlngs[0]) && !Array.isArray(latlngs[0][0])) {
                // Polygon dengan holes
                console.log('Detected Polygon with holes');

                const rings = [];

                // Proses setiap ring
                for (let i = 0; i < latlngs.length; i++) {
                    const ring = latlngs[i];
                    const ringCoords = [];

                    // Proses setiap titik
                    for (let j = 0; j < ring.length; j++) {
                        ringCoords.push(ring[j].lng + ' ' + ring[j].lat);
                    }

                    // Tutup ring jika belum tertutup
                    if (ring[0].lat !== ring[ring.length-1].lat || ring[0].lng !== ring[ring.length-1].lng) {
                        ringCoords.push(ring[0].lng + ' ' + ring[0].lat);
                    }

                    rings.push('(' + ringCoords.join(',') + ')');
                }

                wkt = 'POLYGON(' + rings.join(',') + ')';
                console.log('Created POLYGON with holes WKT');
            }
            else {
                // Simple polygon
                console.log('Simple Polygon structure');

                const coordinates = [];

                // Proses setiap titik
                for (let i = 0; i < latlngs.length; i++) {
                    coordinates.push(latlngs[i].lng + ' ' + latlngs[i].lat);
                }

                // Tutup polygon jika belum tertutup
                if (latlngs[0].lat !== latlngs[latlngs.length-1].lat || latlngs[0].lng !== latlngs[latlngs.length-1].lng) {
                    coordinates.push(latlngs[0].lng + ' ' + latlngs[0].lat);
                }

                wkt = 'POLYGON((' + coordinates.join(',') + '))';
                console.log('Created simple POLYGON WKT');
            }
        }
        // LineString - konversi ke polygon dengan buffer
        else if (layer instanceof L.Polyline && !(layer instanceof L.Polygon)) {
            console.log('Layer is a LineString, converting to POLYGON with buffer');

            const latlngs = layer.getLatLngs();
            const coordinates = [];

            for (let i = 0; i < latlngs.length; i++) {
                coordinates.push([latlngs[i].lng, latlngs[i].lat]);
            }

            // Coba gunakan turf.js untuk membuat buffer jika tersedia
            if (typeof turf !== 'undefined') {
                console.log('Using turf.js to create buffer around LineString');

                const line = {
                    type: 'Feature',
                    geometry: {
                        type: 'LineString',
                        coordinates: coordinates
                    },
                    properties: {}
                };

                // Buat buffer sekitar garis (10 meter)
                const buffered = turf.buffer(line, 10, {units: 'meters'});
                const bufferCoords = buffered.geometry.coordinates[0];

                // Konversi buffer ke WKT
                wkt = 'POLYGON((' + bufferCoords.map(c => c[0] + ' ' + c[1]).join(',') + '))';
                console.log('LineString buffered to POLYGON WKT');
            } else {
                // Fallback - konversi LineString ke POLYGON sederhana
                console.warn('Turf.js not available, converting LineString to basic POLYGON');

                // Buat polygon sederhana dengan menebalkan garis
                const bufferCoordinates = [];

                for (let i = 0; i < coordinates.length; i++) {
                    // Tambahkan offset kecil untuk membuat lebar
                    bufferCoordinates.push((coordinates[i][0] - 0.0001) + ' ' + (coordinates[i][1] - 0.0001));
                }

                for (let i = coordinates.length - 1; i >= 0; i--) {
                    // Tambahkan offset kecil ke arah lain
                    bufferCoordinates.push((coordinates[i][0] + 0.0001) + ' ' + (coordinates[i][1] + 0.0001));
                }

                // Tutup polygon
                bufferCoordinates.push(bufferCoordinates[0]);

                wkt = 'POLYGON((' + bufferCoordinates.join(',') + '))';
                console.log('Created basic polygon from LineString');
            }
        }
        // Marker - konversi ke polygon kecil
        else if (layer instanceof L.Marker) {
            console.log('Layer is a Marker, converting to small POLYGON');

            const latlng = layer.getLatLng();
            const buffer = 0.0001; // Buffer kecil sekitar titik

            // Buat polygon kecil sekitar marker
            const coordinates = [
                [(latlng.lng - buffer) + ' ' + (latlng.lat - buffer)],
                [(latlng.lng + buffer) + ' ' + (latlng.lat - buffer)],
                [(latlng.lng + buffer) + ' ' + (latlng.lat + buffer)],
                [(latlng.lng - buffer) + ' ' + (latlng.lat + buffer)],
                [(latlng.lng - buffer) + ' ' + (latlng.lat - buffer)] // Tutup polygon
            ];

            wkt = 'POLYGON((' + coordinates.join(',') + '))';
            console.log('Marker converted to small POLYGON WKT');
        }
        // FeatureGroup or LayerGroup - proses setiap layer
        else if (layer instanceof L.FeatureGroup || layer instanceof L.LayerGroup) {
            console.log('Layer is a FeatureGroup or LayerGroup');

            const layerWkts = [];

            // Proses setiap layer dalam grup
            layer.eachLayer(function(subLayer) {
                const subWkt = layerToWKT(subLayer);
                if (subWkt) {
                    layerWkts.push(subWkt);
                }
            });

            // Jika hanya ada 1 WKT, gunakan itu
            if (layerWkts.length === 1) {
                wkt = layerWkts[0];
                console.log('Using single WKT from group');
            }
            // Jika banyak dan semua polygon, buat MULTIPOLYGON
            else if (layerWkts.length > 1) {
                // Ekstrak bagian dalam dari setiap polygon
                const polygonParts = layerWkts.map(w => {
                    if (w.startsWith('POLYGON((')) {
                        return w.substring(9, w.length - 2);
                    }
                    else if (w.startsWith('POLYGON(')) {
                        return w.substring(8, w.length - 1);
                    }
                    return null;
                }).filter(p => p !== null);

                if (polygonParts.length > 0) {
                    wkt = 'MULTIPOLYGON(((' + polygonParts.join(')),((') + ')))';
                    console.log('Created MULTIPOLYGON from group layers');
                }
            }
        }

        // Jika masih null, coba metode default
        if (!wkt && typeof getWKT === 'function') {
            console.log('Using fallback getWKT function');
            wkt = getWKT(layer);
        }

        // Validasi WKT akhir
        if (wkt) {
            // Pastikan itu dimulai dengan POLYGON atau MULTIPOLYGON
            const upperWkt = wkt.toUpperCase();
            if (!upperWkt.startsWith('POLYGON') && !upperWkt.startsWith('MULTIPOLYGON') &&
                !upperWkt.startsWith('SRID=')) {
                console.error('Invalid WKT format after conversion:', wkt.substring(0, 50) + '...');
                return null;
            }

            // Log hasil WKT
            console.log('Final WKT format:', wkt.substring(0, 50) + '...');
            return wkt;
        }

        console.error('Failed to convert layer to WKT');
        return null;
    } catch (error) {
        console.error('Error converting layer to WKT:', error);
        return null;
    }
}

// Fungsi yang ditingkatkan untuk mengirimkan form data blok kebun
function submitPlantationForm() {
    console.log('Submitting plantation form...');

    // Check if the form exists
    const plantationForm = document.getElementById('plantationForm');
    if (!plantationForm) {
        console.error('Plantation form not found!');
        return;
    }

    // Get the form fields
    const nameInput = document.getElementById('name');
    const geometryInput = document.getElementById('boundary_geometry');
    const areaInput = document.getElementById('luas_area');
    const formMethod = document.getElementById('plantation_form_method').value || 'POST';
    const plantationId = document.getElementById('plantation_id').value;

    // Validate required fields
    if (!nameInput || !nameInput.value) {
        alert('Nama blok kebun harus diisi!');
        return;
    }

    if (!geometryInput || !geometryInput.value) {
        alert('Anda harus menggambar area blok kebun terlebih dahulu!');
        return;
    }

    // Validate WKT format
    const wkt = geometryInput.value;
    if (!wkt.toUpperCase().startsWith('POLYGON') && !wkt.toUpperCase().startsWith('MULTIPOLYGON') &&
        !wkt.toUpperCase().startsWith('SRID=')) {
        alert('Format geometri tidak valid. Harus berupa POLYGON atau MULTIPOLYGON.');
        return;
    }

    // If area is empty or zero, try to calculate it
    if (!areaInput.value || parseFloat(areaInput.value) === 0) {
        console.log('Area kosong, menghitung dari geometri...');
        const calculatedArea = calculateAreaFromWkt(wkt);
        if (calculatedArea > 0) {
            console.log('Area berhasil dihitung:', calculatedArea);
            areaInput.value = calculatedArea.toFixed(4);
        } else {
            alert('Perhitungan luas area gagal. Silakan masukkan luas area secara manual.');
            areaInput.focus();
            return;
        }
    } else {
        // Pastikan format luas area konsisten dengan 4 angka desimal
        const areaValue = parseFloat(areaInput.value);
        if (!isNaN(areaValue)) {
            areaInput.value = areaValue.toFixed(4);
            console.log('Luas area diformat ulang untuk konsistensi:', areaInput.value);
        }
    }

    // Show loading screen
    if (typeof showLoading === 'function') {
        showLoading('Menyimpan data blok kebun...');
    }

    // Prepare the data
    const formData = new FormData(plantationForm);
    let url = plantationForm.getAttribute('action');

    if (formMethod.includes('PUT') || formMethod.includes('PATCH')) {
        url = `${url}/${plantationId}`;
    }

    const jsonData = {};
    formData.forEach((value, key) => {
        jsonData[key] = value;
    });

    console.log('Sending plantation data:', jsonData);

    // Prepare fetch options based on method
    const fetchOptions = {
        method: formMethod === 'PUT' || formMethod.includes('PATCH') ? 'PUT' : 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(jsonData)
    };

    // Send the request
    fetch(url, fetchOptions)
    .then(response => {
        if (!response.ok) {
            // Coba ambil pesan error dari respons json jika ada
            return response.json().then(errData => {
                throw new Error(errData.message || `HTTP error! status: ${response.status}`);
            }).catch(err => {
                // Jika tidak bisa parse JSON, gunakan pesan default
                if (err instanceof SyntaxError) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                throw err;
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Plantation saved successfully:', data);

        // Hide the modal
        window.dispatchEvent(new CustomEvent('close-plantation-modal'));

        // Sembunyikan loading indicator jika ada
        if (typeof hideLoading === 'function') {
            hideLoading();
        }

        // Tampilkan pesan sukses
        alert(data.message || 'Blok kebun berhasil disimpan!');

        // Tambahkan blok kebun yang baru disimpan ke peta
        const savedPlantation = data.data;
        if (savedPlantation && savedPlantation.id) {
            console.log('Menambahkan blok kebun yang disimpan ke peta:', savedPlantation);
            try {
                // Inisialisasi layer group jika belum ada
                if (!window.plantationLayerGroup) {
                    window.plantationLayerGroup = L.layerGroup();
                    window.plantationLayerGroup.addTo(map);

                    // Tambahkan ke layer control jika ada
                    if (window.baseLayerControl) {
                        window.baseLayerControl.addOverlay(window.plantationLayerGroup, 'Blok Kebun');
                    } else if (window.layerControl) {
                        window.layerControl.addOverlay(window.plantationLayerGroup, 'Blok Kebun');
                    }
                }


                // Jika sedang mengedit, hapus layer lama terlebih dahulu
                if (formMethod.includes('PATCH') || formMethod.includes('PUT')) {
                    // Hapus layer lama jika ada
                    if (window.plantationLayers && window.plantationLayers[savedPlantation.id]) {
                        plantationLayerGroup.removeLayer(window.plantationLayers[savedPlantation.id]);
                        delete window.plantationLayers[savedPlantation.id];
                        console.log('Layer lama berhasil dihapus');
                    }
                }

                // Extract geojson dari hasil yang disimpan
                let geometry = savedPlantation.geometry;
                console.log('Geometry dari server:', geometry);

                // Remove SRID if present
                if (geometry.toUpperCase().startsWith('SRID=')) {
                    geometry = geometry.substring(geometry.indexOf(';') + 1);
                }

                // Konversi ke GeoJSON untuk ditampilkan
                const geojson = parseWKTtoGeoJSON(geometry);
                if (geojson) {
                    // Buat style untuk layer
                    const layerStyle = {
                        color: '#3388ff',
                        weight: 4,
                        opacity: 0.9,
                        fillOpacity: 0.15,
                        fillColor: '#3388ff',
                        pane: 'plantationsPane' // Gunakan pane dengan z-index rendah
                    };

                    // Buat layer dan tambahkan ke layer group
                    const newLayer = L.geoJSON(geojson, {
                        style: layerStyle,
                        pane: 'overlayPane', // Gunakan pane default untuk layer overlay
                        onEachFeature: function(feature, layer) {
                            // Tambahkan popup jika diperlukan
                            const popupContent = createPlantationPopupContent(savedPlantation);
                            layer.bindPopup(popupContent);

                            // Tandai sebagai disimpan
                            layer.isSaved = true;
                            layer.plantationId = savedPlantation.id;
                            layer.plantationData = savedPlantation;
                        }
                    });

                    // Jangan tambahkan ke map langsung, gunakan layer group
                    // Tambahkan ke layer group dan simpan referensi
                    newLayer.addTo(plantationLayerGroup);

                    // Buka popup secara otomatis setelah layer ditambahkan
                    newLayer.eachLayer(function(layer) {
                        if (layer.openPopup) {
                            layer.openPopup();
                            return false; // Hanya buka popup pertama jika ada banyak
                        }
                    });

                    // Simpan referensi ke dua tempat untuk konsistensi
                    window.plantationLayers[savedPlantation.id] = newLayer;

                    // Pastikan juga layer tidak ada yang di-add langsung ke map
                    if (window.plantationLayersMap && window.plantationLayersMap[savedPlantation.id]) {
                        if (map.hasLayer(window.plantationLayersMap[savedPlantation.id])) {
                            map.removeLayer(window.plantationLayersMap[savedPlantation.id]);
                        }
                        delete window.plantationLayersMap[savedPlantation.id];
                    }

                    console.log('Blok kebun berhasil ditambahkan ke layer group');

                    // Zoom ke layer baru
                    map.fitBounds(newLayer.getBounds());

                    // Pastikan layer pohon berada di atas blok kebun
                    ensureTreeLayerOnTop();
                } else {
                    console.error('Gagal mengkonversi geometry ke GeoJSON');
                }
            } catch (err) {
                console.error('Error menambahkan blok kebun ke peta:', err);
            }
        } else {
            console.warn('Data blok kebun tidak lengkap, mencoba memuat ulang semua blok');
            // Reload all plantations if we don't have complete data
            loadExistingPlantations();
        }

    })
    .catch(error => {
        console.error('Error saving plantation:', error);

        // Sembunyikan loading indicator jika ada
        if (typeof hideLoading === 'function') {
            hideLoading();
        }

        // Tampilkan error message
        alert('Gagal menyimpan blok kebun: ' + error.message);
    });
}

// ... existing code ...

// Fungsi untuk memastikan layer pohon berada di atas layer kebun
// Variable untuk mengontrol interval log
let lastLayerLogTime = 0;
// Flag untuk memastikan pesan sukses hanya ditampilkan sekali
let treesPositionMessageShown = false;
// Flag untuk memastikan pesan memastikan layer pohon hanya ditampilkan sekali
let treesPositionCheckMessageShown = false;

function ensureTreeLayerOnTop() {
    // Batasi log hanya setiap 5 detik untuk mengurangi spam
    const now = Date.now();
    const logAllowed = (now - lastLayerLogTime) > 5000; // 5 detik

    if (logAllowed && !treesPositionCheckMessageShown) {
        console.log('Memastikan layer pohon berada di atas layer kebun');
        treesPositionCheckMessageShown = true;
        lastLayerLogTime = now;
    }

    // Periksa apakah drawnItems (layer pohon) dan plantationLayerGroup ada
    if (typeof drawnItems !== 'undefined' && drawnItems &&
        typeof window.plantationLayerGroup !== 'undefined' && window.plantationLayerGroup) {

        try {
            // Cara 1: Atur ulang pane dan z-index
            if (map.getPane('treesPane') && map.getPane('plantationsPane')) {
                // Pastikan pane pohon memiliki z-index lebih tinggi
                map.getPane('treesPane').style.zIndex = 650;
                map.getPane('plantationsPane').style.zIndex = 400;
            }

            // Cara 2: Hapus dan tambahkan kembali layer pohon ke peta agar selalu berada di atas
            if (map.hasLayer(drawnItems)) {
                // Simpan semua marker/layer penting
                const tempLayers = [];
                drawnItems.eachLayer(function(layer) {
                    tempLayers.push(layer);
                });

                map.removeLayer(drawnItems);
                drawnItems.options = drawnItems.options || {};
                drawnItems.options.pane = 'treesPane';
                drawnItems.addTo(map);

                // Tampilkan pesan sukses hanya sekali
                if (logAllowed && !treesPositionMessageShown) {
                    console.log('Layer pohon berhasil diatur ke posisi paling atas');
                    treesPositionMessageShown = true;
                }
            }

            // Cara 3: Atur z-index container langsung
            try {
                if (drawnItems._container) {
                    drawnItems._container.style.zIndex = 650;
                }

                if (window.plantationLayerGroup._container) {
                    window.plantationLayerGroup._container.style.zIndex = 400;
                }

                // Atur langsung z-index semua layer pohon
                drawnItems.eachLayer(function(layer) {
                    if (layer._path) {
                        layer._path.style.zIndex = 650;
                    }
                    if (layer._container) {
                        layer._container.style.zIndex = 650;
                    }
                });

                // Atur langsung z-index semua layer kebun
                if (window.plantationLayers) {
                    Object.values(window.plantationLayers).forEach(function(plantLayer) {
                        if (plantLayer._container) {
                            plantLayer._container.style.zIndex = 400;
                        }
                        if (plantLayer._path) {
                            plantLayer._path.style.zIndex = 400;
                        }
                        // Untuk layer GeoJSON
                        plantLayer.eachLayer && plantLayer.eachLayer(function(subLayer) {
                            if (subLayer._path) {
                                subLayer._path.style.zIndex = 400;
                            }
                        });
                    });
                }
            } catch (zErr) {
                if (logAllowed) {
                    console.warn('Tidak dapat mengatur z-index layer:', zErr);
                }
            }

            // Cara 4: Perbarui semua marker/layer tree dengan pane yang benar
            try {
                drawnItems.eachLayer(function(layer) {
                    layer.options = layer.options || {};
                    layer.options.pane = 'treesPane';
                    if (layer._pane && layer._pane !== 'treesPane') {
                        // Jika layer sudah di-render dengan pane lain
                        const parentNode = layer._path && layer._path.parentNode;
                        if (parentNode) {
                            // Pindahkan elemen SVG ke pane yang benar
                            parentNode.removeChild(layer._path);
                            map.getPane('treesPane').appendChild(layer._path);
                            layer._pane = 'treesPane';
                        }
                    }
                });
            } catch (e) {
                if (logAllowed) {
                    console.warn('Gagal memperbarui pane untuk layer pohon:', e);
                }
            }

        } catch (e) {
            if (logAllowed) {
                console.error('Error mengatur posisi layer pohon:', e);
            }
        }
    } else if (logAllowed && !treesPositionCheckMessageShown) {
        console.log('Layer pohon atau layer kebun belum tersedia');
        treesPositionCheckMessageShown = true;
    }
}

// Fungsi untuk menginisialisasi layer group blok kebun dan menambahkannya ke layer control
function initPlantationLayerGroup() {
    console.log('Initializing plantation layer group');

    // Hapus layer lama jika ada di map tapi tidak di layer group
    if (window.plantationLayersMap) {
        // Hapus semua layer yang terdaftar langsung di map
        for (const id in window.plantationLayersMap) {
            if (map.hasLayer(window.plantationLayersMap[id])) {
                map.removeLayer(window.plantationLayersMap[id]);
                console.log('Removed plantation layer from map:', id);
            }
        }
        // Bersihkan referensi layer map
        window.plantationLayersMap = {};
    }

    // Inisialisasi layer group jika belum ada
    if (!window.plantationLayerGroup) {
        window.plantationLayerGroup = L.layerGroup();
        console.log('Created new plantation layer group');

        // Atur opsi pane/z-index untuk memastikan plantation ada di bawah tree
        try {
            if (!map.getPane('plantationsPane')) {
                map.createPane('plantationsPane');
                map.getPane('plantationsPane').style.zIndex = 400; // Layer pohon biasanya di 600-650
            }
            window.plantationLayerGroup.options = {
                pane: 'plantationsPane'
            };
        } catch (e) {
            console.warn('Tidak dapat membuat pane untuk plantation:', e);
        }

        // Tambahkan layer group ke map
        window.plantationLayerGroup.addTo(map);
        console.log('Added plantation layer group to map');
    } else if (!map.hasLayer(window.plantationLayerGroup)) {
        // Jika layer group sudah ada tapi belum ditambahkan ke map
        window.plantationLayerGroup.addTo(map);
        console.log('Re-added existing plantation layer group to map');
    }

    // Cek apakah kita perlu mendaftarkan ke layer control
    let layerControlFound = false;

    // Pertama cek apakah ada baseLayerControl global
    if (typeof baseLayerControl !== 'undefined' && baseLayerControl) {
        console.log('Found global baseLayerControl, using it');
        window.baseLayerControl = baseLayerControl;
        layerControlFound = true;
    }

    // Cek jika ada baseLayerControl di window
    if (window.baseLayerControl) {
        console.log('Using existing window.baseLayerControl');

        // Cek apakah plantation layer sudah ada di baseLayerControl
        let found = false;
        if (window.baseLayerControl._layers) {
            for (const layer of window.baseLayerControl._layers) {
                if (layer.layer === window.plantationLayerGroup) {
                    found = true;
                    console.log('Plantation layer already exists in baseLayerControl');
                    break;
                }
            }
        }

        if (!found) {
            // Tambahkan layer ke baseLayerControl
            window.baseLayerControl.addOverlay(window.plantationLayerGroup, 'Blok Kebun');
            console.log('Added plantation layer to baseLayerControl');
        }

        layerControlFound = true;
    }

    // Cek jika ada layerControl di window
    if (!layerControlFound && window.layerControl) {
        console.log('Using existing window.layerControl');

        // Cek apakah plantation layer sudah ada di layerControl
        let found = false;
        if (window.layerControl._layers) {
            for (const layer of window.layerControl._layers) {
                if (layer.layer === window.plantationLayerGroup) {
                    found = true;
                    console.log('Plantation layer already exists in layerControl');
                    break;
                }
            }
        }

        if (!found) {
            // Tambahkan layer ke layerControl
            window.layerControl.addOverlay(window.plantationLayerGroup, 'Blok Kebun');
            console.log('Added plantation layer to layerControl');
        }

        layerControlFound = true;
    }

    // Jika tidak ada layer control yang ditemukan, buat yang baru
    if (!layerControlFound && control_layers) {
        console.log('Adding to existing control_layers');
        control_layers.addOverlay(window.plantationLayerGroup, 'Blok Kebun');
        layerControlFound = true;
    }

    // Inisialisasi koleksi layer jika belum ada
    if (!window.plantationLayers) {
        window.plantationLayers = {};
    }

    // Pastikan layer pohon tetap di atas
    setTimeout(ensureTreeLayerOnTop, 100);

    return window.plantationLayerGroup;
}

// ... existing code ...

// Pastikan layer pohon berada di atas ketika dokumen dimuat
document.addEventListener('DOMContentLoaded', function() {
    // Pastikan kode ini hanya dijalankan setelah semua layer dimuat
    document.addEventListener('map-layers-loaded', function() {
        console.log('Map layers loaded, ensuring tree layer is on top');

        // Inisialisasi layer group blok kebun
        initPlantationLayerGroup();

        // Pastikan layer pohon di atas
        setTimeout(ensureTreeLayerOnTop, 500); // Berikan waktu untuk semua layer dimuat
    });

    // Migrasi layer lama yang mungkin ditambahkan langsung ke map
    setTimeout(function() {
        console.log('Checking for orphaned plantation layers');

        // Buat objek untuk menampung layer yang tidak terdaftar
        if (!window.plantationLayersMap) {
            window.plantationLayersMap = {};
        }

        // Cek semua layer di map
        map.eachLayer(function(layer) {
            // Cek apakah layer ini adalah GeoJSON dan memiliki plantationId
            if (layer.feature && layer.plantationId && !window.plantationLayers[layer.plantationId]) {
                console.log('Found orphaned plantation layer:', layer.plantationId);

                // Simpan referensi
                window.plantationLayersMap[layer.plantationId] = layer;
            }
        });

        // Jika ditemukan layer lama, gunakan fungsi initPlantationLayerGroup untuk membersihkan
        if (Object.keys(window.plantationLayersMap).length > 0) {
            console.log('Cleaning up orphaned plantation layers');
            initPlantationLayerGroup();
        }
    }, 1000);
});

// ... existing code ...

// Fungsi untuk mendaftarkan layer kebun pada layer control yang sudah ada
function registerPlantationToLayerControl() {
    if (!window.plantationLayerGroup) {
        console.warn('No plantation layer group found to register');
        return false;
    }

    // Cek apakah baseLayerControl sudah ada
    if (window.baseLayerControl) {
        // Cek apakah layer kebun sudah terdaftar
        let registered = false;
        if (window.baseLayerControl._layers) {
            for (let i = 0; i < window.baseLayerControl._layers.length; i++) {
                const layer = window.baseLayerControl._layers[i];
                if (layer.layer === window.plantationLayerGroup) {
                    registered = true;
                    console.log('Plantation layer already registered in baseLayerControl');
                    break;
                }
            }
        }

        if (!registered) {
            // Tambahkan ke baseLayerControl
            window.baseLayerControl.addOverlay(window.plantationLayerGroup, 'Blok Kebun');
            console.log('Registered plantation layer to existing baseLayerControl');
        }
        return true;
    }

    // Cek apakah layerControl sudah ada
    if (window.layerControl) {
        // Cek apakah layer kebun sudah terdaftar
        let registered = false;
        if (window.layerControl._layers) {
            for (let i = 0; i < window.layerControl._layers.length; i++) {
                const layer = window.layerControl._layers[i];
                if (layer.layer === window.plantationLayerGroup) {
                    registered = true;
                    console.log('Plantation layer already registered in layerControl');
                    break;
                }
            }
        }

        if (!registered) {
            // Tambahkan ke layerControl
            window.layerControl.addOverlay(window.plantationLayerGroup, 'Blok Kebun');
            console.log('Registered plantation layer to existing layerControl');
        }
        return true;
    }

    // Tidak ada layer control yang ditemukan
    console.warn('No existing layer control found');
    return false;
}

// Tambahkan panggilan ke registerPlantationToLayerControl setelah dokumen dimuat
document.addEventListener('DOMContentLoaded', function() {
    // Setelah semua komponen map dimuat
    setTimeout(function() {
        // Coba daftarkan layer kebun ke layer control yang ada
        registerPlantationToLayerControl();
    }, 2000);
});

// Event listener untuk memastikan blok kebun dimuat saat map siap
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM content loaded, setting up plantation loading');

    // Cek jika map sudah diinisialisasi
    if (typeof map !== 'undefined' && map) {
        console.log('Map already available, loading plantations immediately');
        // Inisialisasi layer group kebun
        initPlantationLayerGroup();
        // Muat data kebun dari server
        loadExistingPlantations();
    } else {
        console.log('Map not available yet, waiting for map-initialized event');
        // Tunggu event map-initialized
        document.addEventListener('map-initialized', function() {
            console.log('Map initialized event received, loading plantations');
            // Inisialisasi layer group kebun
            initPlantationLayerGroup();
            // Muat data kebun dari server
            loadExistingPlantations();
        });

        // Tambahkan timer fallback jika event tidak terpicu
        setTimeout(function() {
            if (typeof map !== 'undefined' && map && Object.keys(window.plantationLayers || {}).length === 0) {
                console.log('Fallback timer: loading plantations');
                // Inisialisasi layer group kebun
                initPlantationLayerGroup();
                // Muat data kebun dari server
                loadExistingPlantations();
            }
        }, 3000);
    }

    // Pastikan tree layer berada di atas ketika semua layer dimuat
    document.addEventListener('map-layers-loaded', function() {
        console.log('Map layers loaded, ensuring tree layer is on top');

        // Inisialisasi layer group blok kebun
        initPlantationLayerGroup();

        // Pastikan layer pohon di atas
        setTimeout(ensureTreeLayerOnTop, 500);
    });
});

// Trigger event map-initialized saat map siap
document.addEventListener('DOMContentLoaded', function() {
    // Cek jika map sudah ada
    if (typeof map !== 'undefined' && map) {
        // Trigger event map-initialized
        setTimeout(function() {
            document.dispatchEvent(new CustomEvent('map-initialized'));
            console.log('Triggered map-initialized event');
        }, 500);
    } else {
        // Cek setiap 500ms sampai map siap
        const interval = setInterval(function() {
            if (typeof map !== 'undefined' && map) {
                clearInterval(interval);
                document.dispatchEvent(new CustomEvent('map-initialized'));
                console.log('Triggered map-initialized event (delayed)');
            }
        }, 500);

        // Batalkan pengecekan setelah 10 detik untuk mencegah loop tak terbatas
        setTimeout(function() {
            clearInterval(interval);
        }, 10000);
    }
});

// Variabel throttling untuk ensureTreeLayerOnTop
let treeLayerThrottleTimer = null;

// Fungsi throttle untuk ensureTreeLayerOnTop
function throttledEnsureTreeLayerOnTop(delay = 1000) {
    if (treeLayerThrottleTimer !== null) {
        clearTimeout(treeLayerThrottleTimer);
    }

    treeLayerThrottleTimer = setTimeout(function() {
        ensureTreeLayerOnTop();
        treeLayerThrottleTimer = null;
    }, delay);
}

// Tambahkan pemeriksaan berkala hanya untuk layer pohon
setInterval(function() {
    // Kode untuk otomatis menambahkan kembali plantation layer group dihapus
    // agar visibilitas layer dapat dikontrol manual melalui layer control

    // Pastikan layer pohon tetap di atas
    ensureTreeLayerOnTop();
}, 10000); // Cek setiap 10 detik

// Function to load existing trees from the server
function loadExistingTrees(forceReload = false) {
    console.log('Loading existing trees... forceReload:', forceReload);

    // Simpan popup yang sedang terbuka
    let openPopups = [];
    drawnItems.eachLayer(function(layer) {
        if (layer.isPopupOpen()) {
            openPopups.push({
                id: layer.treeData?.id || layer.feature?.properties?.id,
                latlng: layer.getPopup()._latlng
            });
        }
    });

    // Clear existing layers
    drawnItems.clearLayers();

    // Tambahkan timestamp untuk mencegah cache
    const timestamp = new Date().getTime();

    // Tambahkan parameter forceReload ke URL jika diperlukan
    const reloadParam = forceReload ? '&force=true' : '';

    // Status indikator (tanpa animasi)
    const statusDiv = document.createElement('div');
    statusDiv.id = 'treeLoadStatus';
    statusDiv.style.cssText = 'position:fixed;bottom:10px;right:10px;background:rgba(255,255,255,0.9);padding:8px 12px;border-radius:4px;z-index:1000;font-size:13px;border:1px solid #ccc;';
    document.body.appendChild(statusDiv);

    // Simpan semua data pohon untuk referensi
    window.allTreesData = [];

    // Fetch trees data from the server
    fetch(`/trees/get-all?_=${timestamp}&nocache=true${reloadParam}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Periksa format respons
            let trees = data;
            if (data && typeof data === 'object' && data.hasOwnProperty('success')) {
                if (!data.success) {
                    console.error('Error loading trees:', data.message);
                    return;
                }
                trees = data.data;
            }

            if (!trees || !Array.isArray(trees) || trees.length === 0) {
                console.log('No trees found');
                document.body.removeChild(statusDiv);
                return;
            }

            // Simpan data semua pohon untuk referensi
            window.allTreesData = trees;

            // Simpan layer berdasarkan ID untuk membuka popup nanti
            let layersById = {};

            // Proses data dalam batch untuk menghindari browser freeze
            const batchSize = 100; // Proses 100 pohon dalam satu batch
            const totalTrees = trees.length;
            let processedCount = 0;

            // Buat layer group untuk pohon
            if (!window.treeLayerGroup) {
                window.treeLayerGroup = L.layerGroup([], { pane: 'treesPane', renderer: L.canvas() }).addTo(map);
            } else {
                window.treeLayerGroup.clearLayers(); // Tetap bersihkan layer jika sudah ada, renderer akan tetap
            }

            // Tambahkan fungsi untuk hanya menampilkan pohon yang terlihat dalam viewport
            function updateVisibleTrees() {
                if (!window.allTreesData || !window.allTreesData.length) return;

                // Dapatkan batas viewport saat ini
                const bounds = map.getBounds();

                // Status update
                statusDiv.textContent = "Memperbarui pohon yang terlihat...";

                // Hapus layer lama secara bertahap untuk menghindari blocking
                window.treeLayerGroup.eachLayer(function(layer) {
                    // Sembunyikan dulu agar tidak memakan resource
                    if (layer.options && typeof layer.setStyle === 'function') {
                        layer.setStyle({opacity: 0, fillOpacity: 0});
                    }
                });

                // Tunggu sebentar lalu bersihkan semua layer
                setTimeout(() => {
                    window.treeLayerGroup.clearLayers();

                    // Filter pohon yang ada di dalam viewport
                    let visibleTrees = [];

                    for (let i = 0; i < window.allTreesData.length; i++) {
                        const tree = window.allTreesData[i];
                        if (!tree.canopy_geometry) continue;

                        try {
                            // Parse WKT hanya untuk mendapatkan koordinat
                    const parsedGeometry = parseWKT(tree.canopy_geometry);
                            if (!parsedGeometry) continue;

                            // Untuk polygon, gunakan titik pertama sebagai referensi
                            let lat, lng;
                            if (parsedGeometry.type === 'polygon' && parsedGeometry.coordinates && parsedGeometry.coordinates.length) {
                                lat = parsedGeometry.coordinates[0][0];
                                lng = parsedGeometry.coordinates[0][1];
                            } else if (parsedGeometry.type === 'point' && parsedGeometry.coordinates) {
                                lat = parsedGeometry.coordinates[0];
                                lng = parsedGeometry.coordinates[1];
                            } else {
                                continue;
                            }

                            // Periksa apakah pohon ada dalam viewport
                            if (bounds.contains([lat, lng])) {
                                visibleTrees.push(tree);
                            }
                        } catch (e) {
                            // Abaikan error
                        }
                    }

                    // Update status
                    statusDiv.textContent = `Memuat ${visibleTrees.length} pohon dari total ${window.allTreesData.length}`;

                    // Tambahkan pohon yang terlihat ke peta
                    addVisibleTreesToMap(visibleTrees);
                }, 50);
            }

            // Fungsi untuk menambahkan pohon yang terlihat ke peta
            function addVisibleTreesToMap(visibleTrees) {
                if (!visibleTrees.length) {
                    // Semua selesai
                    setTimeout(() => {
                        if (document.body.contains(statusDiv)) {
                            document.body.removeChild(statusDiv);
                        }
                    }, 2000);
                    return;
                }

                // Bagi menjadi batch kecil
                const batchSize = 50;
                const currentBatch = visibleTrees.splice(0, batchSize);

                // Proses batch saat ini
                currentBatch.forEach(tree => {
                    try {
                        if (!tree.canopy_geometry) return;

                        // Parse WKT to Leaflet coordinates secara efisien
                        const parsedGeometry = parseWKT(tree.canopy_geometry);
                        if (!parsedGeometry) return;

                    let layer;

                    // Buat layer berdasarkan tipe geometri
                    if (parsedGeometry.type === 'point') {
                        // Buat marker untuk POINT
                            layer = L.marker(parsedGeometry.coordinates, {
                                // Tambahkan options untuk marker yang lebih efisien
                                interactive: true,
                                pane: 'treesPane' // Tambahkan pane
                            });
                    }
                    else if (parsedGeometry.type === 'polygon') {
                            // Buat polygon untuk POLYGON dengan style minimalis
                            layer = L.polygon(parsedGeometry.coordinates, {
                                weight: 1,
                                opacity: 0.7,
                                fillOpacity: 0.1, // Kurangi fill opacity untuk meningkatkan performa rendering
                                interactive: true, // Pastikan interaktif untuk popup
                                pane: 'treesPane' // Tambahkan pane
                            });
                    }
                    else {
                        return;
                    }

                        // Hanya simpan ID pohon, bukan seluruh objek
                    layer.treeId = tree.id;

                        // Set properti minimum yang diperlukan
                        const requiredProps = {
                            id: tree.id,
                            varietas: tree.varietas,
                            tahun_tanam: tree.tahun_tanam,
                            health_status: tree.health_status,
                            fase: tree.fase
                        };
                        layer.treeData = requiredProps;

                    // Add popup with tree info
                    const popupContent = createPopupContent(tree);

                        // Gunakan popup options untuk meningkatkan performa
                        layer.bindPopup(popupContent, {
                            autoPan: false, // Mencegah panning otomatis yang berat
                            closeButton: true,
                            maxWidth: 250
                        });

                        // Set layer style based on tree health (untuk polygon)
                    if (parsedGeometry.type === 'polygon') {
                        const healthColor = getHealthColor(tree.health_status);
                        layer.setStyle({
                            fillColor: healthColor,
                                color: healthColor
                        });
                    }

                        // Add layer ke layer group khusus pohon
                        window.treeLayerGroup.addLayer(layer);

                    // Simpan layer berdasarkan ID
                    layersById[tree.id] = layer;
                } catch (error) {
                        // Abaikan error individual
                    }
                });

                // Jika masih ada pohon yang perlu diproses, jadwalkan batch berikutnya
                if (visibleTrees.length > 0) {
                    setTimeout(() => addVisibleTreesToMap(visibleTrees), 10);
                } else {
                    // Selesai memproses semua data
                    setTimeout(() => {
                        statusDiv.textContent = `${window.allTreesData.length} data pohon siap dimuat`;

                        // Buka kembali popup yang sebelumnya terbuka
                        openPopups.forEach(popup => {
                            if (popup.id && layersById[popup.id]) {
                                layersById[popup.id].openPopup();
                            }
                        });

                        // Pastikan layer pohon berada di atas
                        ensureTreeLayerOnTop();

                        // Hapus status setelah 2 detik
                        setTimeout(() => {
                            if (document.body.contains(statusDiv)) {
                                document.body.removeChild(statusDiv);
                            }
                        }, 2000);
                    }, 200);
                }
            }

            // Update pohon saat peta bergerak (pan/zoom)
            if (!window.treeViewUpdateAdded) {
                map.on('moveend', updateVisibleTrees);
                window.treeViewUpdateAdded = true;
            }

            // Mulai proses dengan menampilkan pohon yang terlihat
            updateVisibleTrees();
        })
        .catch(error => {
            console.error('Error loading trees:', error);
            if (document.body.contains(statusDiv)) {
                statusDiv.textContent = 'Gagal memuat data';
                statusDiv.style.backgroundColor = '#ffdddd';
                setTimeout(() => document.body.removeChild(statusDiv), 3000);
            }
        });
}

// Tambahkan pemeriksaan berkala hanya untuk layer pohon
setInterval(function() {
    // Kode untuk otomatis menambahkan kembali plantation layer group dihapus
    // agar visibilitas layer dapat dikontrol manual melalui layer control

    // Pastikan layer pohon tetap di atas
    ensureTreeLayerOnTop();
}, 10000); // Cek setiap 10 detik

// Function untuk me-refresh semua plantation layers dari server
function refreshPlantationLayers() {
    console.log('Refreshing all plantation layers');

    // Bersihkan layer group jika ada
    if (window.plantationLayerGroup) {
        window.plantationLayerGroup.clearLayers();
        console.log('Cleared plantation layer group');
    }

    // Reset semua referensi plantation layers
    window.plantationLayers = {};
    window.plantationLayersMap = {};

    // Muat ulang semua plantation dari server
    fetch('/api/plantations')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.data) {
                console.log(`Loading ${data.data.length} plantations from server`);

                // Inisialisasi layer group jika belum ada
                if (!window.plantationLayerGroup) {
                    window.plantationLayerGroup = L.layerGroup().addTo(map);
                    console.log('Created new plantation layer group');
                }

                // Tambahkan semua plantation ke peta
                data.data.forEach(plantation => {
                    try {
                        if (plantation.geometry) {
                            const geojson = parseWKTtoGeoJSON(plantation.geometry);

                            // Buat layer dan tambahkan ke map
                            const layer = L.geoJSON(geojson, {
                                style: {
                                    fillColor: '#3388ff',
                                    weight: 2,
                                    opacity: 1,
                                    color: '#3388ff',
                                    fillOpacity: 0.2
                                }
                            });

                            // Simpan data plantation di layer
                            layer.plantationData = plantation;

                            // Tambahkan popup
                            layer.bindPopup(createPlantationPopupContent(plantation));

                            // Tambahkan ke layer group
                            layer.addTo(window.plantationLayerGroup);

                            // Simpan referensi ke layer
                            window.plantationLayers[plantation.id] = layer;

                            console.log(`Added plantation ${plantation.id} to map`);
                        }
                    } catch (error) {
                        console.error(`Error adding plantation ${plantation.id} to map:`, error);
                    }
                });

                // Update layer control jika ada
                if (window.layerControl) {
                    try {
                        window.layerControl.removeLayer(window.plantationLayerGroup);
                        window.layerControl.addOverlay(window.plantationLayerGroup, 'Blok Kebun');
                        console.log('Updated layer control with refreshed plantations');
                    } catch (error) {
                        console.warn('Error updating layer control:', error);
                    }
                }

                // Force redraw map
                map.invalidateSize({ animate: false });

                console.log('Plantation layers refreshed successfully');
            } else {
                console.error('Failed to get plantation data:', data.message);
            }
        })
        .catch(error => {
            console.error('Error refreshing plantations:', error);
        });
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof map !== 'undefined') {
        // Event untuk popup yang dibuka (lazy loading kontennya)
        map.on('popupopen', function(e) {
            const popup = e.popup;
            const container = popup.getElement();

            if (!container) return;

            const popupContent = container.querySelector('.popup-content');
            if (!popupContent) return;

            const treeId = popupContent.getAttribute('data-tree-id');
            if (!treeId) return;

            // Cari data pohon dari layer
            let treeData = null;

            // Cari di layer yang memiliki popup yang terbuka
            map.eachLayer(layer => {
                if (layer.treeData && layer.treeData.id == treeId) {
                    treeData = layer.treeData;
                }
            });

            // Jika tidak menemukan di layer, cari di allTreesData
            if (!treeData && window.allTreesData) {
                for (let i = 0; i < window.allTreesData.length; i++) {
                    if (window.allTreesData[i].id == treeId) {
                        treeData = window.allTreesData[i];
                        break;
                    }
                }
            }

            // Jika masih tidak ditemukan, keluar
            if (!treeData) return;

            // Dapatkan role pengguna dari data yang disimpan di window
            const userRole = window.userRole || '';

            // Hitung umur pohon
            const age = treeData.tahun_tanam ? (new Date().getFullYear() - treeData.tahun_tanam) : '-';

            // Reduksi styling inline dan DOM elements
            const btnClass = "bg-opacity-90 text-white p-1 rounded-full cursor-pointer w-7 h-7 inline-flex items-center justify-center border-0";

            // Buat konten penuh
            const fullContent = `
                <div style="margin-bottom:8px">
                    <strong>ID:</strong> ${treeData.id}<br>
                    <strong>Varietas:</strong> ${treeData.varietas || '-'}<br>
                    <strong>Tahun:</strong> ${treeData.tahun_tanam || '-'} (${age} thn)<br>
                    <strong>Status:</strong> ${treeData.health_status || '-'}<br>
                    <strong>Fase:</strong> ${treeData.fase || '-'}
                </div>
                <div style="text-align:center">
                    ${userRole !== 'Guest' ? `
                        <button onclick="editTree('${treeData.id}')" class="${btnClass}" style="background:#f97316;margin-right:3px"><i class="fas fa-edit" style="color:white"></i></button>
                        ${userRole !== 'Operasional' ? `
                            <button onclick="deleteTreeById('${treeData.id}')" class="${btnClass}" style="background:#ef4444"><i class="fas fa-trash" style="color:white"></i></button>
                        ` : ''}
                        <a href="${document.location.origin}/tree-dashboard?id=${treeData.id}" class="${btnClass}" style="background:#3b82f6;margin-left:3px"><i class="fas fa-chart-bar" style="color:white"></i></a>
                    ` : ''}
                </div>
            `;

            // Update konten popup jika memiliki kelas loading-mini
            if (popupContent.querySelector('.loading-mini')) {
                popupContent.innerHTML = fullContent;

                // Perbaiki posisi popup
                popup.update();
            }
        });
    }
});
