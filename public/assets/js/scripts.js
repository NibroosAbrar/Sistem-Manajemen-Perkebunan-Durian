function hideLoadingScreen() {
    document.getElementById('loading-screen').style.display = 'none';
}

var map;
var baseLayers;
var overlays;

// Initialize the Leaflet map
var satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {});

// Tambahkan layer Label (Nama Tempat)
var labels = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {});

// Gabungkan kedua layer
var map = L.map('map', {
    attributionControl: false,
    center: [-6.559434, 106.725815],
    zoom: 23,
    layers: [satellite, labels], // Gabungkan layer satelit dan label
    zoomControl: false
});

var GoogleStreets = L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
    maxZoom: 23,
    subdomains:['mt0','mt1','mt2','mt3']
});

var googleHybrid = L.tileLayer('http://mt0.google.com/vt/lyrs=y&hl=en&x={x}&y={y}&z={z}', {
    maxZoom: 23
});

// Add a marker
var marker = L.marker([-6.559434, 106.725815]).addTo(map); // Koordinat untuk Bogor
marker.bindPopup('<b>Welcome to DuriGeo</b>').openPopup();

// Atur posisi peta agar fokus pada marker
map.setView([-6.559434, 106.725815], 23); // Zoom level 13

// Hapus marker dengan efek fade-out setelah 5 detik
setTimeout(function() {
    // Dapatkan elemen DOM marker
    var markerElement = marker.getElement();

    // Tambahkan kelas CSS untuk efek fade-out
    markerElement.classList.add('leaflet-marker-fade');

    // Tunggu 1 detik (sesuai durasi animasi fade-out), lalu hapus marker dari peta
    setTimeout(function() {
        map.removeLayer(marker);
    }, 1000); // 1000 ms = 1 detik
}, 5000);

// Tambahkan scale bar
L.control.scale({
    position: 'bottomleft', // Posisi scale bar
    metric: true, // Gunakan metrik (meter/kilometer)
    imperial: true // Nonaktifkan imperial (feet/mile)
}).addTo(map);

// Variabel untuk menyimpan status sidebar yang aktif
let activeSidebar = null;

// SIDEBAR
var sidebarcamera = L.control.sidebar('camera', {
    position: 'left',
    autopan: false // Nonaktifkan pemindahan peta saat sidebar terbuka
}).addTo(map);

var sidebarinfo = L.control.sidebar('info', {
    position: 'left',
    autopan: false // Nonaktifkan pemindahan peta saat sidebar terbuka
}).addTo(map);

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

// EasyButton untuk Info
L.easyButton('fa-info-circle', function () {
    if (sidebarcamera.isVisible()) {
        sidebarcamera.hide(); // Tutup sidebar camera jika sedang terbuka
    }
    sidebarinfo.toggle(); // Tampilkan atau sembunyikan sidebar info
}).addTo(map);

// EasyButton untuk Camera
L.easyButton('fa-camera', function () {
    if (sidebarinfo.isVisible()) {
        sidebarinfo.hide(); // Tutup sidebar info jika sedang terbuka
    }
    sidebarcamera.toggle(); // Tampilkan atau sembunyikan sidebar camera
}).addTo(map);

// Mouse Position
L.control.mousePosition({position:'bottomright'}).addTo(map);

// Geoman
map.pm.addControls({
    position: 'topleft',
    drawCircleMarker: false,
    rotateMode: false,
    dragMode: false,
});

map.on("pm:create", function(e) {
    console.log(e);
});

// Layer Control
var baseLayers = {
    "Esri Map": L.layerGroup([satellite, labels]),
    'Google Hybrid': googleHybrid,
    "Google Streets": GoogleStreets,
};

var control_layers = L.control.layers(baseLayers, overlays).addTo(map);

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


