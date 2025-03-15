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
    zoom: 23,
    layers: [satellite, labels], // Gabungkan layer satelit dan label
    zoomControl: false
});

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

// Function untuk membuka modal foto dari mana saja
window.openPhotoModal = function() {
    // Dapatkan Alpine.js instance
    const alpine = document.querySelector('[x-data]').__x.$data;
    alpine.showPhotoModal = true;
};

// Tambahkan event listener untuk sidebar camera
sidebarcamera.on('shown', function() {
    document.dispatchEvent(new Event('camera:opened'));
});

// Function untuk menutup modal foto
window.closePhotoModal = function() {
    const alpine = document.querySelector('[x-data]').__x.$data;
    alpine.showPhotoModal = false;
};

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

// Initialize drawnItems FeatureGroup for the shapes
var drawnItems = new L.FeatureGroup();
map.addLayer(drawnItems);

// Variable to track deletion in progress
let isDeleting = false;

// Initialize Leaflet-Geoman controls
map.pm.addControls({
    position: 'topleft',
    drawCircle: true,
    drawCircleMarker: false,
    drawRectangle: false,
    drawPolyline: false,
    drawMarker: true,
    cutPolygon: false,
    editMode: true,
    dragMode: false,
    removalMode: true,
});

// Variabel untuk menyimpan status edit
let isEditMode = false;
let currentTab = 1;

// Event listener untuk pembuatan objek (polygon, circle, marker)
map.on('pm:create', function(e) {
    console.log('pm:create event triggered', e);

    // Tambahkan layer yang dibuat ke drawnItems
    drawnItems.addLayer(e.layer);

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

    // Dispatch event untuk membuka modal pemilihan jenis data
    window.dispatchEvent(new CustomEvent('open-shape-type-modal', {
        detail: {
            layer: e.layer,
            geometryWkt: wkt,
            shapeType: shape
        }
    }));
});

// Fungsi untuk menampilkan modal form pohon
function showTreeModal(isEdit = false, treeData = null, geometryWkt = null, shapeType = 'Polygon') {
    console.log('showTreeModal called with isEdit:', isEdit, 'treeData:', treeData, 'geometryWkt:', geometryWkt, 'shapeType:', shapeType);

    // Reset form jika bukan mode edit
    if (!isEdit) {
        document.getElementById('treeForm').reset();
    }

    // Set action dan method form berdasarkan mode (edit atau tambah)
    const form = document.getElementById('treeForm');
    if (!form) {
        console.error('Form not found!');
        return;
    }

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

        // Set tree_id untuk referensi
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

        // Set geometri jika ada
        if (geometryWkt) {
            const geometryInput = document.getElementById('canopy_geometry');
            if (geometryInput) {
                geometryInput.value = geometryWkt;
                console.log('Geometry set to:', geometryWkt);
            }
        }
    }

    // Set shape_type
    if (shapeType) {
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
    }

    // Dispatch event untuk Alpine.js
    window.dispatchEvent(new CustomEvent('open-tree-modal', {
        detail: {
            isEdit: isEdit,
            treeData: treeData,
            geometryWkt: geometryWkt,
            shapeType: shapeType
        }
    }));
    console.log('Dispatched open-tree-modal event');

    // Aktifkan tab pertama
    setTimeout(() => {
        switchTab(1);
        console.log('Activated first tab');
    }, 100);
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
    console.log('closeTreeModal called');
    const modal = document.getElementById('treeModal');
    if (modal) {
        modal.classList.add('hidden');
        console.log('Modal hidden (hidden class added)');
    } else {
        console.error('treeModal element not found!');
    }
}

// Fungsi untuk beralih antar tab
function switchTab(tabNumber) {
    console.log('switchTab called with tabNumber:', tabNumber);

    // Variabel untuk melacak tab yang aktif
    window.currentTab = tabNumber;

    // Sembunyikan semua tab content
    const tabContents = document.querySelectorAll('.tab-content');
    if (tabContents.length > 0) {
        tabContents.forEach(tab => {
            tab.classList.add('hidden');
        });
        console.log('All tab contents hidden');
    } else {
        console.error('No tab-content elements found!');
    }

    // Tampilkan tab yang dipilih
    const selectedTab = document.getElementById(`tab-${tabNumber}`);
    if (selectedTab) {
        selectedTab.classList.remove('hidden');
        console.log(`Tab ${tabNumber} displayed`);
    } else {
        console.error(`tab-${tabNumber} element not found!`);
    }

    // Update style tombol tab
    const tabButtons = document.querySelectorAll('.tab-button');
    if (tabButtons.length > 0) {
        tabButtons.forEach(button => {
            button.classList.remove('text-green-600', 'font-semibold', 'border-b-2', 'border-green-600');
            button.classList.add('text-gray-600');
        });
        console.log('All tab buttons reset to inactive style');
    } else {
        console.error('No tab-button elements found!');
    }

    const activeTabButton = document.querySelector(`.tab-${tabNumber}`);
    if (activeTabButton) {
        activeTabButton.classList.remove('text-gray-600');
        activeTabButton.classList.add('text-green-600', 'font-semibold', 'border-b-2', 'border-green-600');
        console.log(`Tab button ${tabNumber} set to active style`);
    } else {
        console.error(`.tab-${tabNumber} button not found!`);
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

    // Tentukan mode (edit atau tambah)
    const formMode = document.getElementById('form_mode')?.value || 'create';
    const isEdit = formMode === 'update';
    const treeId = document.getElementById('tree_id')?.value || '';

    console.log('Form mode:', isEdit ? 'edit' : 'create', 'Tree ID:', treeId);

    // Validasi field yang required
    const requiredFields = ['plantation_id', 'varietas', 'tahun_tanam'];
    let missingFields = [];

    requiredFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (!field || !field.value.trim()) {
            missingFields.push(fieldId);
        }
    });

    if (missingFields.length > 0) {
        console.error('Missing required fields:', missingFields);
        alert('Mohon isi semua field yang wajib diisi: ' + missingFields.join(', '));
        return false;
    }

    // Validasi input geometri
    const geometryInput = document.getElementById('canopy_geometry');
    if (!geometryInput || !geometryInput.value.trim()) {
        console.error('Canopy geometry is empty!');

        // Jika dalam mode edit, coba ambil geometri dari server
        if (isEdit && treeId) {
            console.log('Trying to fetch geometry from server for tree ID:', treeId);

            // Tampilkan loading
            showLoading('Mengambil data geometri...');

            // Tambahkan timestamp untuk mencegah cache
            const timestamp = new Date().getTime();

            // Ambil data pohon dari server
            fetch(`/api/trees/${treeId}?_=${timestamp}`)
                .then(response => response.json())
                .then(data => {
                    // Sembunyikan loading
                    hideLoading();

                    if (data.success && data.data && (data.data.canopy_geometry || data.data.canopy_geometry_wkt)) {
                        console.log('Retrieved geometry from server:', data.data.canopy_geometry || data.data.canopy_geometry_wkt);

                        // Set nilai geometri jika geometryInput ada
                        if (geometryInput) {
                            geometryInput.value = data.data.canopy_geometry || data.data.canopy_geometry_wkt;

                            // Coba kirim form lagi
                            submitTreeForm();
                        } else {
                            console.error('canopy_geometry input not found after fetching geometry');
                            alert('Terjadi kesalahan: Input geometri tidak ditemukan.');
                        }
                    } else {
                        console.error('Failed to retrieve geometry from server');
                        alert('Geometri pohon tidak ditemukan. Silakan gambar ulang area pohon.');
                    }
                })
                .catch(error => {
                    // Sembunyikan loading
                    hideLoading();

                    console.error('Error fetching tree data:', error);
                    alert('Terjadi kesalahan saat mengambil data pohon. Silakan coba lagi.');
                });

            return false;
        }

        alert('Geometri pohon tidak ditemukan. Silakan gambar area pohon terlebih dahulu.');
        return false;
    }

    // Validasi format WKT
    const wkt = geometryInput.value.trim().toUpperCase();
    const shapeType = document.getElementById('shape_type')?.value || 'Polygon';

    console.log('Validating geometry:', wkt, 'Shape type:', shapeType);

    // Validasi berdasarkan tipe bentuk
    let isValid = false;

    // Validasi POINT
    if (shapeType === 'Marker' && wkt.match(/^POINT\s*\(.+\)$/i)) {
        isValid = true;
    }
    // Validasi POLYGON (termasuk lingkaran yang dikonversi ke polygon)
    else if ((shapeType === 'Polygon' || shapeType === 'Circle') && wkt.match(/^POLYGON\s*\(\s*\(.+\)\s*\)$/i)) {
        isValid = true;
    }

    if (!isValid) {
        console.error('Invalid WKT format:', wkt);
        alert('Format geometri tidak valid. Silakan gambar ulang area pohon.');
        return false;
    }

    // Buat objek data untuk dikirim
    const formData = new FormData();

    // Tambahkan semua field form ke formData
    const formElements = form.elements;
    for (let i = 0; i < formElements.length; i++) {
        const element = formElements[i];
        if (element.name && element.name !== '_method') { // Kecualikan _method karena akan ditangani secara terpisah
            formData.append(element.name, element.value);
            console.log(`Added form field: ${element.name} = ${element.value}`);
        }
    }

    // Pastikan field yang required ada di formData dengan nilai yang valid
    const plantationId = document.getElementById('plantation_id')?.value || getDefaultPlantationId();
    formData.set('plantation_id', plantationId);
    console.log('Set plantation_id to:', plantationId);

    // Pastikan varietas ada
    const varietas = document.getElementById('varietas')?.value || 'Tidak Diketahui';
    formData.set('varietas', varietas);
    console.log('Set varietas to:', varietas);

    // Pastikan tahun_tanam ada
    const tahunTanam = document.getElementById('tahun_tanam')?.value || getCurrentYear();
    formData.set('tahun_tanam', tahunTanam);
    console.log('Set tahun_tanam to:', tahunTanam);

    // Pastikan health_status ada
    const healthStatus = document.getElementById('health_status')?.value || 'Sehat';
    formData.set('health_status', healthStatus);
    console.log('Set health_status to:', healthStatus);

    // Pastikan canopy_geometry ada
    formData.set('canopy_geometry', wkt);
    console.log('Set canopy_geometry to:', wkt);

    // Pastikan shape_type ada
    formData.set('shape_type', shapeType);
    console.log('Set shape_type to:', shapeType);

    // Pastikan CSRF token disertakan
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        formData.set('_token', csrfToken);
        console.log('Set _token to:', csrfToken);
    } else {
        console.warn('CSRF token not found in meta tag');
    }

    // Tentukan URL dan method berdasarkan mode (edit atau tambah)
    let url = isEdit ? `/api/trees/${treeId}` : '/api/trees';
    let method = isEdit ? 'PUT' : 'POST';

    // Jika method adalah PUT, tambahkan _method=PUT ke formData
    if (method === 'PUT') {
        formData.append('_method', 'PUT');
        console.log('Added _method=PUT for Laravel method spoofing');
        // Untuk Laravel, kita tetap menggunakan POST tetapi dengan _method=PUT
        method = 'POST';
    }

    console.log('Submitting form to:', url, 'with method:', method);

    // Tampilkan loading
    showLoading('Menyimpan data pohon...');

    // Untuk debugging, log semua data yang akan dikirim
    console.log('Form data yang akan dikirim:');
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }

    // Kirim form dengan fetch API
    fetch(url, {
        method: method,
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken || '',
            // Jangan set Content-Type karena FormData akan mengaturnya secara otomatis dengan boundary
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }).catch(err => {
                // Jika tidak bisa parse JSON, gunakan pesan error default
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

        console.log('Form submission response:', data);

        if (data.success) {
            // Tutup modal
            window.dispatchEvent(new CustomEvent('close-tree-modal'));

            // Tampilkan pesan sukses dengan detail perubahan
            let message = isEdit ? 'Data pohon berhasil diperbarui!' : 'Data pohon berhasil disimpan!';

            // Tambahkan detail perubahan jika ada
            if (data.changes && Object.keys(data.changes).length > 0) {
                message += '\n\nPerubahan yang disimpan:';
                for (const [key, value] of Object.entries(data.changes)) {
                    message += `\n- ${key}: ${value.old} → ${value.new}`;
                }
            }

            alert(message);

            // Jika ini adalah edit, hapus layer lama terlebih dahulu
            if (isEdit && treeId) {
                console.log('Removing old tree layer with ID:', treeId);
                let layerToRemove = null;

                drawnItems.eachLayer(function(layer) {
                    if ((layer.treeData && layer.treeData.id == treeId) ||
                        (layer.feature && layer.feature.properties && layer.feature.properties.id == treeId)) {
                        layerToRemove = layer;
                    }
                });

                if (layerToRemove) {
                    drawnItems.removeLayer(layerToRemove);
                    console.log('Old tree layer removed');
                }
            }

            // Reload trees tanpa refresh halaman - dengan delay untuk memastikan server sudah memproses perubahan
            setTimeout(() => {
                console.log('Reloading trees after edit/create');
                loadExistingTrees();
            }, 500);
        } else {
            console.error('Error:', data.message);
            alert('Terjadi kesalahan: ' + data.message);
        }
    })
    .catch(error => {
        // Sembunyikan loading
        hideLoading();

        console.error('Error submitting form:', error);
        alert('Terjadi kesalahan saat mengirim data: ' + error.message);
    });

    return false; // Prevent default form submission
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
                if ((layer.treeData && layer.treeData.id == treeId) ||
                    (layer.feature && layer.feature.properties && layer.feature.properties.id == treeId)) {
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
    if (!isDeleting) {
        deleteTree(e.layer);
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

// Layer Control
var baseLayers = {
    "Esri Map": L.layerGroup([satellite, labels]),
    'Google Hybrid': googleHybrid,
    "Google Streets": GoogleStreets,
};

var overlays = {
    "Pohon": drawnItems  // Tambahkan layer polygon ke control
};

var control_layers = L.control.layers(baseLayers, overlays).addTo(map);

// Function to load existing trees from the server
function loadExistingTrees() {
    console.log('Loading existing trees...');

    // Tampilkan loading jika fungsi tersedia
    if (typeof showLoading === 'function') {
        showLoading('Memuat data pohon...');
    }

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

    // Fetch trees data from the server dengan parameter nocache untuk memastikan data terbaru
    fetch(`/trees/get-all?_=${timestamp}&nocache=true`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Sembunyikan loading jika fungsi tersedia
            if (typeof hideLoading === 'function') {
                hideLoading();
            }

            console.log('Loaded trees data:', data);

            // Periksa format respons
            let trees = data;
            if (data && typeof data === 'object' && data.hasOwnProperty('success')) {
                // Format respons: { success: true, data: [...] }
                if (!data.success) {
                    console.error('Error loading trees:', data.message);
                    return;
                }
                trees = data.data;
            }

            if (!trees || !Array.isArray(trees) || trees.length === 0) {
                console.log('No trees found');
                return;
            }

            console.log('Processing', trees.length, 'trees');

            // Simpan layer berdasarkan ID untuk membuka popup nanti
            let layersById = {};

            // Add each tree to the map
            trees.forEach(tree => {
                try {
                    if (!tree.canopy_geometry) {
                        console.warn('Tree has no geometry:', tree);
                        return;
                    }

                    console.log('Processing tree:', tree.id, 'with geometry:', tree.canopy_geometry);

                    // Parse WKT to Leaflet coordinates
                    const parsedGeometry = parseWKT(tree.canopy_geometry);

                    if (!parsedGeometry) {
                        console.error('Failed to parse geometry for tree:', tree.id);
                        return;
                    }

                    let layer;

                    // Buat layer berdasarkan tipe geometri
                    if (parsedGeometry.type === 'point') {
                        // Buat marker untuk POINT
                        const [lat, lng] = parsedGeometry.coordinates;
                        layer = L.marker([lat, lng]);
                    }
                    else if (parsedGeometry.type === 'polygon') {
                        // Buat polygon untuk POLYGON
                        layer = L.polygon(parsedGeometry.coordinates);
                    }
                    else {
                        console.error('Unsupported geometry type:', parsedGeometry.type);
                        return;
                    }

                    // Set tree data as layer properties
                    layer.treeId = tree.id;
                    layer.treeData = tree;

                    // Add popup with tree info
                    const popupContent = createPopupContent(tree);
                    layer.bindPopup(popupContent);

                    // Set layer style based on tree health
                    if (parsedGeometry.type === 'polygon') {
                        const healthColor = getHealthColor(tree.health_status);
                        layer.setStyle({
                            fillColor: healthColor,
                            fillOpacity: 0.5,
                            color: healthColor,
                            weight: 2
                        });
                    }

                    // Add layer to drawnItems
                    drawnItems.addLayer(layer);

                    // Simpan layer berdasarkan ID
                    layersById[tree.id] = layer;

                    console.log('Added tree to map:', tree.id);
                } catch (error) {
                    console.error('Error adding tree to map:', error, tree);
                }
            });

            // Buka kembali popup yang sebelumnya terbuka
            openPopups.forEach(popup => {
                if (popup.id && layersById[popup.id]) {
                    layersById[popup.id].openPopup();
                }
            });
        })
        .catch(error => {
            // Sembunyikan loading jika fungsi tersedia
            if (typeof hideLoading === 'function') {
                hideLoading();
            }

            console.error('Error loading trees:', error);
            alert('Terjadi kesalahan saat memuat data pohon: ' + error.message);
        });
}

// Function to parse WKT string to Leaflet coordinates
function parseWKT(wkt) {
    try {
        if (!wkt) {
            console.error('WKT is null or empty');
            return null;
        }

        console.log('Parsing WKT:', wkt);

        // Cek apakah ini POINT
        if (wkt.toUpperCase().startsWith('POINT')) {
            // Format: POINT(lng lat)
            const pointMatch = wkt.match(/POINT\s*\(\s*([0-9.-]+)\s+([0-9.-]+)\s*\)/i);
            if (!pointMatch || pointMatch.length < 3) {
                console.error('Invalid POINT format:', wkt);
                return null;
            }

            const lng = parseFloat(pointMatch[1]);
            const lat = parseFloat(pointMatch[2]);

            if (isNaN(lng) || isNaN(lat)) {
                console.error('Invalid POINT coordinates:', wkt);
                return null;
            }

            console.log('Parsed POINT coordinates:', [lng, lat]);
            return { type: 'point', coordinates: [lat, lng] };
        }
        // Cek apakah ini POLYGON
        else if (wkt.toUpperCase().startsWith('POLYGON')) {
    // Remove POLYGON(( and )) from WKT string
            const coordsStr = wkt.replace(/POLYGON\s*\(\s*\(|\)\s*\)/gi, '');

            if (!coordsStr.trim()) {
                console.error('WKT string is empty after removing POLYGON(())');
                return null;
            }

    // Split into coordinate pairs
    const coords = coordsStr.split(',').map(pair => {
                const parts = pair.trim().split(' ');
                if (parts.length < 2) {
                    console.error('Invalid coordinate pair:', pair);
                    return null;
                }
                const [lng, lat] = parts.map(Number);
                if (isNaN(lng) || isNaN(lat)) {
                    console.error('Invalid coordinate values:', pair);
                    return null;
                }
                return [lat, lng]; // Leaflet uses [lat, lng] format
            }).filter(coord => coord !== null);

            if (coords.length < 3) {
                console.error('Not enough valid coordinates for a polygon');
                return null;
            }

            console.log('Parsed POLYGON coordinates:', coords);
            return { type: 'polygon', coordinates: coords };
        }
        else {
            console.error('Unsupported WKT type:', wkt);
            return null;
        }
    } catch (error) {
        console.error('Error parsing WKT:', error);
        return null;
    }
}

// Function to get color based on health status
function getHealthColor(status) {
    switch (status) {
        case 'Sehat':
            return '#4CAF50'; // Green
        case 'Stres':
            return '#FFC107'; // Yellow
        case 'Terinfeksi':
            return '#FF5722'; // Orange
        case 'Mati':
            return '#F44336'; // Red
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
    const options = {
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'Asia/Jakarta',
        timeZoneName: 'short',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    return date.toLocaleDateString('id-ID', options);
}

// Function to update image info panel
function updateImageInfo(data) {
    document.getElementById('noPhotoInfo').classList.add('hidden');
    document.getElementById('photoInfo').classList.remove('hidden');

    // Update info values
    document.getElementById('imageResolution').textContent = data.resolution + ' cm';
    document.getElementById('imageDateTime').textContent = formatDate(data.capture_time);
    document.getElementById('droneType').textContent = data.drone_type;
    document.getElementById('flightHeight').textContent = data.height + ' meter';
    document.getElementById('imageOverlap').textContent = data.overlap + '%';
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
    if (document.body.dataset.flashMessage === 'success') {
        loadLatestAerialPhoto();
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
    const age = tree.tahun_tanam ? new Date().getFullYear() - tree.tahun_tanam : 'Belum diketahui';
    return `
        <div class="popup-content">
            <div class="mb-2">
                <strong>ID:</strong> ${tree.id}<br>
                <strong>Varietas:</strong> ${tree.varietas || '-'}<br>
                <strong>Tahun Tanam:</strong> ${tree.tahun_tanam || '-'}<br>
                <strong>Umur:</strong> ${age} tahun<br>
                <strong>Status Kesehatan:</strong> ${tree.health_status || '-'}<br>
            </div>
            <div class="text-center mt-2">
                <button onclick="editTree(${tree.id})"
                        style="background-color: #f97316; color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border: none; margin-right: 5px;">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteTreeById(${tree.id})"
                        style="background-color: #ef4444; color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border: none;">
                    <i class="fas fa-trash"></i>
                </button>
                <a href="/tree-dashboard?id=${tree.id}"
                   style="background-color: #3b82f6; color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border: none; margin-left: 5px; text-decoration: none;">
                    <i class="fas fa-chart-bar"></i>
                </a>
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
const urlParams = new URLSearchParams(window.location.search);
const treeId = urlParams.get('id');

//untuk ngontrol lokasi dan view dari id
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

// Function to submit plantation form
function submitPlantationForm() {
    const form = document.getElementById('plantationForm');
    const formData = new FormData(form);
    const plantationId = formData.get('id');
    const isEdit = !!plantationId;

    // Convert FormData to JSON
    const data = {};
    formData.forEach((value, key) => {
        if (key === 'luas_area' && value) {
            data[key] = parseFloat(value);
        } else {
            data[key] = value;
        }
    });

    // Get CSRF token
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Show loading
    showLoading(isEdit ? 'Menyimpan perubahan...' : 'Menyimpan blok kebun...');

    // Determine URL and method based on whether this is an edit or create
    const url = isEdit ? `/api/plantations/${plantationId}` : '/api/plantations';
    const method = isEdit ? 'PUT' : 'POST';

    // Send request
    fetch(url, {
        method: method,
        headers: {
            'X-CSRF-TOKEN': token,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            // Close modal
            document.querySelector('[x-data]').__x.$data.showPlantationModal = false;

            // Show success message
            alert(data.message);

            // Reload plantations
            loadExistingPlantations();
        } else {
            throw new Error(data.message || 'Gagal menyimpan blok kebun');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error submitting form:', error);
        alert(error.message || 'Gagal menyimpan blok kebun');
    });
}

// Function to load existing plantations
function loadExistingPlantations() {
    console.log('Loading existing plantations...');

    // Tampilkan loading
    showLoading('Memuat data blok kebun...');

    // Tambahkan timestamp untuk mencegah cache
    const timestamp = new Date().getTime();

    // Fetch plantations data from the server
    fetch(`/api/plantations?_=${timestamp}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Sembunyikan loading
            hideLoading();

            if (!data.success) {
                throw new Error(data.message || 'Gagal memuat data blok kebun');
            }

            const plantations = data.data;
            console.log('Loaded plantations:', plantations);

            // Clear existing plantation layers
            plantationLayers.clearLayers();

            // Add each plantation to the map
            plantations.forEach(plantation => {
                try {
                    if (!plantation.geometry) {
                        console.warn('Plantation has no geometry:', plantation);
                        return;
                    }

                    // Parse WKT to Leaflet coordinates
                    const parsedGeometry = parseWKT(plantation.geometry);
                    if (!parsedGeometry) {
                        console.error('Failed to parse geometry for plantation:', plantation.id);
                        return;
                    }

                    let layer;
                    if (parsedGeometry.type === 'polygon') {
                        layer = L.polygon(parsedGeometry.coordinates, {
                            color: '#3388ff',
                            weight: 2,
                            opacity: 1,
                            fillOpacity: 0.2
                        });
                    } else {
                        console.error('Unsupported geometry type:', parsedGeometry.type);
                        return;
                    }

                    // Set plantation data as layer properties
                    layer.plantationId = plantation.id;
                    layer.plantationData = plantation;

                    // Add popup with plantation info
                    const popupContent = createPlantationPopupContent(plantation);
                    layer.bindPopup(popupContent);

                    // Add layer to plantationLayers
                    plantationLayers.addLayer(layer);

                } catch (error) {
                    console.error('Error adding plantation to map:', error, plantation);
                }
            });
        })
        .catch(error => {
            hideLoading();
            console.error('Error loading plantations:', error);
            alert('Terjadi kesalahan saat memuat data blok kebun: ' + error.message);
        });
}

// Function to create popup content for plantation
function createPlantationPopupContent(plantation) {
    return `
        <div class="popup-content">
            <div class="mb-2">
                <strong>ID:</strong> ${plantation.id}<br>
                <strong>Nama Blok:</strong> ${plantation.name || '-'}<br>
                <strong>Luas Area:</strong> ${plantation.luas_area ? plantation.luas_area + ' m²' : '-'}<br>
                <strong>Tipe Tanah:</strong> ${plantation.tipe_tanah || '-'}<br>
                <strong>Koordinat:</strong> (${plantation.latitude}, ${plantation.longitude})<br>
            </div>
            <div class="text-center mt-2">
                <button onclick="editPlantation(${plantation.id})"
                        style="background-color: #f97316; color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border: none; margin-right: 5px;">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deletePlantationById(${plantation.id})"
                        style="background-color: #ef4444; color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border: none;">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
}

// Function to handle plantation deletion
function deletePlantationById(plantationId) {
    if (confirm('Apakah Anda yakin ingin menghapus blok kebun ini?')) {
        // Tampilkan loading
        showLoading('Menghapus blok kebun...');

        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(`/api/plantations/${plantationId}`, {
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
                alert(data.message);
                loadExistingPlantations();
            } else {
                throw new Error(data.message || 'Gagal menghapus blok kebun');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error deleting plantation:', error);
            alert(error.message || 'Gagal menghapus blok kebun');
        });
    }
}

// Function to edit plantation
function editPlantation(plantationId) {
    console.log('Editing plantation:', plantationId);

    // Tampilkan loading
    showLoading('Memuat data blok kebun...');

    // Tambahkan timestamp untuk mencegah cache
    const timestamp = new Date().getTime();

    fetch(`/api/plantations/${plantationId}?_=${timestamp}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            hideLoading();
            if (!data.success) {
                throw new Error(data.message || 'Gagal memuat data blok kebun');
            }

            const plantation = data.data;
            console.log('Fetched plantation data:', plantation);

            // Dispatch event untuk membuka modal edit
            window.dispatchEvent(new CustomEvent('open-plantation-modal', {
                detail: {
                    isEdit: true,
                    plantationData: plantation
                }
            }));
        })
        .catch(error => {
            hideLoading();
            console.error('Error fetching plantation data:', error);
            alert('Terjadi kesalahan saat mengambil data blok kebun: ' + error.message);
        });
}

// Initialize plantationLayers FeatureGroup for the plantation shapes
var plantationLayers = new L.FeatureGroup();
map.addLayer(plantationLayers);

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


