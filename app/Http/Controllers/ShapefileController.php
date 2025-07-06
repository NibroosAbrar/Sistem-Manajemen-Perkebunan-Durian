<?php

namespace App\Http\Controllers;

use App\Models\Shapefile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\AerialPhoto;
use App\Models\Plantation;
use PhpZip\ZipFile;

class ShapefileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shapefiles = Shapefile::orderBy('created_at', 'desc')->get();

        // Ambil data aerial photos
        $aerialPhotos = \App\Models\AerialPhoto::orderBy('created_at', 'desc')->get();

        // Ambil data tree detections jika model sudah ada
        $treeDetections = collect(); // Default empty collection
        if (class_exists('\App\Models\TreeDetection')) {
            try {
                $treeDetections = \App\Models\TreeDetection::with('plantation')
                    ->orderBy('created_at', 'desc')
                    ->get();
            } catch (\Exception $e) {
                // Tangani jika tabel belum ada
                \Illuminate\Support\Facades\Log::error('Error loading tree detections: ' . $e->getMessage());
            }
        }

        return view('pages.shapefile.shapefile', compact('shapefiles', 'aerialPhotos', 'treeDetections'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.shapefile.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:plantation,tree',
            'file' => 'required|file|mimes:zip,kml,xml',
            'description' => 'nullable|string',
        ]);

        // Simpan file
        $filePath = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Jika ekstensi file adalah KML tetapi mime type-nya XML, tetap perlakukan sebagai KML
            $originalExtension = $file->getClientOriginalExtension();
            if (strtolower($originalExtension) === 'kml' && $file->getMimeType() === 'text/xml') {
                $fileName = time() . '_' . Str::slug($request->name) . '.kml';
            } else {
                $fileName = time() . '_' . Str::slug($request->name) . '.' . $file->getClientOriginalExtension();
            }

            $filePath = $file->storeAs('shapefiles', $fileName, 'public');
        }

        // Cari ID terkecil yang tersedia
        $availableId = $this->findSmallestAvailableId();

        try {
            // Buat shapefile baru menggunakan metode statis create
            $shapefile = Shapefile::create([
                'id' => $availableId, // Set ID kustom
            'name' => $request->name,
            'type' => $request->type,
            'file_path' => $filePath,
            'description' => $request->description,
        ]);

        return redirect()->route('shapefile.index')
            ->with('success', 'Shapefile berhasil ditambahkan!');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error saat menyimpan shapefile: ' . $e->getMessage());

            return redirect()->route('shapefile.index')
                ->with('error', 'Gagal menyimpan shapefile: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Shapefile $shapefile)
    {
        return view('pages.shapefile.show', compact('shapefile'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Shapefile $shapefile)
    {
        return view('pages.shapefile.edit', compact('shapefile'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Shapefile $shapefile)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:plantation,tree',
            'file' => 'nullable|file|mimes:zip,kml,xml',
            'description' => 'nullable|string',
        ]);

        // Update file jika ada
        if ($request->hasFile('file')) {
            // Hapus file lama jika ada
            if ($shapefile->file_path && Storage::disk('public')->exists($shapefile->file_path)) {
                Storage::disk('public')->delete($shapefile->file_path);
            }

            // Simpan file baru
            $file = $request->file('file');

            // Jika ekstensi file adalah KML tetapi mime type-nya XML, tetap perlakukan sebagai KML
            $originalExtension = $file->getClientOriginalExtension();
            if (strtolower($originalExtension) === 'kml' && $file->getMimeType() === 'text/xml') {
                $fileName = time() . '_' . Str::slug($request->name) . '.kml';
            } else {
                $fileName = time() . '_' . Str::slug($request->name) . '.' . $file->getClientOriginalExtension();
            }

            $filePath = $file->storeAs('shapefiles', $fileName, 'public');

            $shapefile->file_path = $filePath;
        }

        // Update data
        $shapefile->name = $request->name;
        $shapefile->type = $request->type;
        $shapefile->description = $request->description;
        $shapefile->save();

        return redirect()->route('shapefile.index')
            ->with('success', 'Shapefile berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Shapefile $shapefile)
    {
        // Hapus file
        if ($shapefile->file_path && Storage::disk('public')->exists($shapefile->file_path)) {
            Storage::disk('public')->delete($shapefile->file_path);
        }

        // Hapus data
        $shapefile->delete();

        return redirect()->route('shapefile.index')
            ->with('success', 'Shapefile berhasil dihapus!');
    }

    /**
     * Process a shapefile to import into the system.
     */
    public function process(Shapefile $shapefile)
    {
        // Pastikan shapefile belum diproses
        if ($shapefile->geometry) {
            return redirect()->route('shapefile.index')
                ->with('error', 'Shapefile ini sudah diproses sebelumnya!');
        }

        // Pastikan file ada
        if (!$shapefile->file_path || !Storage::disk('public')->exists($shapefile->file_path)) {
            return redirect()->route('shapefile.index')
                ->with('error', 'File shapefile tidak ditemukan!');
        }

        try {
            // Tambahkan info debug
            $sessionId = uniqid('process_');
            Log::info('[' . $sessionId . '] Memulai proses shapefile: ' . $shapefile->id . ' - ' . $shapefile->name . ' (Tipe: ' . $shapefile->type . ')');

            $filePath = Storage::disk('public')->path($shapefile->file_path);
            $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
            Log::info('[' . $sessionId . '] File path: ' . $filePath . ', Extension: ' . $fileExtension);

            // DEBUG - Simpan data lengkap untuk keperluan debugging
            file_put_contents(
                storage_path('logs/shapefile_debug_' . $shapefile->id . '.txt'),
                "File Path: $filePath\nExtension: $fileExtension\nType: {$shapefile->type}\n"
            );

            $geometryWkt = null;

            // Debug info
            $debugInfo = [
                'session_id' => $sessionId,
                'file_path' => $shapefile->file_path,
                'file_extension' => $fileExtension,
                'file_size' => filesize($filePath) . ' bytes',
                'file_mime' => mime_content_type($filePath),
                'shapefile_type' => $shapefile->type,
                'shapefile_id' => $shapefile->id
            ];

            // Process based on file type
            if (strtolower($fileExtension) === 'kml') {
                    // Proses file KML
                Log::info('[' . $sessionId . '] Memproses file KML');
                $kmlContent = file_get_contents($filePath);

                // Ubah untuk mendapatkan semua placemark dari KML
                $placemarksData = $this->processKmlFile($kmlContent);
                        $debugInfo['geometry_type'] = 'KML';
                $debugInfo['placemark_count'] = count($placemarksData);

                Log::info('[' . $sessionId . '] Berhasil memproses file KML, menemukan ' . count($placemarksData) . ' placemark');

                // Gunakan geometri placemark pertama untuk shapefile
                if (!empty($placemarksData)) {
                    $geometryWkt = $placemarksData[0]['geometry'];
                }
                } elseif (strtolower($fileExtension) === 'zip') {
                // Proses file ZIP
                Log::info('[' . $sessionId . '] Memproses file ZIP');

                    // Ekstrak file ZIP
                    $tempDir = storage_path('app/temp_' . uniqid());
                    if (!file_exists($tempDir)) {
                        mkdir($tempDir, 0755, true);
                    }

                // Coba ekstrak ZIP dengan beberapa metode alternatif
                    $extractSuccess = false;
                try {
                    Log::info('[' . $sessionId . '] Mencoba metode ekstraksi file ZIP...');

                    // METODE 1: Coba gunakan PHP extension
                    if (extension_loaded('zip')) {
                        Log::info('[' . $sessionId . '] PHP ZIP extension tersedia, mencoba menggunakan ZipArchive');
                            $zip = new \ZipArchive();
                            if ($zip->open($filePath) === TRUE) {
                                $zip->extractTo($tempDir);
                                $zip->close();
                                $extractSuccess = true;
                                Log::info('[' . $sessionId . '] Ekstraksi berhasil dengan ZipArchive');
                            } else {
                            Log::warning('[' . $sessionId . '] ZipArchive gagal membuka file ZIP');
                            }
                    } else {
                        Log::warning('[' . $sessionId . '] PHP ZIP extension tidak tersedia');
                    }

                    // METODE 2: Gunakan command unzip (Unix/Linux/Mac atau Windows dengan unzip)
                    if (!$extractSuccess) {
                        $unzipCmd = "unzip -o \"$filePath\" -d \"$tempDir\"";
                        Log::info('[' . $sessionId . '] Mencoba unzip command: ' . $unzipCmd);

                        exec($unzipCmd, $output, $retVal);
                        if ($retVal === 0 || $retVal === 1) { // 1 berarti ada warning tapi masih bisa ekstrak
                            $extractSuccess = true;
                            Log::info('[' . $sessionId . '] Ekstraksi berhasil dengan unzip command');
                        } else {
                            Log::warning('[' . $sessionId . '] unzip command gagal dengan kode: ' . $retVal);
                        }
                    }

                    // METODE 3: Gunakan PowerShell (khusus Windows)
                    if (!$extractSuccess && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        Log::info('[' . $sessionId . '] Mencoba PowerShell pada Windows');
                        $psCmd = "powershell.exe -Command \"& {Expand-Archive -LiteralPath '$filePath' -DestinationPath '$tempDir' -Force}\"";
                        Log::info('[' . $sessionId . '] PowerShell command: ' . $psCmd);

                        exec($psCmd, $output, $retVal);
                            if ($retVal === 0) {
                                $extractSuccess = true;
                                Log::info('[' . $sessionId . '] Ekstraksi berhasil dengan PowerShell');
                            } else {
                            Log::warning('[' . $sessionId . '] PowerShell gagal dengan kode: ' . $retVal);
                        }
                    }

                    // METODE 4: Gunakan 7-Zip jika tersedia
                    if (!$extractSuccess) {
                        $sevenZipPaths = [
                            'C:\\Program Files\\7-Zip\\7z.exe',  // Windows 64-bit
                            'C:\\Program Files (x86)\\7-Zip\\7z.exe',  // Windows 32-bit
                            '/usr/bin/7z',  // Linux
                            '/usr/local/bin/7z'  // macOS
                        ];

                        foreach ($sevenZipPaths as $sevenZipPath) {
                            if (file_exists($sevenZipPath)) {
                                $cmd = "\"$sevenZipPath\" x \"$filePath\" -o\"$tempDir\" -y";
                                Log::info('[' . $sessionId . '] Mencoba 7-Zip: ' . $cmd);

                                exec($cmd, $output, $retVal);
                                if ($retVal === 0) {
                                    $extractSuccess = true;
                                    Log::info('[' . $sessionId . '] Ekstraksi berhasil dengan 7-Zip');
                                    break;
                                }
                            }
                        }
                    }

                    // METODE 5: Jika PHP-Zip tersedia sebagai composer package
                    if (!$extractSuccess && class_exists('\\PhpZip\\ZipFile')) {
                        try {
                            Log::info('[' . $sessionId . '] Mencoba menggunakan PhpZip package');
                            $zipFile = new \PhpZip\ZipFile();
                            $zipFile->openFile($filePath)
                                ->extractTo($tempDir)
                                ->close();
                            $extractSuccess = true;
                            Log::info('[' . $sessionId . '] Ekstraksi berhasil dengan PhpZip package');
                        } catch (\Exception $e) {
                            Log::error('[' . $sessionId . '] PhpZip error: ' . $e->getMessage());
                        }
                    }

                } catch (\Exception $e) {
                    Log::error('[' . $sessionId . '] Error saat mengekstrak ZIP: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                    }

                    if ($extractSuccess) {
                    // Log daftar file yang berhasil diekstrak
                    $extractedFiles = glob($tempDir . '/*.*');
                    Log::info('[' . $sessionId . '] File yang berhasil diekstrak: ' . json_encode($extractedFiles));

                    // Cari file shp atau kml di direktori dan subdirektori
                        $shpFiles = glob($tempDir . '/*.shp');
                    $kmlFiles = glob($tempDir . '/*.kml');
                    $dbfFiles = glob($tempDir . '/*.dbf');

                    // Log file dbf yang ditemukan
                    Log::info('[' . $sessionId . '] DBF files: ' . json_encode($dbfFiles));

                    // Jika tidak menemukan di direktori utama, coba cari di subdirektori
                        if (empty($shpFiles)) {
                        $subdirs = glob($tempDir . '/*', GLOB_ONLYDIR);
                        foreach ($subdirs as $subdir) {
                            $subShpFiles = glob($subdir . '/*.shp');
                                if (!empty($subShpFiles)) {
                                    $shpFiles = $subShpFiles;
                                // Jika menemukan SHP, cari juga DBF di folder yang sama
                                $subDbfFiles = glob($subdir . '/*.dbf');
                                if (!empty($subDbfFiles)) {
                                    $dbfFiles = $subDbfFiles;
                                }
                                    break;
                                }
                        }
                    }

                    if (empty($kmlFiles)) {
                        $subdirs = glob($tempDir . '/*', GLOB_ONLYDIR);
                        foreach ($subdirs as $subdir) {
                            $subKmlFiles = glob($subdir . '/*.kml');
                            if (!empty($subKmlFiles)) {
                                $kmlFiles = $subKmlFiles;
                                break;
                            }
                        }
                    }

                    // Log hasil pencarian file
                    Log::info('[' . $sessionId . '] SHP files: ' . json_encode($shpFiles));
                    Log::info('[' . $sessionId . '] KML files: ' . json_encode($kmlFiles));

                    // Proses file SHP atau KML jika ditemukan
                        if (!empty($shpFiles)) {
                        // Proses file SHP yang ditemukan
                            $shpFile = $shpFiles[0];
                        Log::info('[' . $sessionId . '] Memproses file SHP dari ZIP: ' . $shpFile);

                        // Coba konversi SHP ke WKT menggunakan berbagai metode
                            try {
                            // Cek apakah file-file pendukung SHP ada
                                $dbfFile = str_replace('.shp', '.dbf', $shpFile);
                            $shxFile = str_replace('.shp', '.shx', $shpFile);
                                $prjFile = str_replace('.shp', '.prj', $shpFile);

                            Log::info('[' . $sessionId . '] File pendukung SHP: ' .
                                'DBF=' . (file_exists($dbfFile) ? 'Ada' : 'Tidak ada') . ', ' .
                                'SHX=' . (file_exists($shxFile) ? 'Ada' : 'Tidak ada') . ', ' .
                                'PRJ=' . (file_exists($prjFile) ? 'Ada' : 'Tidak ada'));

                            // METODE 1: Coba gunakan ogr2ogr jika tersedia
                            $ogrCmd = null;

                            // Cek ogr2ogr di berbagai lokasi
                            $possibleOgrPaths = [
                                'ogr2ogr', // Jika di PATH
                                '/usr/bin/ogr2ogr',
                                '/usr/local/bin/ogr2ogr',
                                'C:\\OSGeo4W\\bin\\ogr2ogr.exe',
                                'C:\\Program Files\\QGIS\\bin\\ogr2ogr.exe'
                            ];

                            foreach ($possibleOgrPaths as $ogrPath) {
                                $testCmd = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? "where $ogrPath" : "which $ogrPath";
                                exec($testCmd, $output, $retVal);

                                if ($retVal === 0) {
                                    $ogrCmd = $ogrPath;
                                    Log::info('[' . $sessionId . '] Menemukan ogr2ogr di: ' . $ogrPath);
                                    break;
                                }
                            }

                            if ($ogrCmd) {
                                // Konversi SHP ke GeoJSON dengan memastikan proyeksi EPSG 4326
                                $tempGeoJson = $tempDir . '/temp.geojson';
                                // Ubah parameter untuk memastikan presisi koordinat penuh dan bentuk poligon dipertahankan
                                $fullCmd = "$ogrCmd -f GeoJSON \"$tempGeoJson\" \"$shpFile\" -t_srs EPSG:4326 -lco COORDINATE_PRECISION=15 -preserveGeometry -skipfailures";
                                Log::info('[' . $sessionId . '] Menjalankan: ' . $fullCmd);

                                exec($fullCmd, $output, $retVal);
                                Log::info('[' . $sessionId . '] ogr2ogr output: ' . implode("\n", $output));

                                if ($retVal === 0 && file_exists($tempGeoJson)) {
                                    $geoJson = file_get_contents($tempGeoJson);
                                    Log::info('[' . $sessionId . '] GeoJSON berhasil dibuat: ' . substr($geoJson, 0, 100) . '...');

                                    $geoData = json_decode($geoJson, true);
                                    if ($geoData && isset($geoData['features']) && !empty($geoData['features'])) {
                                        // Periksa semua fitur yang ada, cari yang memiliki geometri valid
                                        $validFeature = null;

                                        foreach ($geoData['features'] as $feature) {
                                            if (isset($feature['geometry']) &&
                                                isset($feature['geometry']['coordinates']) &&
                                                !empty($feature['geometry']['coordinates'])) {
                                                $validFeature = $feature;
                                                break;
                                            }
                                        }

                                        if ($validFeature) {
                                            $geometry = $validFeature['geometry'];

                                            // Konversi GeoJSON ke WKT menggunakan PostGIS dengan SRID 4326
                                            try {
                                        $geometryJson = json_encode($geometry);
                                                Log::info('[' . $sessionId . '] GeoJSON yang akan diproses: ' . substr($geometryJson, 0, 200) . '...');

                                                // Pastikan semua koordinat dipertahankan dengan akurasi tinggi
                                        $result = DB::select("SELECT ST_AsText(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)) as wkt", [$geometryJson]);

                                        if (!empty($result) && isset($result[0]->wkt)) {
                                            $geometryWkt = $result[0]->wkt;
                                                    $debugInfo['geometry_type'] = 'SHP from ZIP (ogr2ogr)';
                                                    Log::info('[' . $sessionId . '] Berhasil memproses SHP dari ZIP dengan ogr2ogr');

                                                    // Validasi hasil WKT
                                                    $validation = DB::select("SELECT ST_IsValid(ST_GeomFromText(?, 4326)) as is_valid", [$geometryWkt]);
                                                    if (!empty($validation) && $validation[0]->is_valid) {
                                                        Log::info('[' . $sessionId . '] Bentuk geometri valid');
                                        } else {
                                                        Log::warning('[' . $sessionId . '] Bentuk geometri tidak valid, mencoba memperbaiki');
                                                        // Coba perbaiki geometri jika tidak valid
                                                        $fixed = DB::select("SELECT ST_AsText(ST_MakeValid(ST_GeomFromText(?, 4326))) as wkt", [$geometryWkt]);
                                                        if (!empty($fixed) && isset($fixed[0]->wkt)) {
                                                            $geometryWkt = $fixed[0]->wkt;
                                                            Log::info('[' . $sessionId . '] Geometri berhasil diperbaiki');
                                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                                Log::error('[' . $sessionId . '] Error saat konversi GeoJSON ke WKT: ' . $e->getMessage());
                            }
                        } else {
                                            Log::warning('[' . $sessionId . '] Tidak ditemukan fitur valid dalam GeoJSON');
                                }
                            } else {
                                        Log::warning('[' . $sessionId . '] Format GeoJSON tidak valid atau tidak memiliki fitur');
                            }
                                } else {
                                    Log::warning('[' . $sessionId . '] ogr2ogr gagal dengan kode: ' . $retVal);
                        }
                    } else {
                                Log::warning('[' . $sessionId . '] ogr2ogr tidak ditemukan di sistem');
                            }

                            // METODE 2: Jika ogr2ogr gagal, coba gunakan PostGIS shp2pgsql dengan parameter spesifik untuk proyeksi 4326
                            if (!isset($geometryWkt) && $this->commandExists('shp2pgsql')) {
                                Log::info('[' . $sessionId . '] Mencoba shp2pgsql dengan opsi presisi tinggi...');

                                $tempSql = $tempDir . '/temp.sql';
                                // Gunakan parameter -k untuk mempertahankan semua kolom dan -G untuk menghasilkan geometri
                                $cmd = "shp2pgsql -s 4326 -k -G \"$shpFile\" temp_shape > \"$tempSql\"";
                                exec($cmd, $output, $retVal);

                                if ($retVal === 0 && file_exists($tempSql)) {
                                    $sqlContent = file_get_contents($tempSql);

                                    // Ekstrak WKT dari file SQL dengan lebih hati-hati
                                    if (preg_match('/ST_GeomFromText\(\'([^\']+)\'/', $sqlContent, $matches)) {
                                        $geometryWkt = $matches[1];
                                        $debugInfo['geometry_type'] = 'SHP from ZIP (shp2pgsql)';
                                        Log::info('[' . $sessionId . '] Berhasil mengekstrak geometri dengan shp2pgsql');

                                        // Validasi dan perbaiki geometri jika perlu
                                        $validation = DB::select("SELECT ST_IsValid(ST_GeomFromText(?, 4326)) as is_valid", [$geometryWkt]);
                                        if (!empty($validation) && !$validation[0]->is_valid) {
                                            Log::warning('[' . $sessionId . '] Bentuk geometri tidak valid dari shp2pgsql, mencoba memperbaiki');
                                            $fixed = DB::select("SELECT ST_AsText(ST_MakeValid(ST_GeomFromText(?, 4326))) as wkt", [$geometryWkt]);
                                            if (!empty($fixed) && isset($fixed[0]->wkt)) {
                                                $geometryWkt = $fixed[0]->wkt;
                                                Log::info('[' . $sessionId . '] Geometri berhasil diperbaiki');
                                            }
                                        }
                                    }
                                }
                            }

                            // METODE 3: Jika semua metode di atas gagal, coba dapatkan koordinat dari file pendukung
                            if (!isset($geometryWkt)) {
                                // Coba baca file PRJ untuk info proyeksi
                                $prjFile = str_replace('.shp', '.prj', $shpFile);
                                if (file_exists($prjFile)) {
                                    Log::info('[' . $sessionId . '] Membaca file PRJ untuk info proyeksi');
                                    $prjContent = file_get_contents($prjFile);
                                    Log::info('[' . $sessionId . '] Konten PRJ: ' . $prjContent);
                                }

                                // Coba ekstrak koordinat dari file pendukung
                                $extracted = $this->extractCoordinatesFromShapefileFiles($tempDir, $sessionId, $debugInfo, $geometryWkt);

                                if (!$extracted) {
                                    // Dapatkan bounding box area sekitar dari file lain yang sudah berhasil di database
                                    try {
                                        Log::warning('[' . $sessionId . '] Menggunakan bounding box area dari shapefile lain sebagai fallback');
                                        $validShapefile = DB::table('shapefiles')
                                            ->where('geometry', '!=', 'POLYGON((107.5 -7.0, 107.5 -6.9, 107.7 -6.9, 107.7 -7.0, 107.5 -7.0))')
                                            ->where('geometry', '!=', null)
                                            ->where('id', '!=', $shapefile->id)
                                            ->first();

                                        if ($validShapefile) {
                                            // Dapatkan bounding box
                                            $bounds = DB::select("
                                                SELECT
                                                    ST_XMin(ST_Envelope(ST_GeomFromText(?, 4326))) as xmin,
                                                    ST_YMin(ST_Envelope(ST_GeomFromText(?, 4326))) as ymin,
                                                    ST_XMax(ST_Envelope(ST_GeomFromText(?, 4326))) as xmax,
                                                    ST_YMax(ST_Envelope(ST_GeomFromText(?, 4326))) as ymax
                                            ", [$validShapefile->geometry, $validShapefile->geometry, $validShapefile->geometry, $validShapefile->geometry]);

                                            if (!empty($bounds)) {
                                                $xmin = $bounds[0]->xmin;
                                                $ymin = $bounds[0]->ymin;
                                                $xmax = $bounds[0]->xmax;
                                                $ymax = $bounds[0]->ymax;

                                                // Buat polygon dari bounding box dengan sedikit offset
                                                $geometryWkt = "POLYGON(($xmin $ymin, $xmin $ymax, $xmax $ymax, $xmax $ymin, $xmin $ymin))";
                                                $debugInfo['geometry_type'] = 'Bounding Box dari shapefile lain';
                                                Log::info('[' . $sessionId . '] Menggunakan bounding box: ' . $geometryWkt);
                                    } else {
                                                // Gunakan koordinat default yang lebih akurat berdasarkan area Bandung
                                                $geometryWkt = "POLYGON((106.774 -7.072, 106.774 -7.063, 106.782 -7.063, 106.782 -7.072, 106.774 -7.072))";
                                                $debugInfo['geometry_type'] = 'Default Polygon (Bandung area)';
                                    }
                                } else {
                                            // Gunakan koordinat default yang lebih akurat berdasarkan area Bandung
                                            $geometryWkt = "POLYGON((106.774 -7.072, 106.774 -7.063, 106.782 -7.063, 106.782 -7.072, 106.774 -7.072))";
                                            $debugInfo['geometry_type'] = 'Default Polygon (Bandung area)';
                                        }
                                    } catch (\Exception $e) {
                                        Log::error('[' . $sessionId . '] Error saat mendapatkan bounding box: ' . $e->getMessage());
                                        // Gunakan koordinat default yang lebih akurat berdasarkan area Bandung
                                        $geometryWkt = "POLYGON((106.774 -7.072, 106.774 -7.063, 106.782 -7.063, 106.782 -7.072, 106.774 -7.072))";
                                        $debugInfo['geometry_type'] = 'Default Polygon (Bandung area)';
                                    }
                                }
                            }

                        } catch (\Exception $e) {
                            Log::error('[' . $sessionId . '] Error saat memproses SHP: ' . $e->getMessage());
                            // Gunakan koordinat default yang lebih akurat berdasarkan area Bandung
                            $geometryWkt = "POLYGON((106.774 -7.072, 106.774 -7.063, 106.782 -7.063, 106.782 -7.072, 106.774 -7.072))";
                            $debugInfo['geometry_type'] = 'Default Polygon (SHP error)';
                        }
                    } else if (!empty($kmlFiles)) {
                        // Proses file KML yang ditemukan
                                    $kmlFile = $kmlFiles[0];
                        try {
                            Log::info('[' . $sessionId . '] Memproses file KML dari ZIP: ' . $kmlFile);
                                    $kmlContent = file_get_contents($kmlFile);
                            if (!$kmlContent) {
                                Log::error('[' . $sessionId . '] Gagal membaca konten KML file');
                            } else {
                                Log::info('[' . $sessionId . '] Berhasil membaca KML: ' . strlen($kmlContent) . ' bytes');
                                    $geometryWkt = $this->processKmlFile($kmlContent);
                                $debugInfo['geometry_type'] = 'KML from ZIP';
                                    Log::info('[' . $sessionId . '] Berhasil memproses KML dari ZIP');
                            }
                                } catch (\Exception $e) {
                            Log::error('[' . $sessionId . '] Error saat memproses KML dari ZIP: ' . $e->getMessage());
                                }
                            } else {
                        // Jika tidak menemukan file yang didukung, buat polygon default
                        Log::warning('[' . $sessionId . '] Tidak menemukan file SHP atau KML di ZIP');
                        $geometryWkt = "POLYGON((107.5 -7.0, 107.5 -6.9, 107.7 -6.9, 107.7 -7.0, 107.5 -7.0))";
                        $debugInfo['geometry_type'] = 'Default Polygon';
                        }

                        // Bersihkan direktori temp
                        $this->removeDirectory($tempDir);
                    } else {
                    // Jika semua metode ekstraksi gagal
                    Log::error('[' . $sessionId . '] Semua metode ekstraksi ZIP gagal. Menggunakan polygon default area Bandung');
                    // Ubah polygon default menjadi lebih kecil untuk mengurangi kesalahan
                    $geometryWkt = "POLYGON((106.7745 -7.0765, 106.7745 -7.0715, 106.7795 -7.0715, 106.7795 -7.0765, 106.7745 -7.0765))";
                    $debugInfo['geometry_type'] = 'Default Polygon (extraction failed)';
                    }
                } else {
                    // Format file tidak didukung
                return redirect()->route('shapefile.index')
                    ->with('error', 'Format file tidak didukung. Gunakan .kml atau .zip');
            }

            // Simpan geometri ke shapefile
            if ($geometryWkt) {
                $shapefile->geometry = $geometryWkt;
                $shapefile->processed = true;
                $shapefile->save();

                // Jika shapefile bertipe tree, ekstrak data tree
                if ($shapefile->type === 'tree') {
                    // Tambahkan log untuk tracking proses
                    Log::info('[' . $sessionId . '] Shapefile tipe tree terdeteksi, memulai proses ekstraksi pohon');

                    // Ekstraksi detail untuk debugging
                    Log::info('[TREE IMPORT] Memulai ekstraksi pohon untuk shapefile ID=' . $shapefile->id . ' dengan nama ' . $shapefile->name);

                    try {
                        // Identifikasi tipe geometri
                        $geomType = DB::select("SELECT ST_GeometryType(ST_GeomFromText(?, 4326)) as geom_type", [$shapefile->geometry])[0]->geom_type;
                        Log::info('[TREE IMPORT] Tipe geometri shapefile: ' . $geomType);

                        // Untuk KML dengan multiple placemark
                        if (isset($placemarksData) && count($placemarksData) > 0) {
                            Log::info('[TREE IMPORT] Memproses ' . count($placemarksData) . ' placemark dari KML untuk shapefile ID=' . $shapefile->id . '.');
                            // DB::table('trees')->where('shapefile_id', $shapefile->id)->delete(); // DIHAPUS UNTUK MEMPERTAHANKAN EDITAN

                            $createdTrees = 0;

                            foreach ($placemarksData as $placemarkData) {
                                // Buat record tree untuk setiap placemark
                                $tree = $this->createTreeFromPolygon(
                                    $shapefile,
                                    $placemarkData['geometry'],
                                    $placemarkData['index'],
                                    $placemarkData['attributes']
                                );

                                if ($tree) {
                                    $createdTrees++;
                                    Log::info('[TREE IMPORT] Tree record #' . $createdTrees . ' berhasil dibuat dari placemark dengan ID: ' . $tree->id);
                                }
                            }

                            // Update tree count di shapefile
                            $shapefile->tree_count = $createdTrees;
                            $shapefile->save();

                            Log::info('[TREE IMPORT] Total ' . $createdTrees . ' trees berhasil dibuat dari KML');

                            if ($createdTrees > 0) {
                                return redirect()->route('shapefile.index')
                                    ->with('success', 'Shapefile berhasil diproses dengan ' . $createdTrees . ' pohon!');
                            } else {
                                return redirect()->route('shapefile.index')
                                    ->with('warning', 'Shapefile berhasil diproses tetapi tidak ada pohon yang berhasil dibuat. Periksa format KML Anda.');
                            }
                        } else {
                            // Untuk file non-KML atau KML tanpa multiple placemark, gunakan cara lama
                        $result = $this->extractTreesFromShapefile($shapefile); // Deletion logic will be removed from this method too
                        Log::info('[TREE IMPORT] Hasil ekstraksi pohon: ' . ($result ? 'Berhasil' : 'Gagal'));

                        // Jika gagal ekstraksi, tambahkan pesan error
                        if (!$result) {
                            Log::error('[TREE IMPORT] Gagal mengekstrak pohon dari shapefile');
                            return redirect()->route('shapefile.index')
                                ->with('warning', 'Shapefile berhasil diproses tetapi gagal mengekstrak data pohon. Silakan coba lagi atau periksa format file.');
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('[TREE IMPORT] Error saat ekstraksi pohon: ' . $e->getMessage());
                        Log::error('[TREE IMPORT] ' . $e->getTraceAsString());
                        return redirect()->route('shapefile.index')
                            ->with('warning', 'Shapefile berhasil diproses tetapi gagal mengekstrak data pohon: ' . $e->getMessage());
                    }
                } else if ($shapefile->type === 'plantation') {
                    // Import ke tabel plantations
                    $this->importToPlantation($shapefile);
                }

                return redirect()->route('shapefile.index')
                    ->with('success', 'Shapefile berhasil diproses!');
            } else {
                return redirect()->route('shapefile.index')
                    ->with('error', 'Gagal memproses shapefile. Tidak dapat mengekstrak geometri!');
            }

        } catch (\Exception $e) {
            Log::error('Error processing shapefile: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return redirect()->route('shapefile.index')
                ->with('error', 'Gagal memproses shapefile: ' . $e->getMessage());
        }
    }

    /**
     * Process KML file content and convert it to WKT with full precision.
     * Modified to extract all placemarks with their geometries and attributes.
     *
     * @param string $kmlContent
     * @return array Array of placemark data containing geometry WKT and attributes
     */
    private function processKmlFile($kmlContent)
    {
        try {
            // 1. Parse KML file
            $dom = new \DOMDocument();
            $dom->preserveWhiteSpace = true; // Mempertahankan whitespace untuk presisi
            $dom->loadXML($kmlContent, LIBXML_NOERROR);

            // Aktifkan error handling untuk libraryXML
            $errors = libxml_get_errors();
            libxml_clear_errors();

            if (!empty($errors)) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = "Line {$error->line}: {$error->message}";
                }
                throw new \Exception('Error saat parsing XML KML: ' . implode(', ', $errorMessages));
            }

            // 2. Cari elemen Placemark
            $placemarks = $dom->getElementsByTagName('Placemark');

            if ($placemarks->length === 0) {
                // Debugging: Print struktur KML untuk membantu troubleshooting
                $rootElement = $dom->documentElement->nodeName;
                $namespaces = [];

                if ($dom->documentElement->hasAttributes()) {
                    foreach ($dom->documentElement->attributes as $attr) {
                        if (strpos($attr->nodeName, 'xmlns:') === 0) {
                            $namespaces[] = $attr->nodeName . '="' . $attr->nodeValue . '"';
                        }
                    }
                }

                $namespaceInfo = !empty($namespaces) ? " dengan namespace " . implode(', ', $namespaces) : "";
                throw new \Exception("Tidak ada placemark yang ditemukan dalam file KML. Root element: {$rootElement}{$namespaceInfo}");
            }

            Log::info('Jumlah placemark ditemukan dalam KML: ' . $placemarks->length);

            // 3. Proses semua placemark dan ekstrak geometri dan atribut
            $placemarksData = [];

            foreach ($placemarks as $index => $placemark) {
                // Collect placemark attributes from ExtendedData or SimpleData
                $attributes = [];

                // Get placemark name if available
                $placeName = $placemark->getElementsByTagName('name')->item(0);
                $placemarkName = $placeName ? $placeName->nodeValue : 'tanpa nama';
                $attributes['NAME'] = $placemarkName;

                // Try to get attributes from ExtendedData
                $extendedData = $placemark->getElementsByTagName('ExtendedData')->item(0);
                if ($extendedData) {
                    $dataElements = $extendedData->getElementsByTagName('Data');
                    foreach ($dataElements as $data) {
                        if ($data->hasAttribute('name')) {
                            $name = $data->getAttribute('name');
                            $value = $data->getElementsByTagName('value')->item(0);
                            if ($value) {
                                $attributes[$name] = $value->nodeValue;
                            }
                        }
                    }

                    // Also check for SimpleData elements (used in some KML formats)
                    $simpleDataElements = $extendedData->getElementsByTagName('SimpleData');
                    foreach ($simpleDataElements as $simpleData) {
                        if ($simpleData->hasAttribute('name')) {
                            $name = $simpleData->getAttribute('name');
                            $attributes[$name] = $simpleData->nodeValue;
                        }
                    }
                }

                // Try to get description which might contain HTML with attributes
                $description = $placemark->getElementsByTagName('description')->item(0);
                if ($description && $description->nodeValue) {
                    $descContent = $description->nodeValue;
                    // Try to extract data from HTML tables in description
                    if (preg_match_all('/<tr[^>]*><td[^>]*>(.*?)<\/td><td[^>]*>(.*?)<\/td><\/tr>/is', $descContent, $matches)) {
                        for ($i = 0; $i < count($matches[0]); $i++) {
                            $key = strip_tags($matches[1][$i]);
                            $value = strip_tags($matches[2][$i]);
                            $attributes[trim($key)] = trim($value);
                        }
                    }
                }

                // Cek untuk Polygon
                $polygon = $placemark->getElementsByTagName('Polygon')->item(0);
                $geometryWkt = null;

                if ($polygon) {
                    $outerBoundary = $polygon->getElementsByTagName('outerBoundaryIs')->item(0);
                    if ($outerBoundary) {
                        $linearRing = $outerBoundary->getElementsByTagName('LinearRing')->item(0);
                        if ($linearRing) {
                            $coordinates = $linearRing->getElementsByTagName('coordinates')->item(0);
                            if ($coordinates && $coordinates->nodeValue) {
                                $geometryWkt = $this->kmlCoordinatesToWkt($coordinates->nodeValue, true);
                            }
                        }
                    }
                }

                // Cek untuk Point (lokasi tunggal)
                if (!$geometryWkt) {
                $point = $placemark->getElementsByTagName('Point')->item(0);
                if ($point) {
                    $coordinates = $point->getElementsByTagName('coordinates')->item(0);
                    if ($coordinates && $coordinates->nodeValue) {
                        // Untuk Point, kita perlu konversi khusus
                        $coords = trim($coordinates->nodeValue);
                        $coordArray = explode(',', $coords);
                        if (count($coordArray) >= 2) {
                            // Gunakan koordinat asli tanpa membulatkan
                                $geometryWkt = "POINT({$coordArray[0]} {$coordArray[1]})";
                            }
                        }
                    }
                }

                // Cek jika ada LineString
                if (!$geometryWkt) {
                $lineString = $placemark->getElementsByTagName('LineString')->item(0);
                if ($lineString) {
                    $coordinates = $lineString->getElementsByTagName('coordinates')->item(0);
                    if ($coordinates && $coordinates->nodeValue) {
                        $coordStr = trim($coordinates->nodeValue);
                        $coordPairs = preg_split('/\s+/', $coordStr);
                        $points = [];

                        foreach ($coordPairs as $pair) {
                            if (empty($pair)) continue;
                            $coords = explode(',', $pair);
                            if (count($coords) >= 2) {
                                // Gunakan koordinat asli tanpa membulatkan
                                $points[] = $coords[0] . ' ' . $coords[1];
                            }
                        }

                        if (count($points) >= 2) {
                                $geometryWkt = 'LINESTRING(' . implode(',', $points) . ')';
                            }
                        }
                    }
                }

                // Cek jika ada MultiGeometry
                if (!$geometryWkt) {
                $multiGeometry = $placemark->getElementsByTagName('MultiGeometry')->item(0);
                if ($multiGeometry) {
                    $polygons = $multiGeometry->getElementsByTagName('Polygon');
                    if ($polygons->length > 0) {
                        // Konversi ke MultiPolygon WKT dengan presisi penuh
                            $geometryWkt = $this->kmlMultiPolygonToWkt($polygons, true);
                        }
                    }
                }

                // Jika geometri berhasil diekstrak, tambahkan ke hasil
                if ($geometryWkt) {
                    $placemarksData[] = [
                        'geometry' => $geometryWkt,
                        'attributes' => $attributes,
                        'index' => $index
                    ];
                }
            }

            // Jika menggunakan namespace, coba pendekatan alternatif
            if (empty($placemarksData)) {
            $kml = $dom->documentElement;
            $namespace = $kml->namespaceURI;

            if ($namespace) {
                // Buat XPath dengan namespace
                $xpath = new \DOMXPath($dom);
                $xpath->registerNamespace('kml', $namespace);

                // Coba cari dengan XPath
                $placemarks = $xpath->query('//kml:Placemark');
                if ($placemarks && $placemarks->length > 0) {
                        foreach ($placemarks as $index => $placemark) {
                            // Collect attributes from namespaced elements
                            $attributes = [];

                            // Get placemark name
                            $placeName = $xpath->query('.//kml:name', $placemark)->item(0);
                            $placemarkName = $placeName ? $placeName->nodeValue : 'tanpa nama';
                            $attributes['NAME'] = $placemarkName;

                            // Get ExtendedData
                            $extendedDataNodes = $xpath->query('.//kml:ExtendedData', $placemark);
                            if ($extendedDataNodes && $extendedDataNodes->length > 0) {
                                $extendedData = $extendedDataNodes->item(0);
                                $dataElements = $xpath->query('.//kml:Data', $extendedData);

                                foreach ($dataElements as $data) {
                                    if ($data->hasAttribute('name')) {
                                        $name = $data->getAttribute('name');
                                        $value = $xpath->query('.//kml:value', $data)->item(0);
                                        if ($value) {
                                            $attributes[$name] = $value->nodeValue;
                                        }
                                    }
                                }

                                // Check for SimpleData
                                $simpleDataElements = $xpath->query('.//kml:SimpleData', $extendedData);
                                foreach ($simpleDataElements as $simpleData) {
                                    if ($simpleData->hasAttribute('name')) {
                                        $name = $simpleData->getAttribute('name');
                                        $attributes[$name] = $simpleData->nodeValue;
                                    }
                                }
                            }

                            // Try to get geometry with namespace
                            $geometryWkt = null;

                            // Check for Polygon
                        $polygons = $xpath->query('.//kml:Polygon', $placemark);
                        if ($polygons && $polygons->length > 0) {
                            $polygon = $polygons->item(0);
                            $outerBoundaries = $xpath->query('.//kml:outerBoundaryIs', $polygon);

                            if ($outerBoundaries && $outerBoundaries->length > 0) {
                                $outerBoundary = $outerBoundaries->item(0);
                                $linearRings = $xpath->query('.//kml:LinearRing', $outerBoundary);

                                if ($linearRings && $linearRings->length > 0) {
                                    $linearRing = $linearRings->item(0);
                                    $coordinatesNodes = $xpath->query('.//kml:coordinates', $linearRing);

                                    if ($coordinatesNodes && $coordinatesNodes->length > 0) {
                                        $coordinates = $coordinatesNodes->item(0);
                                        if ($coordinates && $coordinates->nodeValue) {
                                                $geometryWkt = $this->kmlCoordinatesToWkt($coordinates->nodeValue, true);
                                            }
                                        }
                                    }
                                }
                            }

                            // Check for Point
                            if (!$geometryWkt) {
                                $points = $xpath->query('.//kml:Point', $placemark);
                                if ($points && $points->length > 0) {
                                    $point = $points->item(0);
                                    $coordinatesNodes = $xpath->query('.//kml:coordinates', $point);

                                    if ($coordinatesNodes && $coordinatesNodes->length > 0) {
                                        $coordinates = $coordinatesNodes->item(0);
                                        if ($coordinates && $coordinates->nodeValue) {
                                            $coords = trim($coordinates->nodeValue);
                                            $coordArray = explode(',', $coords);
                                            if (count($coordArray) >= 2) {
                                                $geometryWkt = "POINT({$coordArray[0]} {$coordArray[1]})";
                            }
                        }
                    }
                }
            }

                            // If we found geometry, add it to the results
                            if ($geometryWkt) {
                                $placemarksData[] = [
                                    'geometry' => $geometryWkt,
                                    'attributes' => $attributes,
                                    'index' => $index
                                ];
                            }
                        }
                    }
                }
            }

            if (empty($placemarksData)) {
            throw new \Exception('Tidak dapat menemukan geometri yang valid dalam file KML. Harap pastikan format KML valid dan berisi data geometri (Polygon, LineString, Point, atau MultiGeometry).');
            }

            Log::info('Berhasil mengekstrak ' . count($placemarksData) . ' placemark dari file KML');
            return $placemarksData;

        } catch (\Exception $e) {
            // Re-throw exception dengan lebih banyak konteks
            throw new \Exception('Error saat memproses file KML: ' . $e->getMessage());
        }
    }

    /**
     * Convert KML coordinates string to WKT POLYGON with full precision.
     *
     * @param string $coordinatesStr Koordinat dari KML
     * @param bool $fullPrecision Apakah mempertahankan presisi penuh (default: true)
     * @return string WKT dengan presisi penuh
     */
    private function kmlCoordinatesToWkt($coordinatesStr, $fullPrecision = true)
    {
        // Bersihkan string koordinat
        $coordinates = trim($coordinatesStr);
        $coordinatePairs = preg_split('/\s+/', $coordinates);

        $points = [];
        foreach ($coordinatePairs as $pair) {
            if (empty($pair)) continue;

            $coords = explode(',', $pair);
            if (count($coords) >= 2) {
                // Gunakan koordinat asli dengan presisi penuh
                $points[] = $coords[0] . ' ' . $coords[1];
            }
        }

        if (count($points) < 3) {
            throw new \Exception('Polygon harus memiliki minimal 3 titik');
        }

        // Pastikan polygon tertutup (titik awal = titik akhir)
        if ($points[0] !== $points[count($points)-1]) {
            $points[] = $points[0];
        }

        // Log koordinat dengan presisi penuh untuk debugging
        Log::debug('Polygon points with full precision: ' . json_encode($points));

        return 'POLYGON((' . implode(',', $points) . '))';
    }

    /**
     * Convert KML MultiPolygon to WKT with full precision.
     *
     * @param DOMNodeList $polygons Daftar polygons dari KML
     * @param bool $fullPrecision Apakah mempertahankan presisi penuh (default: true)
     * @return string WKT MultiPolygon dengan presisi penuh
     */
    private function kmlMultiPolygonToWkt($polygons, $fullPrecision = true)
    {
        $polygonWkts = [];

        foreach ($polygons as $polygon) {
            $outerBoundary = $polygon->getElementsByTagName('outerBoundaryIs')->item(0);
            if ($outerBoundary) {
                $linearRing = $outerBoundary->getElementsByTagName('LinearRing')->item(0);
                if ($linearRing) {
                    $coordinates = $linearRing->getElementsByTagName('coordinates')->item(0);
                    if ($coordinates && $coordinates->nodeValue) {
                        $coordinates = trim($coordinates->nodeValue);
                        $coordinatePairs = preg_split('/\s+/', $coordinates);

                        $points = [];
                        foreach ($coordinatePairs as $pair) {
                            if (empty($pair)) continue;

                            $coords = explode(',', $pair);
                            if (count($coords) >= 2) {
                                // Gunakan koordinat asli dengan presisi penuh
                                $points[] = $coords[0] . ' ' . $coords[1];
                            }
                        }

                        if (count($points) >= 3) {
                            // Pastikan polygon tertutup
                            if ($points[0] !== $points[count($points)-1]) {
                                $points[] = $points[0];
                            }

                            $polygonWkts[] = '(' . implode(',', $points) . ')';
                        }
                    }
                }
            }
        }

        if (empty($polygonWkts)) {
            throw new \Exception('Tidak dapat menemukan geometri yang valid dalam MultiPolygon');
        }

        return 'MULTIPOLYGON((' . implode('),(', $polygonWkts) . '))';
    }

    /**
     * Recursively remove a directory.
     */
    private function removeDirectory($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->removeDirectory($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    /**
     * API method to get all shapefiles
     */
    public function getAll()
    {
        $shapefiles = Shapefile::where('geometry', '!=', null)
                    ->orderBy('created_at', 'desc')
                    ->get();

        // Konversi WKT ke GeoJSON untuk semua shapefile
        foreach ($shapefiles as $shapefile) {
            // Konversi WKT ke GeoJSON
            $geojson = null;
            try {
                $result = DB::select("SELECT ST_AsGeoJSON(ST_GeomFromText(?, 4326)) as geojson", [$shapefile->geometry]);
                if (!empty($result) && isset($result[0]->geojson)) {
                    $geojson = json_decode($result[0]->geojson);

                    // Tambahkan debug info untuk geometri
                    $geometryType = DB::select("SELECT ST_GeometryType(ST_GeomFromText(?, 4326)) as geometry_type", [$shapefile->geometry])[0]->geometry_type ?? 'Unknown';
                    Log::debug('Shapefile #' . $shapefile->id . ' (' . $shapefile->name . ') type: ' . $geometryType);
                }
            } catch (\Exception $e) {
                Log::error('Error converting WKT to GeoJSON for shapefile ID ' . $shapefile->id . ': ' . $e->getMessage());

                // Buat properti geojson placeholder agar tidak null
                if ($shapefile->type === 'tree') {
                    // Default polygon kecil untuk tree
                    $geojson = json_decode('{"type":"Polygon","coordinates":[[['
                        . ($shapefile->longitude ?? 107.6) . ',' . ($shapefile->latitude ?? -6.9) . '],'
                        . '[' . (($shapefile->longitude ?? 107.6) + 0.001) . ',' . ($shapefile->latitude ?? -6.9) . '],'
                        . '[' . (($shapefile->longitude ?? 107.6) + 0.001) . ',' . (($shapefile->latitude ?? -6.9) + 0.001) . '],'
                        . '[' . ($shapefile->longitude ?? 107.6) . ',' . (($shapefile->latitude ?? -6.9) + 0.001) . '],'
                        . '[' . ($shapefile->longitude ?? 107.6) . ',' . ($shapefile->latitude ?? -6.9) . ']]]}');

                    Log::info('Menggunakan polygon default untuk shapefile tree ID ' . $shapefile->id . ' karena error konversi');
                }
            }

            // Tambahkan geojson ke shapefile
            $shapefile->geojson = $geojson;
        }

        return response()->json([
            'success' => true,
            'data' => $shapefiles
        ]);
    }

    /**
     * API method to get shapefile by ID
     */
    public function getById($id)
    {
        $shapefile = Shapefile::where('id', $id)
                    ->where('geometry', '!=', null)
                    ->first();

        if (!$shapefile) {
            return response()->json([
                'success' => false,
                'message' => 'Shapefile tidak ditemukan'
            ], 404);
        }

        // Konversi WKT ke GeoJSON agar lebih mudah ditampilkan di Leaflet
        $geojson = null;
        try {
            $result = DB::select("SELECT ST_AsGeoJSON(ST_GeomFromText(?, 4326)) as geojson", [$shapefile->geometry]);
            if (!empty($result) && isset($result[0]->geojson)) {
                $geojson = json_decode($result[0]->geojson);

                // Tambahkan detail tipe geometri untuk debugging
                $geometryType = DB::select("SELECT ST_GeometryType(ST_GeomFromText(?, 4326)) as geometry_type", [$shapefile->geometry])[0]->geometry_type ?? 'Unknown';
                Log::info('GeoJSON berhasil dibuat: ' . json_encode($geojson) . ' (type: ' . $geometryType . ')');
            }
        } catch (\Exception $e) {
            Log::error('Error converting WKT to GeoJSON: ' . $e->getMessage());

            // Buat properti geojson placeholder agar tidak null
            if ($shapefile->type === 'tree') {
                // Coba dapatkan centroid untuk membuat polygon default
                try {
                    $centroid = DB::select("
                        SELECT
                            ST_Y(ST_Centroid(ST_GeomFromText(?, 4326))) as latitude,
                            ST_X(ST_Centroid(ST_GeomFromText(?, 4326))) as longitude
                    ", [$shapefile->geometry, $shapefile->geometry]);

                    $lat = $centroid[0]->latitude ?? -6.9;
                    $lng = $centroid[0]->longitude ?? 107.6;

                    // Default polygon kecil untuk tree berdasarkan centroid
                    $geojson = json_decode('{"type":"Polygon","coordinates":[[['
                        . $lng . ',' . $lat . '],'
                        . '[' . ($lng + 0.001) . ',' . $lat . '],'
                        . '[' . ($lng + 0.001) . ',' . ($lat + 0.001) . '],'
                        . '[' . $lng . ',' . ($lat + 0.001) . '],'
                        . '[' . $lng . ',' . $lat . ']]]}');

                    Log::info('Menggunakan polygon default untuk shapefile tree ID ' . $shapefile->id . ' dengan centroid');
                } catch (\Exception $e2) {
                    // Jika gagal mendapatkan centroid, gunakan nilai default
                    $geojson = json_decode('{"type":"Polygon","coordinates":[[[107.6,-6.9],[107.601,-6.9],[107.601,-6.899],[107.6,-6.899],[107.6,-6.9]]]}');
                    Log::error('Gagal mendapatkan centroid, menggunakan polygon default untuk shapefile tree ID ' . $shapefile->id);
                }
            }
        }

        // Tambahkan geojson ke response
        $shapefile->geojson = $geojson;

        return response()->json([
            'success' => true,
            'data' => $shapefile
        ]);
    }

    /**
     * Import shapefile to plantations table.
     */
    private function importToPlantation($shapefile)
    {
        try {
            Log::info('Mulai import shapefile ke plantation. Shapefile ID: ' . $shapefile->id);

            // Pastikan shapefile sudah memiliki geometry
            if (!$shapefile->geometry) {
                throw new \Exception('Shapefile tidak memiliki data geometri');
            }

            // Cari ID terkecil yang tersedia untuk plantation
            $availableId = $this->findSmallestAvailableIdForPlantation();

            // Buat record plantation baru
            $plantation = new \App\Models\Plantation();

            // Set ID ke nilai terkecil yang tersedia jika ditemukan
            if ($availableId) {
                $plantation->id = $availableId;
            }

            // Gunakan shapefile_id alih-alih user_id
            $plantation->shapefile_id = $shapefile->id;
            $plantation->name = $shapefile->name;
            $plantation->geometry = $shapefile->geometry;

            // Hitung luas area (dalam hektar) menggunakan PostGIS
            // Menggunakan EPSG:4326 langsung sebagaimana diminta
            // Catatan: Perhitungan luas dengan EPSG:4326 menggunakan fungsi geography
            // untuk memperoleh perhitungan yang akurat pada permukaan bumi
            $result = \Illuminate\Support\Facades\DB::select("
                SELECT
                    ST_Area(geography(ST_GeomFromText(?, 4326))) / 10000 as area_hectares
            ", [$shapefile->geometry]);

            if (!empty($result) && isset($result[0]->area_hectares)) {
                $plantation->luas_area = round($result[0]->area_hectares, 4); // 4 desimal
            } else {
                $plantation->luas_area = 0;
            }

            // Ekstrak titik tengah untuk latitude dan longitude
            $centroid = \Illuminate\Support\Facades\DB::select("
                SELECT
                    ST_Y(ST_Centroid(ST_GeomFromText(?, 4326))) as latitude,
                    ST_X(ST_Centroid(ST_GeomFromText(?, 4326))) as longitude
            ", [$shapefile->geometry, $shapefile->geometry]);

            if (!empty($centroid) && isset($centroid[0]->latitude) && isset($centroid[0]->longitude)) {
                $plantation->latitude = $centroid[0]->latitude;
                $plantation->longitude = $centroid[0]->longitude;
            }

            // Log data sebelum disimpan
            Log::info('Data plantation yang akan disimpan:', [
                'id' => $plantation->id,
                'shapefile_id' => $plantation->shapefile_id,
                'name' => $plantation->name,
                'luas_area' => $plantation->luas_area,
                'latitude' => $plantation->latitude ?? 'null',
                'longitude' => $plantation->longitude ?? 'null',
                'geometry length' => $plantation->geometry ? strlen($plantation->geometry) : 0
            ]);

            // Simpan plantation
            $plantation->save();

            // Update shapefile dengan plantation_id untuk referensi
            $shapefile->plantation_id = $plantation->id;
            $shapefile->save();

            Log::info('Berhasil import shapefile ke plantation. Plantation ID: ' . $plantation->id);
            return true;
        } catch (\Exception $e) {
            // Log error
            \Illuminate\Support\Facades\Log::error('Error importing shapefile to plantation: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());
            return false;
        }
    }

    /**
     * Mencari ID terkecil yang tersedia untuk Plantation
     */
    private function findSmallestAvailableIdForPlantation()
    {
        try {
            // Dapatkan semua ID yang ada di database
            $existingIds = \App\Models\Plantation::pluck('id')->toArray();

            // Jika tidak ada data sama sekali, mulai dari ID 1
            if (empty($existingIds)) {
                \Illuminate\Support\Facades\Log::info('Tidak ada plantation, mulai dengan ID 1');
                return 1;
            }

            // Dapatkan ID terbesar yang ada di database
            $maxId = max($existingIds);

            // Cari ID terkecil yang tersedia dalam rentang 1 sampai maxId
            for ($i = 1; $i <= $maxId; $i++) {
                if (!in_array($i, $existingIds)) {
                    \Illuminate\Support\Facades\Log::info('Ditemukan ID terkecil yang tersedia untuk plantation: ' . $i);
                    return $i;
                }
            }

            // Jika semua ID dari 1 sampai maxId sudah digunakan,
            // gunakan ID berikutnya (maxId + 1)
            $nextId = $maxId + 1;
            \Illuminate\Support\Facades\Log::info('Tidak ada gap pada plantation, menggunakan ID berikutnya: ' . $nextId);
            return $nextId;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error mencari ID terkecil untuk plantation: ' . $e->getMessage());
            // Default ke ID 1 jika terjadi error
            return 1;
        }
    }

    /**
     * Reset shapefile state to unprocessed
     */
    public function resetShapefile(Shapefile $shapefile)
    {
        try {
            // Reset geometry menjadi null
            $shapefile->geometry = null;

            // Jika plantation_id ada, reset juga
            if ($shapefile->plantation_id) {
                $shapefile->plantation_id = null;
            }

            // Reset kolom processed menjadi false
            $shapefile->processed = false;

            $shapefile->save();

            // Jika ada tree detection terkait, hapus juga
            if ($shapefile->type === 'tree' && DB::getSchemaBuilder()->hasTable('tree_detections')) {
                try {
                    DB::table('tree_detections')
                        ->where('shapefile_id', $shapefile->id)
                        ->delete();
                    Log::info('Tree detection untuk shapefile ID=' . $shapefile->id . ' berhasil dihapus');
                } catch (\Exception $e) {
                    Log::error('Gagal menghapus tree detection terkait: ' . $e->getMessage());
                }
            }

            return redirect()->route('shapefile.index')
                ->with('success', 'Shapefile berhasil direset ke status belum diproses!');
        } catch (\Exception $e) {
            return redirect()->route('shapefile.index')
                ->with('error', 'Terjadi kesalahan saat mereset shapefile: ' . $e->getMessage());
        }
    }

    /**
     * Mencari ID terkecil yang tersedia
     */
    private function findSmallestAvailableId()
    {
        try {
            // Dapatkan semua ID yang ada di database
            $existingIds = Shapefile::pluck('id')->toArray();

            // Jika tidak ada data sama sekali, mulai dari ID 1
            if (empty($existingIds)) {
                Log::info('Tidak ada shapefile, mulai dengan ID 1');
                return 1;
            }

            // Dapatkan ID terbesar yang ada di database
            $maxId = max($existingIds);

            // Cari ID terkecil yang tersedia dalam rentang 1 sampai maxId
            for ($i = 1; $i <= $maxId; $i++) {
                if (!in_array($i, $existingIds)) {
                    Log::info('Ditemukan ID terkecil yang tersedia: ' . $i);
                    return $i;
                }
            }

            // Jika semua ID dari 1 sampai maxId sudah digunakan,
            // gunakan ID berikutnya (maxId + 1)
            $nextId = $maxId + 1;
            Log::info('Tidak ada gap, menggunakan ID berikutnya: ' . $nextId);
            return $nextId;
        } catch (\Exception $e) {
            Log::error('Error mencari ID terkecil: ' . $e->getMessage());
            // Default ke ID 1 jika terjadi error
            return 1;
        }
    }

    /**
     * Mendapatkan daftar plantations berdasarkan aerial photo
     * Perbaikan untuk error data.forEach is not a function
     */
    public function getPlantationsByAerialPhoto($id)
    {
        try {
            \Illuminate\Support\Facades\Log::info('ShapefileController - API dipanggil: plantations-by-aerial-photo/' . $id);

            // Ambil data aerial photo
            $aerialPhoto = AerialPhoto::findOrFail($id);

            // Ambil semua plantation
            $plantations = Plantation::select(
                'id',
                'name',
                'luas_area',
                'geometry'
            )
            ->orderBy('name')
            ->get();

            // Format data untuk response
            $formattedPlantations = [];
            foreach ($plantations as $plantation) {
                // Konversi geometry ke GeoJSON
                $geojson = null;
                if ($plantation->geometry) {
                    try {
                        $result = DB::select("SELECT ST_AsGeoJSON(geometry) as geojson FROM plantations WHERE id = ?", [$plantation->id]);
                        if (!empty($result) && isset($result[0]->geojson)) {
                            $geojson = json_decode($result[0]->geojson);
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Error converting geometry to GeoJSON: ' . $e->getMessage());
                    }
                }

                $formattedPlantations[] = [
                    'id' => $plantation->id,
                    'name' => $plantation->name,
                    'luas_area' => $plantation->luas_area,
                    'geometry' => $geojson
                ];
            }

            \Illuminate\Support\Facades\Log::info('ShapefileController - Mengembalikan data plantation: ' . count($formattedPlantations) . ' items');

            // Selalu kembalikan data dalam format yang konsisten dengan array data
            return response()->json([
                'success' => true,
                'data' => $formattedPlantations
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('ShapefileController - Error fetching plantations: ' . $e->getMessage());

            // Selalu kembalikan array kosong untuk memastikan data.forEach bisa berjalan
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Memeriksa apakah unzip command tersedia di sistem
     */
    private function hasUnzipCommand()
    {
        $unzipCommand = "unzip";

        // Cek di Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $testCommand = "where $unzipCommand";
        } else {
            // Linux, Unix, macOS
            $testCommand = "command -v $unzipCommand";
        }

        // Jalankan perintah dan cek hasilnya
        exec($testCommand, $output, $returnVar);

        return $returnVar === 0 && !empty($output);
    }

    /**
     * Import shapefile data untuk tree
     */
    private function importTreeShapefile($shapefile)
    {
        try {
            // Tambahkan logging detail untuk debugging
            Log::info('Mulai import tree shapefile ID=' . $shapefile->id);

            // Pastikan shapefile sudah memiliki geometry
            if (!$shapefile->geometry) {
                Log::error('Shapefile pohon tidak memiliki data geometri');
                throw new \Exception('Shapefile tidak memiliki data geometri');
            }

            // Pastikan tabel tree_detections tersedia
            if (!DB::getSchemaBuilder()->hasTable('tree_detections')) {
                Log::error('Tabel tree_detections tidak ditemukan');
                throw new \Exception('Tabel tree_detections tidak ditemukan. Jalankan migrasi database terlebih dahulu.');
            }

            // Cari ID terkecil yang tersedia untuk tree detection
            $availableId = $this->findSmallestAvailableIdForTreeDetection();

            // Identifikasi tipe geometri
            try {
                $geometryType = DB::select("SELECT ST_GeometryType(ST_GeomFromText(?, 4326)) as geometry_type", [$shapefile->geometry])[0]->geometry_type;
                Log::info('Tipe geometri shapefile: ' . $geometryType);
            } catch (\Exception $e) {
                Log::error('Gagal mengidentifikasi tipe geometri: ' . $e->getMessage());
                $geometryType = 'UNKNOWN';
            }

            // Konversi ke GeoJSON untuk analisis
            $geojson = null;
            try {
                $result = DB::select("SELECT ST_AsGeoJSON(ST_GeomFromText(?, 4326)) as geojson", [$shapefile->geometry]);
                if (!empty($result) && isset($result[0]->geojson)) {
                    $geojson = json_decode($result[0]->geojson);
                    Log::info('GeoJSON berhasil dibuat untuk analisis');
                }
            } catch (\Exception $e) {
                Log::error('Error converting WKT to GeoJSON: ' . $e->getMessage());
            }

            // Hitung jumlah pohon berdasarkan tipe geometri
            $treeCount = 1; // Default untuk polygon atau multipolygon (satu area kebun)

            // Jika tipe geometri adalah POINT atau MULTIPOINT, hitung jumlah titik
            if (strpos($geometryType, 'POINT') !== false && $geojson) {
                if ($geometryType === 'ST_Point') {
                    $treeCount = 1;
                } else if ($geometryType === 'ST_MultiPoint') {
                    // Hitung jumlah titik dalam multipoint
                    $treeCount = count($geojson->coordinates);
                }
                Log::info('Jumlah pohon dari geometri ' . $geometryType . ': ' . $treeCount);
            }

            // Ekstrak titik tengah untuk latitude dan longitude
            $centroid = DB::select("
                SELECT
                    ST_Y(ST_Centroid(ST_GeomFromText(?, 4326))) as latitude,
                    ST_X(ST_Centroid(ST_GeomFromText(?, 4326))) as longitude
            ", [$shapefile->geometry, $shapefile->geometry]);

            $latitude = $longitude = null;
            if (!empty($centroid) && isset($centroid[0]->latitude) && isset($centroid[0]->longitude)) {
                $latitude = $centroid[0]->latitude;
                $longitude = $centroid[0]->longitude;
                Log::info('Centroid shapefile pohon: ' . $latitude . ', ' . $longitude);
            }

            // Buat record tree detection
            $treeDetection = new \App\Models\TreeDetection();

            // Set ID ke nilai terkecil yang tersedia jika ditemukan
            if ($availableId) {
                $treeDetection->id = $availableId;
            }

            $treeDetection->name = $shapefile->name;
            $treeDetection->shapefile_id = $shapefile->id;
            $treeDetection->user_id = Auth::id() ?: 1;
            $treeDetection->tree_count = $treeCount;
            $treeDetection->geometry = $shapefile->geometry;
            $treeDetection->description = $shapefile->description ?: '';

            // Simpan tree detection
            $treeDetection->save();
            Log::info('Tree detection record berhasil dibuat dengan ID=' . $treeDetection->id);

            return true;
        } catch (\Exception $e) {
            // Log error
            Log::error('Error importing shapefile to tree detection: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Mencari ID terkecil yang tersedia untuk TreeDetection
     */
    private function findSmallestAvailableIdForTreeDetection()
    {
        try {
            // Dapatkan semua ID yang ada di database
            $existingIds = DB::table('tree_detections')->pluck('id')->toArray();

            // Jika tidak ada data sama sekali, mulai dari ID 1
            if (empty($existingIds)) {
                Log::info('Tidak ada tree detection, mulai dengan ID 1');
                return 1;
            }

            // Dapatkan ID terbesar yang ada di database
            $maxId = max($existingIds);

            // Cari ID terkecil yang tersedia dalam rentang 1 sampai maxId
            for ($i = 1; $i <= $maxId; $i++) {
                if (!in_array($i, $existingIds)) {
                    Log::info('Ditemukan ID terkecil yang tersedia untuk tree detection: ' . $i);
                    return $i;
                }
            }

            // Jika semua ID dari 1 sampai maxId sudah digunakan,
            // gunakan ID berikutnya (maxId + 1)
            $nextId = $maxId + 1;
            Log::info('Tidak ada gap pada tree detection, menggunakan ID berikutnya: ' . $nextId);
            return $nextId;
        } catch (\Exception $e) {
            Log::error('Error mencari ID terkecil untuk tree detection: ' . $e->getMessage());
            // Default ke ID 1 jika terjadi error
            return 1;
        }
    }

    /**
     * Ekstraski koordinat dari file-file shapefile dengan presisi penuh
     *
     * @param string $tempDir Direktori tempat file shapefile diekstrak
     * @param string $sessionId ID sesi untuk logging
     * @param array &$debugInfo Array untuk menyimpan info debug
     * @param string &$geometryWkt Variabel untuk menyimpan hasil WKT
     * @return bool True jika berhasil, false jika gagal
     */
    private function extractCoordinatesFromShapefileFiles($tempDir, $sessionId, &$debugInfo, &$geometryWkt)
    {
        try {
            Log::info('[' . $sessionId . '] Mencoba metode ekstraksi koordinat alternatif dengan presisi tinggi');

            // Cari file dengan berbagai ekstensi terkait shapefile
            $shpFiles = glob($tempDir . '/*.shp');
            $dbfFiles = glob($tempDir . '/*.dbf');
            $prjFiles = glob($tempDir . '/*.prj');
            $shxFiles = glob($tempDir . '/*.shx');
            $txtFiles = glob($tempDir . '/*.txt');
            $csvFiles = glob($tempDir . '/*.csv');
            $jsonFiles = glob($tempDir . '/*.json');

            Log::info('[' . $sessionId . '] File terkait: SHP=' . count($shpFiles) . ', DBF=' . count($dbfFiles) .
                ', PRJ=' . count($prjFiles) . ', SHX=' . count($shxFiles) . ', TXT=' . count($txtFiles) .
                ', CSV=' . count($csvFiles) . ', JSON=' . count($jsonFiles));

            // Jika ada SHP file, gunakan metode ekstraksi langsung dengan OGR yang paling handal
            if (!empty($shpFiles)) {
                $shpFile = $shpFiles[0];

                // METODE BARU: Gunakan ogr2ogr untuk langsung mengekstrak ke WKT
                if ($this->commandExists('ogr2ogr')) {
                    Log::info('[' . $sessionId . '] Menggunakan ogr2ogr untuk ekstraksi langsung ke format WKT');

                    $tempWkt = $tempDir . '/temp.wkt';
                    // Format untuk menghasilkan WKT dengan ogr2ogr
                    $cmd = "ogr2ogr -f \"ESRI Shapefile\" -t_srs EPSG:4326 \"$tempDir/reprojected\" \"$shpFile\" && ogrinfo -al -so \"$tempDir/reprojected\" -fid 0 | grep -A 1000 POLYGON";

                    exec($cmd, $output, $retVal);
                    $ogrOutput = implode("\n", $output);

                    // Cari string WKT dalam output
                    if (preg_match('/(POLYGON|MULTIPOLYGON)\s*\(\(.*\)\)/s', $ogrOutput, $matches)) {
                        $wktCandidate = $matches[0];
                        // Bersihkan dari karakter yang tidak perlu
                        $wktCandidate = preg_replace('/\s+/', ' ', $wktCandidate);

                        // Validasi WKT dengan PostGIS
                        try {
                            $isValid = DB::select("SELECT ST_IsValid(ST_GeomFromText(?, 4326)) as is_valid", [$wktCandidate])[0]->is_valid;

                            if ($isValid) {
                                $geometryWkt = $wktCandidate;
                                $debugInfo['geometry_type'] = 'Direct WKT from OGR';
                                Log::info('[' . $sessionId . '] Berhasil mendapatkan WKT langsung dari OGR');
                                return true;
                    }
                } catch (\Exception $e) {
                            Log::warning('[' . $sessionId . '] WKT dari OGR tidak valid: ' . $e->getMessage());
                        }
                    }
                }

                // METODE ALTERNATIVE: Gunakan Python dengan shapely untuk ekstraksi bentuk akurat
                if (($this->commandExists('python') || $this->commandExists('python3')) && !isset($geometryWkt)) {
                    $pythonCmd = $this->commandExists('python3') ? 'python3' : 'python';

                    // Buat skrip python dengan shapely untuk ekstraksi lebih akurat
                    $pyScript = $tempDir . '/extract_shapefile.py';
                    $pyContent = '
import sys
import json
try:
    # Coba gunakan fiona dan shapely untuk ekstraksi presisi tinggi
    import fiona
    from shapely.geometry import shape
    from shapely.wkt import dumps

    shp_file = sys.argv[1]

    # Baca shapefile
    with fiona.open(shp_file) as src:
        # Ambil feature pertama
        for feature in src:
            # Konversi ke shapely geometry
            geom = shape(feature["geometry"])
            # Output as WKT dengan presisi penuh
            print(dumps(geom, rounding_precision=15))
            break
except ImportError:
    # Fallback ke OGR jika fiona/shapely tidak tersedia
    try:
        from osgeo import ogr
        shp_file = sys.argv[1]
        driver = ogr.GetDriverByName("ESRI Shapefile")
        dataSource = driver.Open(shp_file, 0)
        layer = dataSource.GetLayer()
        feature = layer.GetNextFeature()
        geom = feature.GetGeometryRef()
        # Pastikan menggunakan proyeksi 4326
        srs = layer.GetSpatialRef()
        if srs:
            print("SRS: " + str(srs.ExportToWkt()))
        # Ubah ke EPSG:4326 jika perlu
        if srs and not srs.IsGeographic():
            target = ogr.osr.SpatialReference()
            target.ImportFromEPSG(4326)
            transform = ogr.osr.CoordinateTransformation(srs, target)
            geom.Transform(transform)
        # Hasilkan WKT dengan presisi penuh
        wkt = geom.ExportToWkt()
        print(wkt)
    except Exception as e:
        print("ERROR: " + str(e))
        sys.exit(1)
except Exception as e:
    print("ERROR: " + str(e))
    sys.exit(1)
';
                    file_put_contents($pyScript, $pyContent);

                    // Jalankan skrip Python
                    $cmd = "$pythonCmd \"$pyScript\" \"$shpFile\" 2>&1";
                    Log::info('[' . $sessionId . '] Menjalankan skrip Python presisi tinggi: ' . $cmd);

                    exec($cmd, $output, $retVal);
                    $pyOutput = implode("\n", $output);

                    if ($retVal === 0 && !empty($pyOutput) && strpos($pyOutput, 'ERROR:') === false) {
                        // Ambil baris pertama yang berisi WKT (mungkin ada output debug)
                        $lines = explode("\n", $pyOutput);
                        foreach ($lines as $line) {
                            if (strpos($line, 'POLYGON') === 0 || strpos($line, 'MULTIPOLYGON') === 0) {
                                // Temukan WKT yang valid
                                try {
                                    $isValid = DB::select("SELECT ST_IsValid(ST_GeomFromText(?, 4326)) as is_valid", [$line])[0]->is_valid;

                                    if ($isValid) {
                                        $geometryWkt = $line;
                                        $debugInfo['geometry_type'] = 'Python Shapely/OGR high precision';
                                        Log::info('[' . $sessionId . '] Berhasil mengekstrak bentuk dengan Python presisi tinggi');
                            return true;
                                    }
                                } catch (\Exception $e) {
                                    Log::warning('[' . $sessionId . '] WKT dari Python tidak valid: ' . $e->getMessage());
                                }
                            }
                        }
                    }
                }
            }

            // Jika semua metode gagal
            Log::warning('[' . $sessionId . '] Semua metode ekstraksi koordinat presisi penuh gagal');
            return false;
        } catch (\Exception $e) {
            Log::error('[' . $sessionId . '] Error pada extractCoordinatesFromShapefileFiles: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ekstrak geometri pohon dari shapefile untuk membuat records di tabel trees
     *
     * @param Shapefile $shapefile Shapefile dengan tipe 'tree'
     * @return bool True jika berhasil, false jika gagal
     */
    private function extractTreesFromShapefile(Shapefile $shapefile)
    {
        try {
            // Pastikan shapefile bertype tree dan memiliki geometri
            if ($shapefile->type !== 'tree' || !$shapefile->geometry) {
                Log::error('[TREE IMPORT] Tidak dapat mengekstrak pohon: shapefile bukan tipe tree atau tidak memiliki geometri');
                return false;
            }

            // HAPUS SEMUA POHON LAMA YANG TERKAIT DENGAN SHAPEFILE INI
            // Log::info('[TREE IMPORT] Menghapus semua pohon lama untuk shapefile ID=' . $shapefile->id . ' sebelum ekstraksi baru.');
            // DB::table('trees')->where('shapefile_id', $shapefile->id)->delete(); // <-- BARIS INI AKAN DIHAPUS

            // Cek apakah tabel trees ada
            if (!DB::getSchemaBuilder()->hasTable('trees')) {
                Log::error('[TREE IMPORT] Tabel trees tidak ditemukan! Membuat tabel...');

                // Jika tabel tidak ada, coba buat tabel trees
                try {
                    DB::statement('
                        CREATE TABLE IF NOT EXISTS trees (
                            id VARCHAR(50) PRIMARY KEY,
                            shapefile_id BIGINT,
                            polygon_index INT,
                            plantation_id BIGINT NULL,
                            varietas VARCHAR(100) DEFAULT \'Belum ditentukan\',
                            tahun_tanam YEAR DEFAULT NULL,
                            canopy_geometry TEXT,
                            longitude DECIMAL(11, 8),
                            latitude DECIMAL(10, 8),
                            health_status VARCHAR(20) DEFAULT \'Sehat\',
                            fase VARCHAR(20) DEFAULT \'Vegetatif\',
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            sumber_bibit VARCHAR(100) DEFAULT NULL,
                            digitasi_id BIGINT DEFAULT NULL
                        )
                    ');
                    Log::info('[TREE IMPORT] Tabel trees berhasil dibuat');
                } catch (\Exception $e) {
                    Log::error('[TREE IMPORT] Gagal membuat tabel trees: ' . $e->getMessage());
                    return false;
                }
            }

            // Cek tipe geometri yang dimiliki shapefile
            $geometryType = DB::select("SELECT ST_GeometryType(ST_GeomFromText(?, 4326)) as geometry_type", [$shapefile->geometry])[0]->geometry_type;
            Log::info('[TREE IMPORT] Tipe geometri shapefile pohon: ' . $geometryType);

            $createdTrees = 0;

            // Tambahkan debugging untuk melihat WKT asli
            Log::info('[TREE IMPORT] WKT geometry asli: ' . substr($shapefile->geometry, 0, 100) . '...');

            // Coba baca data atribut dari file DBF jika ada
            Log::info('[TREE IMPORT] Membaca data atribut dari file DBF...');
            $dbfData = $this->readDbfDataFromShapefile($shapefile);

            if ($dbfData) {
                Log::info('[TREE IMPORT] Berhasil membaca data DBF dengan ' . count($dbfData) . ' records');
                Log::info('[TREE IMPORT] Contoh data DBF pertama: ' . json_encode(array_slice($dbfData, 0, 1)));

                // Mencari kolom-kolom penting dalam data DBF
                $firstRecord = $dbfData[0] ?? [];
                $availableColumns = array_keys($firstRecord);

                Log::info('[TREE IMPORT] Kolom yang tersedia dalam DBF: ' . json_encode($availableColumns));
            } else {
                Log::info('[TREE IMPORT] Tidak ada data DBF yang tersedia, menggunakan ID dan atribut default');
            }

            // Jika tipe geometri MultiPolygon, kita perlu ekstrak setiap polygon
            if ($geometryType === 'ST_MultiPolygon') {
                // Konversi MultiPolygon ke GeoJSON
                $geojson = DB::select("SELECT ST_AsGeoJSON(ST_GeomFromText(?, 4326)) as geojson", [$shapefile->geometry])[0]->geojson;
                $geojsonObj = json_decode($geojson);

                Log::info('[TREE IMPORT] MultiPolygon GeoJSON: ' . substr($geojson, 0, 200) . '...');
                Log::info('[TREE IMPORT] Jumlah polygon dalam MultiPolygon: ' . (isset($geojsonObj->coordinates) ? count($geojsonObj->coordinates) : 0));

                // Pastikan kita bekerja dengan MultiPolygon
                if ($geojsonObj && isset($geojsonObj->type) && $geojsonObj->type === 'MultiPolygon') {
                    // Proses setiap polygon
                    foreach ($geojsonObj->coordinates as $polygonIndex => $polygonCoords) {
                        Log::info('[TREE IMPORT] Memproses polygon #' . $polygonIndex);

                        // Buat polygon WKT untuk setiap polygon dalam MultiPolygon
                        $polygonWkt = $this->convertPolygonCoordsToWkt($polygonCoords);

                        Log::info('[TREE IMPORT] Processed polygon WKT: ' . substr($polygonWkt, 0, 100) . '...');

                        // Ambil atribut dari DBF jika tersedia untuk polygon ini
                        $attributes = null;
                        if ($dbfData && isset($dbfData[$polygonIndex])) {
                            $attributes = $dbfData[$polygonIndex];
                            Log::info('[TREE IMPORT] Menggunakan atribut dari DBF untuk polygon #' . $polygonIndex);
                        }

                        // Buat record tree baru
                        $tree = $this->createTreeFromPolygon($shapefile, $polygonWkt, $polygonIndex, $attributes);
                        if ($tree) {
                            $createdTrees++;
                            Log::info('[TREE IMPORT] Tree record #' . $createdTrees . ' berhasil dibuat dengan ID: ' . $tree->id);
                        }
                    }
                } else {
                    Log::error('[TREE IMPORT] Format GeoJSON tidak valid atau bukan MultiPolygon');
                }
            }
            // Jika tipe geometri Polygon tunggal
            else if ($geometryType === 'ST_Polygon') {
                // Langsung buat tree dari polygon tunggal
                Log::info('[TREE IMPORT] Memproses polygon tunggal');

                // Ambil atribut dari DBF jika tersedia untuk polygon tunggal
                $attributes = null;
                if ($dbfData && isset($dbfData[0])) {
                    $attributes = $dbfData[0];
                    Log::info('[TREE IMPORT] Menggunakan atribut dari DBF untuk polygon tunggal: ' . json_encode($attributes));
                }

                $tree = $this->createTreeFromPolygon($shapefile, $shapefile->geometry, 0, $attributes);
                if ($tree) {
                    $createdTrees++;
                    Log::info('[TREE IMPORT] Tree record berhasil dibuat dari polygon tunggal dengan ID: ' . $tree->id);
                }
            }
            // Jika tipe geometri Point
            else if ($geometryType === 'ST_Point') {
                // Buat buffer polygon dari point untuk mendapatkan representasi canopy
                Log::info('[TREE IMPORT] Memproses point sebagai pohon');
                $pointWkt = $shapefile->geometry;
                $polygonWkt = DB::select("SELECT ST_AsText(ST_Buffer(ST_GeomFromText(?, 4326)::geography, 5)::geometry) as wkt",
                                         [$pointWkt])[0]->wkt;

                // Ambil atribut dari DBF jika tersedia untuk point
                $attributes = null;
                if ($dbfData && isset($dbfData[0])) {
                    $attributes = $dbfData[0];
                    Log::info('[TREE IMPORT] Menggunakan atribut dari DBF untuk point: ' . json_encode($attributes));
                }

                Log::info('[TREE IMPORT] Point buffer polygon WKT: ' . substr($polygonWkt, 0, 100) . '...');

                // Buat record tree baru
                $tree = $this->createTreeFromPolygon($shapefile, $polygonWkt, 0, $attributes);
                if ($tree) {
                    $createdTrees++;
                    Log::info('[TREE IMPORT] Tree record berhasil dibuat dari point dengan ID: ' . $tree->id);
                }
            }
            // MultiPoint - buat tree untuk setiap point
            else if ($geometryType === 'ST_MultiPoint') {
                Log::info('[TREE IMPORT] Memproses multipoint sebagai kumpulan pohon');
                // Konversi MultiPoint ke GeoJSON
                $geojson = DB::select("SELECT ST_AsGeoJSON(ST_GeomFromText(?, 4326)) as geojson", [$shapefile->geometry])[0]->geojson;
                $geojsonObj = json_decode($geojson);

                Log::info('[TREE IMPORT] MultiPoint GeoJSON: ' . substr($geojson, 0, 200) . '...');
                Log::info('[TREE IMPORT] Jumlah point dalam MultiPoint: ' . (isset($geojsonObj->coordinates) ? count($geojsonObj->coordinates) : 0));

                if ($geojsonObj && isset($geojsonObj->type) && $geojsonObj->type === 'MultiPoint') {
                    // Proses setiap point - buat polygon kecil untuk setiap point
                    foreach ($geojsonObj->coordinates as $pointIndex => $pointCoords) {
                        Log::info('[TREE IMPORT] Memproses point #' . $pointIndex);

                        // Buat polygon buffer dari point (5m buffer untuk ukuran canopy)
                        $pointWkt = "POINT({$pointCoords[0]} {$pointCoords[1]})";
                        $polygonWkt = DB::select("SELECT ST_AsText(ST_Buffer(ST_GeomFromText(?, 4326)::geography, 5)::geometry) as wkt",
                                                [$pointWkt])[0]->wkt;

                        // Ambil atribut dari DBF jika tersedia untuk point ini
                        $attributes = null;
                        if ($dbfData && isset($dbfData[$pointIndex])) {
                            $attributes = $dbfData[$pointIndex];
                            Log::info('[TREE IMPORT] Menggunakan atribut dari DBF untuk point #' . $pointIndex);
                        }

                        Log::info('[TREE IMPORT] Point buffer polygon WKT: ' . substr($polygonWkt, 0, 100) . '...');

                        // Buat record tree baru
                        $tree = $this->createTreeFromPolygon($shapefile, $polygonWkt, $pointIndex, $attributes);
                        if ($tree) {
                            $createdTrees++;
                            Log::info('[TREE IMPORT] Tree record #' . $createdTrees . ' berhasil dibuat dengan ID: ' . $tree->id);
                        }
                    }
                } else {
                    Log::error('[TREE IMPORT] Format GeoJSON tidak valid atau bukan MultiPoint');
                }
            }
            // LineString - mungkin baris pohon, konversi ke polygon dengan buffer
            else if ($geometryType === 'ST_LineString' || $geometryType === 'ST_MultiLineString') {
                Log::info('[TREE IMPORT] Memproses linestring sebagai baris pohon');
                // Konversi ke polygon dengan buffer
                $polygonWkt = DB::select("SELECT ST_AsText(ST_Buffer(ST_GeomFromText(?, 4326)::geography, 5)::geometry) as wkt",
                                         [$shapefile->geometry])[0]->wkt;

                // Ambil atribut dari DBF jika tersedia
                $attributes = null;
                if ($dbfData && isset($dbfData[0])) {
                    $attributes = $dbfData[0];
                    Log::info('[TREE IMPORT] Menggunakan atribut dari DBF untuk linestring: ' . json_encode($attributes));
                }

                Log::info('[TREE IMPORT] LineString buffer polygon WKT: ' . substr($polygonWkt, 0, 100) . '...');

                $tree = $this->createTreeFromPolygon($shapefile, $polygonWkt, 0, $attributes);
                if ($tree) {
                    $createdTrees++;
                    Log::info('[TREE IMPORT] Tree record berhasil dibuat dari linestring dengan ID: ' . $tree->id);
                }
            }
            // Default fallback untuk tipe lain
            else {
                Log::warning('[TREE IMPORT] Tipe geometri tidak didukung: ' . $geometryType . '. Mencoba konversi ke polygon.');

                // Coba konversi ke polygon apapun tipe geometrinya
                try {
                    $polygonWkt = DB::select("SELECT ST_AsText(ST_Buffer(ST_GeomFromText(?, 4326)::geography, 5)::geometry) as wkt",
                                             [$shapefile->geometry])[0]->wkt;

                    // Ambil atribut dari DBF jika tersedia
                    $attributes = null;
                    if ($dbfData && isset($dbfData[0])) {
                        $attributes = $dbfData[0];
                        Log::info('[TREE IMPORT] Menggunakan atribut dari DBF untuk geometri default: ' . json_encode($attributes));
                    }

                    Log::info('[TREE IMPORT] Fallback polygon WKT: ' . substr($polygonWkt, 0, 100) . '...');

                    $tree = $this->createTreeFromPolygon($shapefile, $polygonWkt, 0, $attributes);
                    if ($tree) {
                        $createdTrees++;
                        Log::info('[TREE IMPORT] Tree record berhasil dibuat dari geometri default dengan ID: ' . $tree->id);
                    }
                } catch (\Exception $e) {
                    Log::error('[TREE IMPORT] Gagal mengkonversi geometri ke polygon: ' . $e->getMessage());
                }
            }

            Log::info('[TREE IMPORT] Berhasil membuat ' . $createdTrees . ' records pohon dari shapefile ID=' . $shapefile->id);

            // Update metadata tree di shapefile dan tandai sebagai processed
            try {
                $shapefile->tree_count = $createdTrees;
                $shapefile->processed = true;
                $shapefile->save();
                Log::info('[TREE IMPORT] Metadata tree count berhasil diupdate di shapefile: ' . $createdTrees);
            } catch (\Exception $e) {
                Log::error('[TREE IMPORT] Gagal update metadata tree count: ' . $e->getMessage());
            }

            return $createdTrees > 0;

        } catch (\Exception $e) {
            Log::error('[TREE IMPORT] Error extracting trees from shapefile: ' . $e->getMessage());
            Log::error('[TREE IMPORT] Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Konversi koordinat polygon dalam format GeoJSON ke WKT
     *
     * @param array $polygonCoords Koordinat polygon dari GeoJSON
     * @return string WKT polygon
     */
    private function convertPolygonCoordsToWkt($polygonCoords)
    {
        try {
        // Format koordinat untuk WKT
        $rings = [];
        foreach ($polygonCoords as $ring) {
            $points = [];
            foreach ($ring as $point) {
                    if (is_array($point) && count($point) >= 2) {
                $points[] = $point[0] . ' ' . $point[1];
                    } else {
                        Log::warning('[TREE IMPORT] Format koordinat tidak valid: ' . json_encode($point));
                    }
                }

                // Pastikan polygon tertutup (titik pertama = titik terakhir)
                if (count($points) > 0 && $points[0] !== $points[count($points) - 1]) {
                    $points[] = $points[0]; // Tambahkan titik pertama di akhir
                }

                // Pastikan ada minimal 4 titik untuk membentuk polygon tertutup
                if (count($points) >= 4) {
            $rings[] = '(' . implode(',', $points) . ')';
                } else {
                    Log::warning('[TREE IMPORT] Ring polygon memiliki terlalu sedikit titik (' . count($points) . '): ' . implode(',', $points));
                }
        }

            if (count($rings) > 0) {
        return 'POLYGON(' . implode(',', $rings) . ')';
            } else {
                throw new \Exception('Tidak ada ring polygon yang valid');
            }
        } catch (\Exception $e) {
            Log::error('[TREE IMPORT] Error saat mengkonversi koordinat polygon: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Buat record tree dari polygon yang diekstrak
     *
     * @param Shapefile $shapefile Shapefile sumber
     * @param string $polygonWkt Polygon dalam format WKT
     * @param int $index Indeks polygon dalam shapefile
     * @param array|null $attributes Atribut dari file DBF jika tersedia
     * @return object|null Record tree yang dibuat atau null jika gagal
     */
    private function createTreeFromPolygon(Shapefile $shapefile, $polygonWkt, $index, $attributes = null)
    {
        try {
            // Gunakan ID dari atribut DBF jika tersedia, jika tidak buat ID default
            $treeId = null;
            $varietas = 'Belum ditentukan';
            $tahunTanam = date('Y');
            $healthStatus = 'Sehat';
            $fase = 'Vegetatif';
            $sumberBibit = null;

            // Flags untuk menandai apakah atribut diatur dari KML/DBF
            $varietasSetFromKml = false;
            $tahunTanamSetFromKml = false;
            $healthStatusSetFromKml = false;
            $faseSetFromKml = false;
            $sumberBibitSetFromKml = false;

            // Ekstrak atribut dari DBF jika tersedia
            if ($attributes) {
                // Cari field ID atau OBJECTID atau FID dalam atribut
                foreach (['ID', 'OBJECTID', 'FID', 'GID', 'OID', 'id', 'objectid', 'fid', 'gid', 'oid'] as $idField) {
                    if (isset($attributes[$idField]) && !empty($attributes[$idField])) {
                        $treeId = 'T' . preg_replace('/[^a-zA-Z0-9]/', '', $attributes[$idField]);
                        Log::info('[TREE IMPORT] Menggunakan ID dari DBF: ' . $idField . '=' . $attributes[$idField]);
                        break;
                    }
                }

                // Cari field untuk varietas/jenis pohon
                foreach (['VARIETAS', 'JENIS', 'SPECIES', 'TYPE', 'VARIETY', 'NAME', 'varietas', 'jenis', 'species', 'type', 'variety', 'name'] as $field) {
                    if (isset($attributes[$field]) && !empty($attributes[$field])) {
                        $varietas = $attributes[$field];
                        $varietasSetFromKml = true;
                        Log::info('[TREE IMPORT] Menggunakan varietas dari field: ' . $field . '=' . $varietas);
                        break;
                    }
                }

                // Cari field untuk tahun tanam
                foreach (['TAHUN', 'YEAR', 'PLANT_YEAR', 'PLANTING_YR', 'TANAM', 'tahun', 'year', 'plant_year', 'planting_yr', 'tanam'] as $field) {
                    if (isset($attributes[$field]) && !empty($attributes[$field])) {
                        $tahunValue = $attributes[$field];
                        // Pastikan tahun valid
                        if (is_numeric($tahunValue) && $tahunValue > 1900 && $tahunValue <= date('Y')) {
                            $tahunTanam = $tahunValue;
                            $tahunTanamSetFromKml = true;
                            Log::info('[TREE IMPORT] Menggunakan tahun tanam dari field: ' . $field . '=' . $tahunTanam);
                        } else {
                            Log::warning('[TREE IMPORT] Nilai tahun tanam tidak valid dari field: ' . $field . '=' . $tahunValue . '. Menggunakan default.');
                        }
                        break; // Hanya proses field pertama yang cocok
                    }
                }

                // Cari field untuk status kesehatan
                foreach (['HEALTH', 'STATUS', 'CONDITION', 'KESEHATAN', 'health', 'status', 'condition', 'kesehatan'] as $field) {
                    if (isset($attributes[$field]) && !empty($attributes[$field])) {
                        $healthStatus = $attributes[$field]; // Akan dinormalisasi nanti
                        $healthStatusSetFromKml = true;
                        Log::info('[TREE IMPORT] Menggunakan health status dari field: ' . $field . '=' . $healthStatus);
                        break;
                    }
                }

                // Cari field untuk fase pertumbuhan
                foreach (['FASE', 'PHASE', 'GROWTH', 'STAGE', 'fase', 'phase', 'growth', 'stage'] as $field) {
                    if (isset($attributes[$field]) && !empty($attributes[$field])) {
                        $fase = $attributes[$field]; // Akan dinormalisasi nanti
                        $faseSetFromKml = true;
                        Log::info('[TREE IMPORT] Menggunakan fase dari field: ' . $field . '=' . $fase);
                        break;
                    }
                }

                // Cari field untuk sumber bibit
                foreach (['SUMBER_BIBIT', 'BIBIT', 'SOURCE', 'SEED_SOURCE', 'SUPPLIER', 'sumber_bibit', 'bibit', 'source', 'seed_source', 'supplier'] as $field) {
                    if (isset($attributes[$field]) && !empty($attributes[$field])) {
                        $sumberBibit = $attributes[$field];
                        $sumberBibitSetFromKml = true;
                        Log::info('[TREE IMPORT] Menggunakan sumber bibit dari field: ' . $field . '=' . $sumberBibit);
                        break;
                    }
                }
            }

            // Normalisasi nilai setelah diekstrak (jika diatur dari KML) atau dari default
            $healthStatus = $this->normalizeHealthStatus($healthStatus);
            $fase = $this->normalizeFase($fase);

            // Jika tidak mendapatkan ID dari atribut, gunakan ID default
            if (!$treeId) {
                $treeId = 'SF' . $shapefile->id . 'P' . ($index + 1);
            }

            // Pastikan ID di-trim dan bersih
            $treeId = trim($treeId);

            // DEBUGGING KHUSUS untuk ID yang bermasalah (misal '1P' atau yang muncul di error)
            // Ganti '1P' dengan ID aktual yang menyebabkan error jika berbeda
            $problematicIdForDebug = '1P'; // Ambil dari pesan error terakhir Anda
            if ($treeId === $problematicIdForDebug) {
                Log::info('[TREE IMPORT DEBUG]', [
                    'message' => 'Processing tree with problematic ID',
                    'treeId_determined' => $treeId,
                    'shapefile_id' => $shapefile->id,
                    'polygon_index' => $index,
                    'attributes_from_kml' => $attributes
                ]);
            }

            // Validasi geometri WKT dengan PostGIS sebelum memproses lebih lanjut
            $isValid = DB::select("SELECT ST_IsValid(ST_GeomFromText(?, 4326)) as is_valid", [$polygonWkt])[0]->is_valid;

            if (!$isValid) {
                Log::warning('[TREE IMPORT] Geometri WKT tidak valid: ' . substr($polygonWkt, 0, 50) . '... Mencoba memperbaiki.');
                $polygonWkt = DB::select("SELECT ST_AsText(ST_MakeValid(ST_GeomFromText(?, 4326))) as wkt", [$polygonWkt])[0]->wkt;

                // Cek lagi apakah sudah valid
                $isValid = DB::select("SELECT ST_IsValid(ST_GeomFromText(?, 4326)) as is_valid", [$polygonWkt])[0]->is_valid;
                if (!$isValid) {
                    Log::error('[TREE IMPORT] Gagal memperbaiki geometri WKT: ' . substr($polygonWkt, 0, 50) . '...');
                    return null;
                } else {
                    Log::info('[TREE IMPORT] Geometri berhasil diperbaiki.');
                }
            }

            // Dapatkan koordinat centroid
            $centroid = DB::select("
                SELECT
                    ST_Y(ST_Centroid(ST_GeomFromText(?, 4326))) as latitude,
                    ST_X(ST_Centroid(ST_GeomFromText(?, 4326))) as longitude
            ", [$polygonWkt, $polygonWkt]);

            $latitude = $centroid[0]->latitude ?? null;
            $longitude = $centroid[0]->longitude ?? null;

            Log::info('[TREE IMPORT] Tree centroid: Lat=' . $latitude . ', Lng=' . $longitude);

            // Cek apakah pohon dengan ID ini sudah ada
            $existingTree = DB::table('trees')->where('id', $treeId)->first();

            if ($treeId === $problematicIdForDebug) {
                Log::info('[TREE IMPORT DEBUG]', [
                    'message' => 'After checking DB for existing tree',
                    'treeId_queried' => $treeId,
                    'existingTree_found' => $existingTree ? 'YES' : 'NO',
                    'existingTree_data' => $existingTree ? json_encode($existingTree) : null
                ]);
            }

            if ($existingTree) {
                // Jika sudah ada, update dengan data baru secara selektif
                try {
                    $updateData = [
                        'shapefile_id' => $shapefile->id,
                        'polygon_index' => $index,
                        'canopy_geometry' => $polygonWkt,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'plantation_id' => $shapefile->plantation_id, // Pastikan ini sesuai
                        'updated_at' => now()
                    ];

                    if ($varietasSetFromKml) {
                        $updateData['varietas'] = $varietas;
                    }
                    if ($tahunTanamSetFromKml) {
                        $updateData['tahun_tanam'] = $tahunTanam;
                    }
                    if ($healthStatusSetFromKml) {
                        $updateData['health_status'] = $healthStatus; // Sudah dinormalisasi
                    }
                    if ($faseSetFromKml) {
                        $updateData['fase'] = $fase; // Sudah dinormalisasi
                    }
                    if ($sumberBibitSetFromKml) {
                        $updateData['sumber_bibit'] = $sumberBibit;
                    }

                    DB::table('trees')->where('id', $treeId)->update($updateData);

                    Log::info('[TREE IMPORT] Updated existing tree: ' . $treeId . ' with selective attributes. Fields updated: ' . json_encode(array_keys($updateData)));
                } catch (\Exception $e) {
                    Log::error('[TREE IMPORT] Error updating tree: ' . $e->getMessage());
                }
                return (object)['id' => $treeId]; // Return object dengan minimal property id
            }

            // Insert langsung ke database untuk menghindari masalah dengan model
            try {
                $insertResult = DB::insert('
                    INSERT INTO trees (
                        id, shapefile_id, polygon_index, canopy_geometry, latitude, longitude,
                        varietas, tahun_tanam, health_status, fase, plantation_id, created_at, updated_at,
                        sumber_bibit
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?
                    )
                ', [
                    $treeId,
                    $shapefile->id,
                    $index,
                    $polygonWkt,
                    $latitude,
                    $longitude,
                    $varietas,
                    $tahunTanam,
                    $healthStatus,
                    $fase,
                    $shapefile->plantation_id,
                    $sumberBibit
                ]);

                // Log hasil insert
                if ($insertResult) {
                    Log::info('[TREE IMPORT] Created new tree with direct DB insert: ' . $treeId);
                    return (object)['id' => $treeId]; // Return object dengan minimal property id
                } else {
                    Log::error('[TREE IMPORT] Failed to insert tree into database: ' . $treeId);
                    return null;
                }
            } catch (\Exception $e) {
                Log::error('[TREE IMPORT] Error inserting tree into database: ' . $e->getMessage());

                // Tampilkan detail query untuk troubleshooting
                Log::error('[TREE IMPORT] Insert error detail: ID=' . $treeId .
                          ', ShapefileID=' . $shapefile->id .
                          ', PlantationID=' . ($shapefile->plantation_id ?? 'NULL') .
                          ', Lat=' . $latitude .
                          ', Long=' . $longitude);

                return null;
            }

        } catch (\Exception $e) {
            Log::error('[TREE IMPORT] Error membuat tree dari polygon: ' . $e->getMessage());
            Log::error('[TREE IMPORT] Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Membaca data atribut dari file DBF shapefile
     *
     * @param Shapefile $shapefile Shapefile yang akan dibaca atributnya
     * @return array|null Array data atribut dari DBF atau null jika gagal
     */
    private function readDbfDataFromShapefile(Shapefile $shapefile)
    {
        try {
            Log::info('[TREE IMPORT] Mencoba membaca data atribut DBF untuk shapefile ID=' . $shapefile->id);

            // Periksa apakah file shapefile ada di storage
            if (!$shapefile->file_path || !Storage::disk('public')->exists($shapefile->file_path)) {
                Log::warning('[TREE IMPORT] File shapefile tidak ditemukan di storage');
                return null;
            }

            // Dapatkan path file asli
            $filePath = Storage::disk('public')->path($shapefile->file_path);
            $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

            if (strtolower($fileExtension) !== 'zip') {
                Log::warning('[TREE IMPORT] File bukan ZIP, tidak dapat mengekstrak DBF');
                return null;
            }

            // Ekstrak file ZIP ke direktori sementara
            $tempDir = storage_path('app/temp_dbf_' . uniqid());
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Ekstrak file ZIP
            $extractSuccess = false;

            try {
                // Gunakan ZipArchive jika tersedia
                if (extension_loaded('zip')) {
                    $zip = new \ZipArchive();
                    if ($zip->open($filePath) === TRUE) {
                        $zip->extractTo($tempDir);
                        $zip->close();
                        $extractSuccess = true;
                        Log::info('[TREE IMPORT] Ekstraksi berhasil dengan ZipArchive');
                    }
                }

                // Coba dengan unzip command jika ZipArchive gagal
                if (!$extractSuccess) {
                    $unzipCmd = "unzip -o \"$filePath\" -d \"$tempDir\"";
                    exec($unzipCmd, $output, $retVal);

                    if ($retVal === 0 || $retVal === 1) {
                        $extractSuccess = true;
                        Log::info('[TREE IMPORT] Ekstraksi berhasil dengan unzip command');
                    }
                }

                // Coba dengan PowerShell jika unzip gagal
                if (!$extractSuccess && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    $psCmd = "powershell.exe -Command \"& {Expand-Archive -LiteralPath '$filePath' -DestinationPath '$tempDir' -Force}\"";
                    exec($psCmd, $output, $retVal);

                    if ($retVal === 0) {
                        $extractSuccess = true;
                        Log::info('[TREE IMPORT] Ekstraksi berhasil dengan PowerShell');
                    }
                }
            } catch (\Exception $e) {
                Log::error('[TREE IMPORT] Error saat mengekstrak ZIP: ' . $e->getMessage());
            }

            if (!$extractSuccess) {
                Log::error('[TREE IMPORT] Semua metode ekstraksi ZIP gagal');
                return null;
            }

            // Cari file DBF di direktori hasil ekstraksi
            $dbfFiles = glob($tempDir . '/*.dbf');

            // Jika tidak menemukan di direktori utama, cari di subdirektori
            if (empty($dbfFiles)) {
                $subdirs = glob($tempDir . '/*', GLOB_ONLYDIR);
                foreach ($subdirs as $subdir) {
                    $subDbfFiles = glob($subdir . '/*.dbf');
                    if (!empty($subDbfFiles)) {
                        $dbfFiles = $subDbfFiles;
                        break;
                    }
                }
            }

            if (empty($dbfFiles)) {
                Log::warning('[TREE IMPORT] Tidak menemukan file DBF dalam ZIP');
                $this->removeDirectory($tempDir);
                return null;
            }

            $dbfFile = $dbfFiles[0];
            Log::info('[TREE IMPORT] File DBF ditemukan: ' . $dbfFile);

            // Coba baca file DBF dengan dbase extension jika tersedia
            $dbfData = [];

            if (extension_loaded('dbase')) {
                // Buka file DBF
                $dbfHandle = dbase_open($dbfFile, 0);

                if ($dbfHandle) {
                    $recordCount = dbase_numrecords($dbfHandle);
                    Log::info('[TREE IMPORT] Jumlah record dalam DBF: ' . $recordCount);

                    // Baca semua record
                    for ($i = 1; $i <= $recordCount; $i++) {
                        $record = dbase_get_record_with_names($dbfHandle, $i);

                        // Bersihkan karakter tidak perlu dan konversi ke UTF-8
                        $cleanRecord = [];
                        foreach ($record as $key => $value) {
                            if ($key === 'deleted') continue;

                            // Bersihkan whitespace
                            if (is_string($value)) {
                                $value = trim($value);

                                // Konversi ke UTF-8 jika perlu
                                if (!mb_check_encoding($value, 'UTF-8')) {
                                    $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
                                }
                            }

                            $cleanRecord[$key] = $value;
                        }

                        $dbfData[] = $cleanRecord;
                    }

                    dbase_close($dbfHandle);
                    Log::info('[TREE IMPORT] Berhasil membaca ' . count($dbfData) . ' records dari DBF');
                } else {
                    Log::warning('[TREE IMPORT] Gagal membuka file DBF dengan dbase extension');
                }
            }
            // Alternatif jika dbase extension tidak tersedia
            else {
                Log::warning('[TREE IMPORT] dbase extension tidak tersedia, mencoba alternatif');

                // Coba gunakan python jika tersedia
                $pythonCommand = $this->commandExists('python3') ? 'python3' : ($this->commandExists('python') ? 'python' : null);

                if ($pythonCommand) {
                    $pythonScript = $tempDir . '/read_dbf.py';
                    $outputJson = $tempDir . '/dbf_data.json';

                    // Buat script python untuk membaca DBF
                    $script = <<<PYTHON
import sys
import json
import os

try:
    import pandas as pd
    dbf_file = sys.argv[1]
    output_file = sys.argv[2]

    # Baca DBF menggunakan pandas
    df = pd.read_dbf(dbf_file)

    # Konversi ke JSON
    df.to_json(output_file, orient='records')
    print(f"Success: Converted {len(df)} records to JSON")
except ImportError:
    print("Error: pandas module not available")
    sys.exit(1)
except Exception as e:
    print(f"Error: {e}")
    sys.exit(1)
PYTHON;

                    file_put_contents($pythonScript, $script);

                    // Jalankan script
                    $cmd = "$pythonCommand \"$pythonScript\" \"$dbfFile\" \"$outputJson\"";
                    exec($cmd, $output, $retVal);

                    // Baca hasil jika berhasil
                    if ($retVal === 0 && file_exists($outputJson)) {
                        $jsonData = file_get_contents($outputJson);
                        $dbfData = json_decode($jsonData, true);

                        if ($dbfData) {
                            Log::info('[TREE IMPORT] Berhasil membaca ' . count($dbfData) . ' records dari DBF menggunakan Python');
                        } else {
                            Log::warning('[TREE IMPORT] JSON data kosong atau tidak valid');
                        }
                    } else {
                        Log::warning('[TREE IMPORT] Python gagal membaca DBF: ' . implode("\n", $output));
                    }
                } else {
                    Log::warning('[TREE IMPORT] Python tidak tersedia untuk membaca DBF');
                }
            }

            // Bersihkan direktori temp
            $this->removeDirectory($tempDir);

            return count($dbfData) > 0 ? $dbfData : null;

        } catch (\Exception $e) {
            Log::error('[TREE IMPORT] Error saat membaca data DBF: ' . $e->getMessage());
            Log::error('[TREE IMPORT] Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Cek apakah suatu command tersedia di sistem
     *
     * @param string $command Nama command yang akan dicek
     * @return bool True jika command tersedia, false jika tidak
     */
    private function commandExists($command)
    {
        $testCmd = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? "where $command" : "which $command";
        exec($testCmd, $output, $returnVar);
        return $returnVar === 0 && !empty($output);
    }

    /**
     * Normalisasi nilai health_status untuk sesuai dengan enum di database
     *
     * @param string $status Status kesehatan dari DBF
     * @return string Status kesehatan yang sudah dinormalisasi
     */
    private function normalizeHealthStatus($status)
    {
        $status = trim(strtolower($status));

        // Map nilai umum ke nilai yang valid dalam enum database
        $mapping = [
            // Nilai Sehat
            'sehat' => 'Sehat',
            'healthy' => 'Sehat',
            'good' => 'Sehat',
            'baik' => 'Sehat',
            'normal' => 'Sehat',

            // Nilai Stres
            'stres' => 'Stres',
            'stress' => 'Stres',
            'stres ringan' => 'Stres',
            'kurang sehat' => 'Stres',
            'moderate' => 'Stres',

            // Nilai Sakit
            'sakit' => 'Sakit',
            'sick' => 'Sakit',
            'diseased' => 'Sakit',
            'penyakit' => 'Sakit',
            'poor' => 'Sakit',
            'buruk' => 'Sakit',

            // Nilai Mati
            'mati' => 'Mati',
            'dead' => 'Mati',
            'meninggal' => 'Mati',
            'rusak' => 'Mati'
        ];

        if (isset($mapping[$status])) {
            return $mapping[$status];
        }

        // Default jika tidak ada nilai yang cocok
        return 'Sehat';
    }

    /**
     * Normalisasi nilai fase untuk sesuai dengan enum di database
     *
     * @param string $fase Fase pertumbuhan dari DBF
     * @return string Fase yang sudah dinormalisasi
     */
    private function normalizeFase($fase)
    {
        $fase = trim(strtolower($fase));

        // Map nilai umum ke nilai yang valid dalam enum database
        $mapping = [
            // Nilai Generatif
            'generatif' => 'Generatif',
            'generative' => 'Generatif',
            'berbuah' => 'Generatif',
            'reproduktif' => 'Generatif',
            'reproductive' => 'Generatif',
            'g' => 'Generatif',
            'fruit' => 'Generatif',
            'flower' => 'Generatif',
            'bunga' => 'Generatif',
            'buah' => 'Generatif',

            // Nilai Vegetatif
            'vegetatif' => 'Vegetatif',
            'vegetative' => 'Vegetatif',
            'growth' => 'Vegetatif',
            'tumbuh' => 'Vegetatif',
            'v' => 'Vegetatif'
        ];

        if (isset($mapping[$fase])) {
            return $mapping[$fase];
        }

        // Default jika tidak ada nilai yang cocok
        return 'Vegetatif';
    }
}
