/**
 * Shapefile Layer - Component untuk menampilkan shapefile di Leaflet
 * @author Claude
 */

// Objek untuk menyimpan layer shapefile
const shapefileLayers = {
    plantation: new L.LayerGroup(),
    tree: new L.LayerGroup()
};

// Style untuk shapefile
const shapefileStyles = {
    plantation: {
        color: '#29AB87',
        weight: 3,
        opacity: 0.6,
        fillOpacity: 0.2,
        fillColor: '#29AB87'
    },
    tree: {
        color: '#3498db',
        weight: 2,
        opacity: 0.7,
        fillOpacity: 0.2,
        fillColor: '#3498db'
    }
};

/**
 * Inisialisasi layer untuk shapefile
 * @param {Object} map - Objek Leaflet map
 */
function initShapefileLayers(map) {
    if (!map) {
        console.error('Map tidak tersedia untuk inisialisasi layer shapefile');
        return;
    }

    // Tambahkan layer group ke map
    shapefileLayers.plantation.addTo(map);
    shapefileLayers.tree.addTo(map);

    // Load shapefile dari API
    loadShapefiles();

    console.log('Shapefile layers initialized');
}

/**
 * Load semua shapefile dari API
 */
function loadShapefiles() {
    console.log('Loading shapefiles from API...');
    
    fetch('/api/shapefiles')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear layer terlebih dahulu
                clearShapefileLayers();
                
                console.log(`Memuat ${data.data.length} shapefiles`);
                
                // Hitung per tipe
                const countByType = {
                    plantation: 0,
                    tree: 0
                };
                
                // Tampilkan shapefile
                data.data.forEach(shapefile => {
                    const success = showShapefile(shapefile);
                    if (success && shapefile.type) {
                        countByType[shapefile.type]++;
                    }
                });
                
                console.log(`Loaded ${data.data.length} shapefiles (${countByType.plantation} plantation, ${countByType.tree} tree)`);
            } else {
                console.error('Gagal memuat shapefile:', data.message);
            }
        })
        .catch(error => {
            console.error('Error saat memuat shapefile:', error);
        });
}

/**
 * Clear semua shapefile layer
 */
function clearShapefileLayers() {
    shapefileLayers.plantation.clearLayers();
    shapefileLayers.tree.clearLayers();
    console.log('Shapefile layers cleared');
}

/**
 * Menampilkan shapefile pada map
 * @param {Object} shapefile - Data shapefile dari API
 * @returns {Boolean} - Berhasil atau tidak menampilkan shapefile
 */
function showShapefile(shapefile) {
    if (!shapefile || !shapefile.geometry) {
        console.warn('Shapefile tidak valid atau tidak memiliki geometri');
        return false;
    }

    try {
        console.log(`Memproses shapefile: ${shapefile.id} - ${shapefile.name} (Tipe: ${shapefile.type})`);
        
        // Parse WKT ke GeoJSON menggunakan Wicket
        let geojsonData;
        
        // Gunakan geojson yang sudah ada dari API jika tersedia
        if (shapefile.geojson) {
            console.log(`Shapefile "${shapefile.name}" menggunakan geojson dari API`);
            geojsonData = shapefile.geojson;
        } else {
            // Jika tidak, parse dari WKT
            console.log(`Shapefile "${shapefile.name}" parsing WKT ke GeoJSON`);
            try {
                const wkt = new Wkt.Wkt();
                wkt.read(shapefile.geometry);
                geojsonData = wkt.toJson();
            } catch (e) {
                console.error(`Error parsing WKT: ${e.message}`);
                // Coba metode alternatif jika Wicket gagal
                try {
                    console.log('Mencoba metode parsing alternatif untuk WKT');
                    geojsonData = parseWKTManually(shapefile.geometry);
                } catch (e2) {
                    console.error(`Metode alternatif juga gagal: ${e2.message}`);
                    return false;
                }
            }
        }
        
        // Validasi geojsonData
        if (!geojsonData) {
            console.error(`Shapefile "${shapefile.name}" (ID: ${shapefile.id}) gagal: GeoJSON null`);
            return false;
        }
        
        // Debug info GeoJSON
        console.log(`GeoJSON type: ${geojsonData.type}`, geojsonData);
        
        // Perbaiki koordinat jika ada masalah format
        if (typeof geojsonData.coordinates !== 'undefined' && !geojsonData.type) {
            geojsonData = {
                type: guessGeoJSONType(geojsonData.coordinates),
                coordinates: geojsonData.coordinates
            };
        }
        
        // Buat layer GeoJSON
        const layer = L.geoJSON(geojsonData, {
            style: shapefileStyles[shapefile.type],
            pointToLayer: function(feature, latlng) {
                // Khusus untuk point, tampilkan sebagai circle marker
                if (feature.geometry.type === 'Point') {
                    return L.circleMarker(latlng, {
                        radius: 8,
                        fillColor: shapefileStyles[shapefile.type].color,
                        color: "#000",
                        weight: 1,
                        opacity: 1,
                        fillOpacity: 0.8
                    });
                }
                // Default marker
                return L.marker(latlng);
            },
            onEachFeature: (feature, layer) => {
                // Tambahkan popup
                layer.bindPopup(`
                    <div class="shapefile-popup">
                        <h4>${shapefile.name}</h4>
                        <p>Tipe: ${shapefile.type === 'plantation' ? 'Blok Kebun' : 'Pohon'}</p>
                        ${shapefile.description ? `<p>${shapefile.description}</p>` : ''}
                    </div>
                `);
                
                // Tambahkan event handler untuk click
                layer.on('click', function(e) {
                    // Perbarui URL tanpa refresh
                    const newUrl = window.location.pathname + `?shapefile=${shapefile.id}`;
                    window.history.pushState({ id: shapefile.id }, '', newUrl);
                });
            }
        });

        // Tambahkan ke layer group berdasarkan tipe
        if (shapefile.type === 'plantation') {
            shapefileLayers.plantation.addLayer(layer);
        } else if (shapefile.type === 'tree') {
            shapefileLayers.tree.addLayer(layer);
        }
        
        return true;
    } catch (error) {
        console.error(`Error menampilkan shapefile ${shapefile.name}:`, error);
        return false;
    }
}

/**
 * Parse WKT secara manual jika Wicket gagal
 * @param {string} wktString - WKT string
 * @returns {Object} - GeoJSON object
 */
function parseWKTManually(wktString) {
    console.log('Parsing WKT manually:', wktString.substring(0, 100) + '...');
    
    let type, coordinates;
    
    // POINT(x y)
    if (wktString.startsWith('POINT')) {
        const pointMatch = wktString.match(/POINT\s*\(\s*([^\s]+)\s+([^\s]+)\s*\)/i);
        if (pointMatch) {
            const lng = parseFloat(pointMatch[1]);
            const lat = parseFloat(pointMatch[2]);
            return {
                type: 'Point',
                coordinates: [lng, lat]
            };
        }
    }
    // POLYGON((x1 y1, x2 y2, ...))
    else if (wktString.startsWith('POLYGON')) {
        try {
            // Extract coordinates between (( and ))
            const coordsMatch = wktString.match(/POLYGON\s*\(\s*\((.*?)\)\s*\)/i);
            if (coordsMatch) {
                const coordsString = coordsMatch[1];
                const coordPairs = coordsString.split(',');
                
                // Convert to GeoJSON format
                const ring = coordPairs.map(pair => {
                    const [lng, lat] = pair.trim().split(/\s+/).map(parseFloat);
                    return [lng, lat];
                });
                
                return {
                    type: 'Polygon',
                    coordinates: [ring]
                };
            }
        } catch (e) {
            console.error('Error parsing polygon WKT:', e);
        }
    }
    // MULTIPOLYGON(((x1 y1, x2 y2, ...)), ((x3 y3, x4 y4, ...)))
    else if (wktString.startsWith('MULTIPOLYGON')) {
        try {
            // Extract all rings
            const matches = wktString.matchAll(/\(\s*\((.*?)\)\s*\)/g);
            const polygons = [];
            
            for (const match of matches) {
                const coordsString = match[1];
                const coordPairs = coordsString.split(',');
                
                // Convert to GeoJSON format
                const ring = coordPairs.map(pair => {
                    const [lng, lat] = pair.trim().split(/\s+/).map(parseFloat);
                    return [lng, lat];
                });
                
                polygons.push([ring]);
            }
            
            return {
                type: 'MultiPolygon',
                coordinates: polygons
            };
        } catch (e) {
            console.error('Error parsing multipolygon WKT:', e);
        }
    }
    
    throw new Error('Unsupported WKT format or parsing failed');
}

/**
 * Tebak tipe GeoJSON berdasarkan struktur koordinat
 * @param {Array} coordinates - Koordinat GeoJSON
 * @returns {string} - Tipe GeoJSON
 */
function guessGeoJSONType(coordinates) {
    if (!Array.isArray(coordinates)) {
        return 'Unknown';
    }
    
    // Check if coordinates is a point [x, y]
    if (coordinates.length === 2 && typeof coordinates[0] === 'number' && typeof coordinates[1] === 'number') {
        return 'Point';
    }
    
    // Check if coordinates is a polygon [[x1,y1], [x2,y2], ...]
    if (coordinates.length > 0 && Array.isArray(coordinates[0]) && 
        coordinates[0].length === 2 && typeof coordinates[0][0] === 'number') {
        return 'LineString';
    }
    
    // Check if coordinates is a polygon [[[x1,y1], [x2,y2], ...]]
    if (coordinates.length > 0 && Array.isArray(coordinates[0]) && 
        coordinates[0].length > 0 && Array.isArray(coordinates[0][0])) {
        return 'Polygon';
    }
    
    // Default to polygon
    return 'Polygon';
}

/**
 * Load shapefile berdasarkan ID
 * @param {number} id - ID shapefile
 */
function loadShapefileById(id) {
    console.log(`Loading shapefile ID ${id}...`);
    
    fetch(`/api/shapefiles/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const success = showShapefile(data.data);
                
                if (success) {
                    console.log(`Shapefile ID ${id} berhasil ditampilkan`);
                } else {
                    console.error(`Shapefile ID ${id} gagal ditampilkan`);
                }
                
                // Zoom ke shapefile jika ada geometri
                if (data.data.geometry && window.map) {
                    try {
                        // Gunakan geojson dari API jika tersedia
                        let geojsonData;
                        if (data.data.geojson) {
                            geojsonData = data.data.geojson;
                        } else {
                            const wkt = new Wkt.Wkt();
                            wkt.read(data.data.geometry);
                            geojsonData = wkt.toJson();
                        }
                        
                        const layer = L.geoJSON(geojsonData);
                        window.map.fitBounds(layer.getBounds(), {
                            padding: [50, 50],
                            maxZoom: 18
                        });
                        
                        // Tambahkan highlight sementara
                        const highlightLayer = L.geoJSON(geojsonData, {
                            style: {
                                color: '#FF4500',
                                weight: 5,
                                opacity: 0.9,
                                fillOpacity: 0.3,
                                fillColor: '#FF4500'
                            }
                        }).addTo(window.map);
                        
                        // Hapus highlight setelah 3 detik
                        setTimeout(() => {
                            window.map.removeLayer(highlightLayer);
                        }, 3000);
                        
                    } catch (error) {
                        console.error('Error saat zoom ke shapefile:', error);
                    }
                }
            } else {
                console.error('Gagal memuat shapefile:', data.message);
            }
        })
        .catch(error => {
            console.error('Error saat memuat shapefile:', error);
        });
}

// Export fungsi untuk digunakan di luar
window.initShapefileLayers = initShapefileLayers;
window.loadShapefiles = loadShapefiles;
window.loadShapefileById = loadShapefileById; 