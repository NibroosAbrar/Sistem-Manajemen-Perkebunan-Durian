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

    // Jika method adalah PUT, kita perlu mengubah nama field dosis menjadi dosis_pestisida
    if (method === 'PUT') {
        formData.set('dosis_pestisida', formData.get('dosis'));
        formData.delete('dosis');
    }

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
            throw new Error('Network response was not ok');
        }
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
    .then(() => {
        window.location.href = `/tree-dashboard?id=${treeId}`;
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan: ' + error.message);
    });
};

// Fungsi untuk submit form riwayat kesehatan
window.submitHealthProfileForm = function(form) {
    const formData = new FormData(form);
    const treeId = document.querySelector('input[name="tree_id"]').value;

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken()
        },
        body: formData
    })
    .then(() => {
        window.location.href = `/tree-dashboard?id=${treeId}`;
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
        .then(() => {
            location.reload();
        })
        .catch(() => {
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
        .then(() => {
            location.reload();
        })
        .catch(() => {
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
        .then(() => {
            location.reload();
        })
        .catch(() => {
            location.reload();
        });
    }
};

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
                form.querySelector('input[name="tanggal_panen"]').value = harvest.tanggal_panen;
                form.querySelector('input[name="fruit_count"]').value = harvest.fruit_count;
                form.querySelector('input[name="total_weight"]').value = harvest.total_weight;
                form.querySelector('select[name="fruit_condition"]').value = harvest.fruit_condition;

                const average = harvest.total_weight / harvest.fruit_count;
                form.querySelector('input[name="average_weight_per_fruit"]').value = average.toFixed(2);

                form.action = `/trees/harvest/${id}`;

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
                    tanggalInput.value = healthProfile.tanggal_pemeriksaan;
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
            });
        }
    };

    // Fungsi untuk memuat data riwayat kesehatan
    window.loadHealthProfiles = function() {
        const treeId = document.querySelector('input[name="tree_id"]').value;
        const container = document.getElementById('health-profiles-container');
        
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
                    row.className = 'border-t hover:bg-gray-50';
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
                        case 'Terinfeksi':
                            statusClass = 'bg-red-100 text-red-800';
                            break;
                        case 'Mati':
                            statusClass = 'bg-gray-100 text-gray-800';
                            break;
                    }
                    
                    row.innerHTML = `
                        <td class="p-4 border border-gray-300 text-center">${formattedDate}</td>
                        <td class="p-4 border border-gray-300 text-center">
                            <span class="px-2 py-1 rounded-full ${statusClass}">${profile.status_kesehatan}</span>
                        </td>
                        <td class="p-4 border border-gray-300">${profile.gejala || '-'}</td>
                        <td class="p-4 border border-gray-300">${profile.diagnosis || '-'}</td>
                        <td class="p-4 border border-gray-300">${profile.tindakan_penanganan || '-'}</td>
                        <td class="p-4 border border-gray-300 text-center">
                            <button onclick="editHealthProfile(${profile.id})"
                                    class="bg-yellow-500 text-white px-3 py-1 rounded mr-1">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteHealthProfile(${profile.id})"
                                    class="bg-red-500 text-white px-3 py-1 rounded">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                    
                    container.appendChild(row);
                });
            } else {
                container.innerHTML = `
                    <tr>
                        <td colspan="6" class="p-4 border border-gray-300 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-info-circle mr-1"></i>Belum ada data riwayat kesehatan
                            </div>
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = `
                <tr>
                    <td colspan="6" class="p-4 border border-gray-300 text-center">
                        <div class="text-red-500">
                            <i class="fas fa-exclamation-circle mr-1"></i>Gagal memuat data
                        </div>
                    </td>
                </tr>
            `;
        });
    };
}); 