// Fungsi untuk mendapatkan parameter dari URL
function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

// Fungsi untuk menunggu map diinisialisasi
function waitForMap(callback, maxAttempts = 10) {
    let attempts = 0;
    const interval = setInterval(() => {
        attempts++;
        if (window.map) {
            clearInterval(interval);
            callback();
        } else if (attempts >= maxAttempts) {
            clearInterval(interval);
            console.error('Map initialization timeout');
            alert('Gagal memuat peta. Silakan muat ulang halaman.');
        }
    }, 500);
}

// Fungsi untuk fokus ke pohon berdasarkan ID
function focusOnTree(treeId) {
    if (!treeId) return;

    console.log('Focusing on tree:', treeId); // Debug log

    waitForMap(() => {
        // Ambil data pohon dari API
        fetch(`/api/trees/${treeId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(tree => {
                console.log('Tree data received:', tree); // Debug log

                const latitude = parseFloat(tree.data.latitude);
                const longitude = parseFloat(tree.data.longitude);

                console.log('Using coordinates:', { latitude, longitude }); // Debug log

                // Pastikan koordinat valid
                if (!isNaN(latitude) && !isNaN(longitude) && (latitude !== 0 || longitude !== 0)) {
                    // Hapus marker welcome jika ada
                    if (window.welcomeMarker) {
                        window.map.removeLayer(window.welcomeMarker);
                    }

                    // Pindahkan view peta ke lokasi pohon
                    window.map.setView([latitude, longitude], 18);

                    // Tambahkan marker sementara
                    const marker = L.marker([latitude, longitude]).addTo(window.map);

                    // Tampilkan popup dengan informasi pohon
                    marker.bindPopup(`
                        <div class="text-center">
                            <h3 class="font-bold mb-2">Pohon ID: ${tree.data.id}</h3>
                            <p><b>Varietas:</b> ${tree.data.varietas}</p>
                            <p><b>Tahun Tanam:</b> ${tree.data.tahun_tanam}</p>
                            <p><b>Status:</b> ${tree.data.health_status}</p>
                            <p><b>Koordinat:</b></p>
                            <p>Latitude: ${latitude}</p>
                            <p>Longitude: ${longitude}</p>
                        </div>
                    `).openPopup();

                    // Hapus marker setelah popup ditutup
                    marker.on('popupclose', function() {
                        window.map.removeLayer(marker);
                        // Tampilkan kembali marker welcome
                        if (window.welcomeMarker) {
                            window.welcomeMarker.addTo(window.map);
                        }
                    });
                } else {
                    console.error('Invalid or zero coordinates:', { latitude, longitude });
                    alert('Koordinat pohon tidak valid atau belum diatur.');
                }
            })
            .catch(error => {
                console.error('Error fetching tree data:', error);
                alert('Gagal memuat data pohon. Silakan coba lagi.');
            });
    });
}

// Cek parameter id saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    const treeId = getUrlParameter('id');
    if (treeId) {
        console.log('Tree ID from URL:', treeId); // Debug log
        focusOnTree(treeId);
    }
});
