// Fungsi untuk mendapatkan CSRF token
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

// Fungsi untuk format tanggal
function formatDate(date) {
    const d = new Date(date);
    const month = (d.getMonth() + 1).toString().padStart(2, '0');
    const day = d.getDate().toString().padStart(2, '0');
    return `${d.getFullYear()}-${month}-${day}`;
}

// Fungsi untuk menghitung rata-rata berat per buah
function calculateAverageWeight() {
    const fruitCount = parseFloat(document.getElementById('fruit_count').value) || 0;
    const totalWeight = parseFloat(document.getElementById('total_weight').value) || 0;
    const averageWeightInput = document.getElementById('average_weight_per_fruit');

    if (fruitCount > 0 && totalWeight > 0) {
        const average = totalWeight / fruitCount;
        averageWeightInput.value = average.toFixed(2);
    } else {
        averageWeightInput.value = '';
    }
}

// Fungsi untuk submit form pestisida
window.submitPesticideForm = function(form) {
    const formData = new FormData(form);
    const treeId = document.querySelector('input[name="tree_id"]').value;
    const method = form.querySelector('input[name="_method"]')?.value || 'POST';

    // Tidak perlu mengubah nama field lagi karena controller sudah menangani nama field yang sama
    // Jika method adalah PUT, biarkan semua field dengan nama aslinya

    fetch(form.action, {
        method: 'POST', // Tetap gunakan POST karena Laravel menghandle method override
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Error response:', text);
                throw new Error('Network response was not ok');
            });
        }
        // Langsung refresh halaman tanpa menampilkan alert
        window.location.href = `/tree-dashboard?id=${treeId}`;
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan: ' + error.message);
    });
};

// Fungsi untuk submit form panen
window.submitHarvestForm = function(form) {
    const formData = new FormData(form);
    const treeId = document.querySelector('input[name="tree_id"]').value;

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken()
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        // Langsung refresh halaman tanpa menampilkan alert
        window.location.href = `/tree-dashboard?id=${treeId}`;
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan: ' + error.message);
    });
};

// Fungsi untuk submit form kesehatan
window.submitHealthProfileForm = function() {
    const form = document.getElementById('healthProfileForm');
    const formData = new FormData(form);
    const treeId = document.querySelector('input[name="tree_id"]').value;
    const healthId = document.getElementById('health_id').value;

    let url = '/api/trees/health-profiles';
    let method = 'POST';

    if (healthId) {
        url = `/api/trees/health-profiles/${healthId}`;
        method = 'PUT';
    }

    fetch(url, {
        method: method,
        headers: {
            'X-CSRF-TOKEN': getCsrfToken()
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // Refresh halaman setelah berhasil
        window.location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan: ' + error.message);
    });
};

// Fungsi untuk menampilkan loading
window.showLoading = function(message = 'Loading...') {
    const loadingScreen = document.getElementById('loading-screen');
    const loadingMessage = document.getElementById('loadingMessage');
    if (loadingMessage) {
        loadingMessage.textContent = message;
    }
    if (loadingScreen) {
        loadingScreen.style.display = 'flex';
    }
};

// Fungsi untuk menyembunyikan loading
window.hideLoading = function() {
    const loadingScreen = document.getElementById('loading-screen');
    if (loadingScreen) {
        loadingScreen.style.display = 'none';
    }
};

// Fungsi untuk mereset form pemupukan
window.resetFertilizationForm = function() {
    const form = document.getElementById('fertilizationForm');
    if (!form) return;

    form.reset();
    form.action = '/trees/fertilization';

    const methodInput = form.querySelector('input[name="_method"]');
    if (methodInput) methodInput.remove();

    const modalTitle = form.querySelector('h3');
    if (modalTitle) modalTitle.textContent = 'Tambah Data Pemupukan';
};

// Fungsi untuk mereset form pestisida
window.resetPesticideForm = function() {
    const form = document.getElementById('pesticideForm');
    if (!form) return;

    form.reset();
    form.action = '/trees/pesticide';

    const methodInput = form.querySelector('input[name="_method"]');
    if (methodInput) methodInput.remove();

    const modalTitle = form.querySelector('h3');
    if (modalTitle) modalTitle.textContent = 'Tambah Data Pestisida';
};

// Fungsi untuk mereset form panen
window.resetHarvestForm = function() {
    const form = document.getElementById('harvestForm');
    if (!form) return;

    form.reset();
    form.action = '/trees/harvest';

    const methodInput = form.querySelector('input[name="_method"]');
    if (methodInput) methodInput.remove();

    const modalTitle = form.querySelector('h3');
    if (modalTitle) modalTitle.textContent = 'Tambah Data Panen';

    const averageWeightInput = form.querySelector('input[name="average_weight_per_fruit"]');
    if (averageWeightInput) averageWeightInput.value = '';
};

// Fungsi untuk mereset form riwayat kesehatan
window.resetHealthProfileForm = function() {
    const form = document.getElementById('healthProfileForm');
    if (!form) return;

    form.reset();
    form.action = '/trees/health-profiles';

    const methodInput = form.querySelector('input[name="_method"]');
    if (methodInput) methodInput.remove();

    const modalTitle = document.getElementById('healthProfileModalTitle');
    if (modalTitle) modalTitle.textContent = 'Tambah Riwayat Kesehatan';
};

// Fungsi untuk menghapus data pemupukan
window.deleteFertilization = function(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data pemupukan ini?')) {
        fetch(`/tree-dashboard/fertilization/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Langsung refresh halaman tanpa menampilkan alert
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan: ' + error.message);
            location.reload();
        });
    }
};

// Fungsi untuk menghapus data pestisida
window.deletePesticide = function(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data pestisida ini?')) {
        fetch(`/tree-dashboard/pesticide/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Langsung refresh halaman tanpa menampilkan alert
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan: ' + error.message);
            location.reload();
        });
    }
};

// Fungsi untuk menghapus data panen
window.deleteHarvest = function(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data panen ini?')) {
        fetch(`/tree-dashboard/harvest/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Langsung refresh halaman tanpa menampilkan alert
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan: ' + error.message);
            location.reload();
        });
    }
};

// Fungsi untuk menampilkan foto kesehatan dalam modal
window.showHealthPhoto = function(photoUrl) {
    const modal = document.getElementById('photoModal');
    const modalImg = document.getElementById('modalPhotoImg');

    if (modal && modalImg) {
        modalImg.src = photoUrl;
        modal.classList.remove('hidden');
    } else {
        console.error('Modal atau gambar modal tidak ditemukan');
    }
};

// Alias untuk showHealthPhoto
window.showPhotoModal = function(photoUrl) {
    window.showHealthPhoto(photoUrl);
};

// Fungsi untuk menutup modal foto
window.closePhotoModal = function() {
    const modal = document.getElementById('photoModal');
    if (modal) {
        modal.classList.add('hidden');
    }
};

// Fungsi untuk mendapatkan kategori kondisi buah
function getFruitConditionCategory(percentage) {
    if (percentage >= 0 && percentage <= 20) {
        return 'Sangat Tidak Baik';
    } else if (percentage > 20 && percentage <= 40) {
        return 'Tidak Baik';
    } else if (percentage > 40 && percentage <= 60) {
        return 'Cukup';
    } else if (percentage > 60 && percentage <= 80) {
        return 'Baik';
    } else {
        return 'Sangat Baik';
    }
}

// Fungsi untuk menampilkan keterangan kategori kondisi buah
function updateFruitConditionHelp(value) {
    const helpText = document.getElementById('fruit-condition-help');
    if (helpText) {
        const category = getFruitConditionCategory(parseFloat(value));
        helpText.innerHTML = `
            <div class="text-sm text-gray-600 mt-1">
                <p>Kategori: <strong>${category}</strong></p>
                <p class="mt-1">Keterangan Kategori:</p>
                <ul class="list-disc pl-5 mt-1">
                    <li>0-20% : Sangat Tidak Baik</li>
                    <li>21-40% : Tidak Baik</li>
                    <li>41-60% : Cukup</li>
                    <li>61-80% : Baik</li>
                    <li>81-100% : Sangat Baik</li>
                </ul>
            </div>
        `;
    }
}

// Fungsi untuk memvalidasi input kondisi buah
function validateFruitCondition(input) {
    let value = parseFloat(input.value);
    if (value > 100) {
        value = 100;
    } else if (value < 0) {
        value = 0;
    }
    input.value = value;
    updateFruitConditionHelp(value);
}

// Event listeners saat dokumen dimuat
document.addEventListener('DOMContentLoaded', function() {
    // Event listener untuk input jumlah buah
    const fruitCountInput = document.getElementById('fruit_count');
    if (fruitCountInput) {
        fruitCountInput.addEventListener('input', calculateAverageWeight);
    }

    // Event listener untuk input total berat
    const totalWeightInput = document.getElementById('total_weight');
    if (totalWeightInput) {
        totalWeightInput.addEventListener('input', calculateAverageWeight);
    }

    // Event listener untuk input kondisi buah
    const fruitConditionInput = document.querySelector('input[name="fruit_condition"]');
    if (fruitConditionInput) {
        // Tambahkan atribut min, max, dan step
        fruitConditionInput.setAttribute('min', '0');
        fruitConditionInput.setAttribute('max', '100');
        fruitConditionInput.setAttribute('step', '0.01');

        fruitConditionInput.addEventListener('input', function() {
            validateFruitCondition(this);
        });

        // Validasi saat blur (user selesai input)
        fruitConditionInput.addEventListener('blur', function() {
            if (this.value === '' || isNaN(this.value)) {
                this.value = '0';
            }
            validateFruitCondition(this);
        });
    }

    // Fungsi untuk menampilkan loading
    window.showLoading = function(message = 'Loading...') {
        const loadingScreen = document.getElementById('loading-screen');
        const loadingMessage = document.getElementById('loadingMessage');
        if (loadingMessage) {
            loadingMessage.textContent = message;
        }
        if (loadingScreen) {
            loadingScreen.style.display = 'flex';
        }
    };

    // Fungsi untuk menyembunyikan loading
    window.hideLoading = function() {
        const loadingScreen = document.getElementById('loading-screen');
        if (loadingScreen) {
            loadingScreen.style.display = 'none';
        }
    };

    // Fungsi untuk mereset form pemupukan
    window.resetFertilizationForm = function() {
        const form = document.getElementById('fertilizationForm');
        if (!form) return;

        form.reset();
        form.action = '/trees/fertilization';

        const methodInput = form.querySelector('input[name="_method"]');
        if (methodInput) methodInput.remove();

        const modalTitle = document.getElementById('fertilizationModalTitle');
        if (modalTitle) modalTitle.textContent = 'Tambah Data Pemupukan';

        const treeIdInput = form.querySelector('input[name="tree_id"]');
        if (treeIdInput) treeIdInput.value = document.querySelector('input[name="tree_id"]').value;
    };

    // Fungsi untuk edit data pemupukan
    window.editFertilization = function(id) {
        const form = document.getElementById('fertilizationForm');
        window.dispatchEvent(new Event('open-fertilization-modal'));

        fetch(`/trees/fertilization/${id}/edit`, {
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const fertilization = data.data;
                form.querySelector('input[name="tanggal_pemupukan"]').value = fertilization.tanggal_pemupukan;
                form.querySelector('input[name="nama_pupuk"]').value = fertilization.nama_pupuk;
                form.querySelector('select[name="jenis_pupuk"]').value = fertilization.jenis_pupuk;
                form.querySelector('input[name="bentuk_pupuk"]').value = fertilization.bentuk_pupuk;
                form.querySelector('input[name="dosis_pupuk"]').value = fertilization.dosis_pupuk;
                form.querySelector('select[name="unit"]').value = fertilization.unit;

                form.action = `/trees/fertilization/${id}`;

                const methodInput = form.querySelector('input[name="_method"]') || document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'PUT';
                if (!form.querySelector('input[name="_method"]')) {
                    form.appendChild(methodInput);
                }
            }
        })
        .catch(error => console.error('Error:', error));
    };

    // Fungsi untuk edit data pestisida
    window.editPesticide = function(id) {
        const form = document.getElementById('pesticideForm');
        window.dispatchEvent(new Event('open-pesticide-modal'));

        fetch(`/trees/pesticide/${id}/edit`, {
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const pesticide = data.data;
                form.querySelector('input[name="tanggal_pestisida"]').value = pesticide.tanggal_pestisida;
                form.querySelector('input[name="nama_pestisida"]').value = pesticide.nama_pestisida;
                form.querySelector('select[name="jenis_pestisida"]').value = pesticide.jenis_pestisida;

                // Mengisi bentuk pestisida jika ada
                const bentukInput = form.querySelector('input[name="bentuk_pestisida"]');
                if (bentukInput) {
                    bentukInput.value = pesticide.bentuk_pestisida || '';
                }

                form.querySelector('input[name="dosis"]').value = pesticide.dosis;
                form.querySelector('select[name="unit"]').value = pesticide.unit;

                form.action = `/trees/pesticide/${id}`;

                const existingMethod = form.querySelector('input[name="_method"]');
                if (existingMethod) {
                    existingMethod.remove();
                }

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'PUT';
                form.appendChild(methodInput);

                const modalTitle = form.querySelector('h3');
                if (modalTitle) {
                    modalTitle.textContent = 'Edit Data Pestisida';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengambil data: ' + error.message);
        });
    };

    // Fungsi untuk edit data panen
    window.editHarvest = function(id) {
        const form = document.getElementById('harvestForm');
        if (!form) {
            console.error('Form harvest tidak ditemukan');
            return;
        }
        window.dispatchEvent(new Event('open-harvest-modal'));

        fetch(`/trees/harvest/${id}/edit`, {
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const harvest = data.data;

                // Tambahkan pengecekan untuk setiap elemen sebelum mengatur nilainya
                const tanggalPanenInput = form.querySelector('input[name="tanggal_panen"]');
                if (tanggalPanenInput) tanggalPanenInput.value = harvest.tanggal_panen;

                const fruitCountInput = form.querySelector('input[name="fruit_count"]');
                if (fruitCountInput) fruitCountInput.value = harvest.fruit_count;

                const totalWeightInput = form.querySelector('input[name="total_weight"]');
                if (totalWeightInput) totalWeightInput.value = harvest.total_weight;

                const fruitConditionInput = form.querySelector('input[name="fruit_condition"]');
                if (fruitConditionInput) {
                    fruitConditionInput.value = harvest.fruit_condition;
                    // Update keterangan kategori saat mengedit
                    updateFruitConditionHelp(harvest.fruit_condition);
                }

                const unitSelect = form.querySelector('select[name="unit"]');
                if (unitSelect) unitSelect.value = harvest.unit;

                // Hitung dan tampilkan rata-rata berat per buah jika kedua input ada
                if (harvest.fruit_count && harvest.total_weight) {
                    const average = harvest.total_weight / harvest.fruit_count;
                    const averageInput = form.querySelector('input[name="average_weight_per_fruit"]');
                    if (averageInput) averageInput.value = average.toFixed(2);
                }

                form.action = `/trees/harvest/${id}`;

                // Tambahkan method input jika belum ada
                let methodInput = form.querySelector('input[name="_method"]');
                if (!methodInput) {
                    methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'PUT';
                    form.appendChild(methodInput);
                } else {
                    methodInput.value = 'PUT';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengambil data panen');
        });
    };

    // Fungsi untuk edit data riwayat kesehatan
    window.editHealthProfile = function(id) {
        const form = document.getElementById('healthProfileForm');
        window.dispatchEvent(new Event('open-health-modal'));

        fetch(`/api/trees/health-profiles/${id}`, {
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                if (response.status === 404) {
                    throw new Error('Data tidak ditemukan');
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.data) {
                const healthProfile = data.data;

                const tanggalInput = form.querySelector('input[name="tanggal_pemeriksaan"]');
                if (tanggalInput && healthProfile.tanggal_pemeriksaan) {
                    // Konversi tanggal dari format ISO ke format yyyy-MM-dd
                    const date = new Date(healthProfile.tanggal_pemeriksaan);
                    const formattedDate = date.toISOString().split('T')[0]; // Mengambil bagian yyyy-MM-dd saja
                    tanggalInput.value = formattedDate;
                    console.log('Formatted tanggal_pemeriksaan to:', formattedDate);
                } else {
                    tanggalInput.value = formatDate(new Date());
                }

                const statusSelect = form.querySelector('select[name="status_kesehatan"]');
                if (statusSelect && healthProfile.status_kesehatan) {
                    statusSelect.value = healthProfile.status_kesehatan;
                }

                const gejalaTextarea = form.querySelector('textarea[name="gejala"]');
                if (gejalaTextarea) {
                    gejalaTextarea.value = healthProfile.gejala || '';
                }

                const diagnosisTextarea = form.querySelector('textarea[name="diagnosis"]');
                if (diagnosisTextarea) {
                    diagnosisTextarea.value = healthProfile.diagnosis || '';
                }

                const tindakanTextarea = form.querySelector('textarea[name="tindakan_penanganan"]');
                if (tindakanTextarea) {
                    tindakanTextarea.value = healthProfile.tindakan_penanganan || '';
                }

                const catatanTextarea = form.querySelector('textarea[name="catatan_tambahan"]');
                if (catatanTextarea) {
                    catatanTextarea.value = healthProfile.catatan_tambahan || '';
                }

                if (healthProfile.foto_kondisi) {
                    const currentPhoto = document.getElementById('current-photo');
                    const currentPhotoImg = document.getElementById('current-photo-img');

                    if (currentPhoto && currentPhotoImg) {
                        currentPhotoImg.src = `/storage/${healthProfile.foto_kondisi}`;
                        currentPhoto.classList.remove('hidden');
                    }
                } else {
                    const currentPhoto = document.getElementById('current-photo');
                    if (currentPhoto) {
                        currentPhoto.classList.add('hidden');
                    }
                }

                form.action = `/api/trees/health-profiles/${id}`;

                const existingMethod = form.querySelector('input[name="_method"]');
                if (existingMethod) {
                    existingMethod.remove();
                }

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'PUT';
                form.appendChild(methodInput);

                const modalTitle = document.getElementById('healthProfileModalTitle');
                if (modalTitle) {
                    modalTitle.textContent = 'Edit Riwayat Kesehatan';
                }
            } else {
                throw new Error(data.message || 'Gagal mengambil data riwayat kesehatan');
            }
        })
        .catch(error => {
            console.error('Error:', error);

            const modal = document.querySelector('[x-data="{ showHealthModal: false }"]');
            if (modal && typeof modal.__x !== 'undefined') {
                modal.__x.setProperty('showHealthModal', false);
            } else {
                window.dispatchEvent(new CustomEvent('close-health-modal'));
            }

            alert('Terjadi kesalahan saat mengambil data: ' + error.message);

            loadHealthProfiles();
        });
    };

    // Fungsi untuk hapus data riwayat kesehatan
    window.deleteHealthProfile = function(id) {
        if (confirm('Apakah Anda yakin ingin menghapus riwayat kesehatan ini?')) {
            fetch(`/api/trees/health-profiles/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (response.status === 404) {
                    window.location.reload();
                    return { success: false, message: 'Data tidak ditemukan' };
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Langsung refresh halaman tanpa menampilkan alert
                    window.location.reload();
                } else {
                    if (data.message && data.message.includes('No query results')) {
                        window.location.reload();
                    } else {
                        alert('Gagal menghapus data: ' + (data.message || 'Terjadi kesalahan'));
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghapus data. Silakan coba lagi.');
                window.location.reload();
            });
        }
    };

    // Fungsi untuk memuat data riwayat kesehatan
    window.loadHealthProfiles = function() {
        const treeId = document.querySelector('input[name="tree_id"]').value;
        const container = document.getElementById('health-profiles-container');
        const isGuest = document.body.getAttribute('data-user-role') === '4'; // Cek role dari atribut data di body

        if (!container) return;

        fetch(`/api/trees/${treeId}/health-profiles`, {
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                const sortedData = [...data.data].sort((a, b) => {
                    const dateA = new Date(a.tanggal_pemeriksaan);
                    const dateB = new Date(b.tanggal_pemeriksaan);
                    return dateB - dateA;
                });

                container.innerHTML = '';

                sortedData.forEach(profile => {
                    const row = document.createElement('tr');
                    row.setAttribute('data-health-profile-id', profile.id);

                    const date = new Date(profile.tanggal_pemeriksaan);
                    const formattedDate = `${date.getDate().toString().padStart(2, '0')}/${(date.getMonth() + 1).toString().padStart(2, '0')}/${date.getFullYear()}`;

                    let statusClass = '';
                    switch(profile.status_kesehatan) {
                        case 'Sehat':
                            statusClass = 'bg-green-100 text-green-800';
                            break;
                        case 'Stres':
                            statusClass = 'bg-yellow-100 text-yellow-800';
                            break;
                        case 'Sakit':
                            statusClass = 'bg-red-100 text-red-800';
                            break;
                        case 'Mati':
                            statusClass = 'bg-gray-700 text-white';
                            break;
                        default:
                            statusClass = 'bg-gray-100 text-gray-800';
                    }

                    // HTML untuk baris tabel dengan atau tanpa kolom aksi
                    let rowHTML = `
                        <td class="text-center">${formattedDate}</td>
                        <td class="text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium ${statusClass}">
                                ${profile.status_kesehatan}
                            </span>
                        </td>
                        <td class="text-center">${profile.gejala || '-'}</td>
                        <td class="text-center">${profile.diagnosis || '-'}</td>
                        <td class="text-center">${profile.tindakan_penanganan || '-'}</td>
                        <td class="text-center">
                            ${profile.foto_kondisi ?
                                `<button onclick="showPhotoModal('/storage/${profile.foto_kondisi}')" class="text-blue-500 hover:underline">Lihat Foto</button>` :
                                '-'}
                        </td>`;

                    // Tambahkan kolom aksi hanya jika bukan guest
                    if (!isGuest) {
                        rowHTML += `
                        <td class="text-center">
                            <div class="flex justify-center space-x-1">
                                <button onclick="editHealthProfile(${profile.id})"
                                        class="p-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition-colors">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteHealthProfile(${profile.id})"
                                        class="p-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>`;
                    }

                    row.innerHTML = rowHTML;
                    container.appendChild(row);
                });
            } else {
                const colSpan = isGuest ? 6 : 7; // Jumlah kolom berbeda tergantung apakah ada kolom aksi
                container.innerHTML = `
                    <tr>
                        <td colspan="${colSpan}" class="text-center py-8">
                            <div class="flex flex-col items-center justify-center text-gray-500">
                                <i class="fas fa-info-circle text-2xl mb-2"></i>
                                <p>Belum ada data riwayat kesehatan</p>
                            </div>
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const colSpan = isGuest ? 6 : 7; // Jumlah kolom berbeda tergantung apakah ada kolom aksi
            container.innerHTML = `
                <tr>
                    <td colspan="${colSpan}" class="text-center py-8">
                        <div class="flex flex-col items-center justify-center text-red-500">
                            <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                            <p>Gagal memuat data</p>
                        </div>
                    </td>
                </tr>
            `;
        });
    };

    // Fungsi untuk memuat data ZPT
    window.loadZptRecords = function() {
        const treeId = document.querySelector('input[name="tree_id"]').value;
        const zptRecordsTable = document.getElementById('zptRecordsTable');
        const isGuest = document.body.getAttribute('data-user-role') === '4'; // Cek role dari atribut data di body

        if (!zptRecordsTable) return;

        console.log('Loading ZPT records for tree ID:', treeId);

        // Gunakan URL dengan parameter query string untuk tree_id
        fetch(`/api/trees/${treeId}/zpts?tree_id=${treeId}`, {
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('ZPT load response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('ZPT data loaded:', data);
            if (data.success && data.data) {
                const zptRecords = data.data;

                if (zptRecords.length === 0) {
                    const colSpan = isGuest ? 8 : 9; // Jumlah kolom berbeda tergantung apakah ada kolom aksi
                    zptRecordsTable.innerHTML = `
                        <tr>
                            <td colspan="${colSpan}" class="text-center py-8">
                                <div class="flex flex-col items-center justify-center text-gray-500">
                                    <i class="fas fa-info-circle text-2xl mb-2"></i>
                                    <p>Belum ada data ZPT</p>
                                </div>
                            </td>
                        </tr>
                    `;
                    return;
                }

                // Urutkan berdasarkan tanggal aplikasi (terbaru dulu)
                zptRecords.sort((a, b) => new Date(b.tanggal_aplikasi) - new Date(a.tanggal_aplikasi));

                let tableContent = '';
                zptRecords.forEach(zpt => {
                    const tanggal = new Date(zpt.tanggal_aplikasi).toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    });

                    let rowContent = `
                        <tr>
                            <td class="text-center">${tanggal}</td>
                            <td class="text-center">${zpt.nama_zpt || '-'}</td>
                            <td class="text-center">${zpt.merek || '-'}</td>
                            <td class="text-center">${zpt.jenis_senyawa || '-'}</td>
                            <td class="text-center">${zpt.konsentrasi || '-'}</td>
                            <td class="text-center">${zpt.volume_larutan || '0'} ${zpt.unit || ''}</td>
                            <td class="text-center">${zpt.fase_pertumbuhan || '-'}</td>
                            <td class="text-center">${zpt.metode_aplikasi || '-'}</td>`;

                    // Tambahkan kolom aksi hanya jika bukan guest
                    if (!isGuest) {
                        rowContent += `
                            <td class="text-center">
                                <div class="flex justify-center space-x-1">
                                    <button onclick="editZptRecord(${zpt.id})"
                                            class="p-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition-colors">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteZptRecord(${zpt.id})"
                                            class="p-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>`;
                    }

                    rowContent += `</tr>`;
                    tableContent += rowContent;
                });

                zptRecordsTable.innerHTML = tableContent;
            } else {
                const colSpan = isGuest ? 8 : 9; // Jumlah kolom berbeda tergantung apakah ada kolom aksi
                zptRecordsTable.innerHTML = `
                    <tr>
                        <td colspan="${colSpan}" class="text-center py-8">
                            <div class="flex flex-col items-center justify-center text-gray-500">
                                <i class="fas fa-info-circle text-2xl mb-2"></i>
                                <p>Belum ada data ZPT</p>
                            </div>
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const colSpan = isGuest ? 8 : 9; // Jumlah kolom berbeda tergantung apakah ada kolom aksi
            zptRecordsTable.innerHTML = `
                <tr>
                    <td colspan="${colSpan}" class="text-center py-8">
                        <div class="flex flex-col items-center justify-center text-red-500">
                            <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                            <p>Gagal memuat data: ${error.message}</p>
                        </div>
                    </td>
                </tr>
            `;
        });
    };

    // Fungsi untuk edit data ZPT
    window.editZpt = function(zptId) {
        console.log('Editing ZPT with ID:', zptId);

        const form = document.getElementById('zptForm');
        if (!form) {
            console.error('ZPT form not found in the DOM');
            alert('Terjadi kesalahan: Form ZPT tidak ditemukan');
            return;
        }

        // Reset form terlebih dahulu
        resetZptForm();

        // Buka modal
        window.dispatchEvent(new Event('open-zpt-modal'));

        // Tampilkan loading
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Loading...';
        }

        fetch(`/api/trees/zpts/${zptId}`, {
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('Edit ZPT response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Edit ZPT response data:', data);

            if (data.success && data.data) {
                const zpt = data.data;
                console.log('ZPT data to edit:', zpt);

                // Isi form dengan data yang ada
                document.getElementById('zpt_id').value = zpt.id;

                // Isi tanggal aplikasi jika ada, dengan format yang benar (yyyy-MM-dd)
                const tanggalInput = document.getElementById('tanggal_aplikasi');
                if (tanggalInput && zpt.tanggal_aplikasi) {
                    // Konversi tanggal dari format ISO ke format yyyy-MM-dd
                    const date = new Date(zpt.tanggal_aplikasi);
                    const formattedDate = date.toISOString().split('T')[0]; // Mengambil bagian yyyy-MM-dd saja
                    tanggalInput.value = formattedDate;
                    console.log('Formatted tanggal_aplikasi to:', formattedDate);
                }

                // Isi field lainnya jika ada
                setFormValueIfExists('nama_zpt', zpt.nama_zpt);
                setFormValueIfExists('merek', zpt.merek);
                setFormValueIfExists('jenis_senyawa', zpt.jenis_senyawa);
                setFormValueIfExists('konsentrasi', zpt.konsentrasi);
                setFormValueIfExists('volume_larutan', zpt.volume_larutan);
                setFormValueIfExists('unit', zpt.unit || 'ml');
                setFormValueIfExists('fase_pertumbuhan', zpt.fase_pertumbuhan);
                setFormValueIfExists('metode_aplikasi', zpt.metode_aplikasi);

                // Ubah judul modal
                const modalTitle = document.getElementById('zptModalTitle');
                if (modalTitle) {
                    modalTitle.textContent = 'Edit Data ZPT';
                }

                // Pastikan tree_id tersedia dalam form
                const treeIdInput = form.querySelector('input[name="tree_id"]');
                if (!treeIdInput) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'tree_id';
                    input.value = zpt.tree_id;
                    form.appendChild(input);
                    console.log('Added tree_id input with value:', zpt.tree_id);
                } else {
                    treeIdInput.value = zpt.tree_id;
                    console.log('Updated tree_id input with value:', zpt.tree_id);
                }
            } else {
                throw new Error(data.message || 'Data ZPT tidak ditemukan');
            }
        })
        .catch(error => {
            console.error('Error in editZpt:', error);
            alert('Terjadi kesalahan saat mengambil data: ' + error.message);
        })
        .finally(() => {
            // Kembalikan tombol submit ke kondisi normal
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = 'Simpan';
            }
        });
    };

    // Fungsi helper untuk mengisi nilai form jika ada
    function setFormValueIfExists(id, value) {
        const element = document.getElementById(id);
        if (element && value !== undefined && value !== null) {
            element.value = value;
            console.log(`Set ${id} to:`, value);
        } else if (!element) {
            console.warn(`Element with id ${id} not found`);
        } else {
            console.warn(`Value for ${id} is undefined or null`);
        }
    }

    // Fungsi untuk menghapus data ZPT
    window.deleteZpt = function(zptId) {
        if (confirm('Apakah Anda yakin ingin menghapus data ZPT ini?')) {
            fetch(`/api/trees/zpts/${zptId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'Terjadi kesalahan');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Langsung refresh halaman tanpa menampilkan alert
                    window.location.reload();
                } else {
                    alert('Gagal menghapus data: ' + (data.message || 'Terjadi kesalahan'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghapus data. Silakan coba lagi.');
            });
        }
    };

    // Fungsi untuk submit form ZPT
    window.submitZptForm = function() {
        const form = document.getElementById('zptForm');
        const formData = new FormData(form);
        const treeId = document.querySelector('input[name="tree_id"]').value;
        const zptId = document.getElementById('zpt_id').value;

        let url = '/api/trees/zpts';
        let method = 'POST';

        if (zptId) {
            url = `/api/trees/zpts/${zptId}`;
            method = 'PUT';
        }

        fetch(url, {
            method: method,
            headers: {
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Refresh halaman setelah berhasil
            window.location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan: ' + error.message);
        });
    };

    // Fungsi untuk mereset form ZPT
    window.resetZptForm = function() {
        console.log('Resetting ZPT form');

        const form = document.getElementById('zptForm');
        if (!form) {
            console.error('ZPT form not found');
            return;
        }

        // Reset form
        form.reset();

        // Reset ID ZPT
        const zptIdInput = document.getElementById('zpt_id');
        if (zptIdInput) {
            zptIdInput.value = '';
        }

        // Reset judul modal
        const modalTitle = document.getElementById('zptModalTitle');
        if (modalTitle) {
            modalTitle.textContent = 'Tambah Data ZPT';
        }

        // Pastikan tree_id tetap ada dan terisi
        const treeId = document.querySelector('input[name="tree_id"]').value;
        const treeIdInput = form.querySelector('input[name="tree_id"]');
        if (!treeIdInput) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'tree_id';
            input.value = treeId;
            form.appendChild(input);
        } else {
            treeIdInput.value = treeId;
        }

        // Set tanggal aplikasi ke hari ini jika kosong
        const tanggalInput = document.getElementById('tanggal_aplikasi');
        if (tanggalInput && !tanggalInput.value) {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            tanggalInput.value = `${year}-${month}-${day}`;
        }

        // Set nilai default untuk unit jika ada
        const unitSelect = document.getElementById('unit');
        if (unitSelect && !unitSelect.value) {
            unitSelect.value = 'ml';
        }

        console.log('ZPT form reset complete');
    };

    // Alias untuk fungsi editZpt
    window.editZptRecord = function(id) {
        window.editZpt(id);
    };

    // Alias untuk fungsi deleteZpt
    window.deleteZptRecord = function(id) {
        window.deleteZpt(id);
    };

    // Alias untuk resetHealthProfileForm
    window.resetHealthForm = function() {
        window.resetHealthProfileForm();
    };
});
