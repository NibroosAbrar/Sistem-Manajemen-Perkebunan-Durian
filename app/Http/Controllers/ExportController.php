<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Tree;
use App\Models\Plantation;
use App\Models\Kegiatan;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    /**
     * Export data pohon beserta semua riwayatnya ke Excel
     */
    public function exportTrees(Request $request)
    {
        try {
            // Pastikan direktori storage/app/public tersedia
            $directory = storage_path('app/public');
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            
            // Buat spreadsheet baru
            $spreadsheet = new Spreadsheet();
            
            // Sheet untuk data pohon
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Data Pohon');
            
            // Header untuk data pohon
            $sheet->setCellValue('A1', 'ID Pohon');
            $sheet->setCellValue('B1', 'Blok');
            $sheet->setCellValue('C1', 'Varietas');
            $sheet->setCellValue('D1', 'Tahun Tanam');
            $sheet->setCellValue('E1', 'Status Kesehatan');
            $sheet->setCellValue('F1', 'Fase');
            $sheet->setCellValue('G1', 'Latitude');
            $sheet->setCellValue('H1', 'Longitude');
            $sheet->setCellValue('I1', 'Sumber Bibit');
            
            // Ambil data pohon
            $trees = Tree::with(['plantation', 'fertilizations', 'pesticides', 'harvests', 'zpts', 'growths', 'healthProfiles'])->get();
            
            // Isi data pohon
            $row = 2;
            foreach ($trees as $tree) {
                $sheet->setCellValue('A' . $row, $tree->id);
                $sheet->setCellValue('B' . $row, $tree->plantation ? $tree->plantation->name : '-');
                $sheet->setCellValue('C' . $row, $tree->varietas);
                $sheet->setCellValue('D' . $row, $tree->tahun_tanam);
                $sheet->setCellValue('E' . $row, $tree->health_status);
                $sheet->setCellValue('F' . $row, $tree->fase);
                $sheet->setCellValue('G' . $row, $tree->latitude);
                $sheet->setCellValue('H' . $row, $tree->longitude);
                $sheet->setCellValue('I' . $row, $tree->sumber_bibit);
                $row++;
            }
            
            // Sheet untuk pemupukan
            $fertilizationSheet = $spreadsheet->createSheet();
            $fertilizationSheet->setTitle('Pemupukan');
            
            // Header untuk pemupukan
            $fertilizationSheet->setCellValue('A1', 'ID Pohon');
            $fertilizationSheet->setCellValue('B1', 'Tanggal Pemupukan');
            $fertilizationSheet->setCellValue('C1', 'Nama Pupuk');
            $fertilizationSheet->setCellValue('D1', 'Jenis Pupuk');
            $fertilizationSheet->setCellValue('E1', 'Bentuk Pupuk');
            $fertilizationSheet->setCellValue('F1', 'Dosis');
            $fertilizationSheet->setCellValue('G1', 'Unit');
            
            // Ambil dan isi data pemupukan
            $row = 2;
            foreach ($trees as $tree) {
                if ($tree->fertilizations && $tree->fertilizations->count() > 0) {
                    foreach ($tree->fertilizations as $fertilization) {
                        $fertilizationSheet->setCellValue('A' . $row, $tree->id);
                        $fertilizationSheet->setCellValue('B' . $row, Carbon::parse($fertilization->tanggal_pemupukan)->format('d/m/Y'));
                        $fertilizationSheet->setCellValue('C' . $row, $fertilization->nama_pupuk);
                        $fertilizationSheet->setCellValue('D' . $row, $fertilization->jenis_pupuk);
                        $fertilizationSheet->setCellValue('E' . $row, $fertilization->bentuk_pupuk);
                        $fertilizationSheet->setCellValue('F' . $row, $fertilization->dosis_pupuk);
                        $fertilizationSheet->setCellValue('G' . $row, $fertilization->unit);
                        $row++;
                    }
                }
            }
            
            // Sheet untuk pestisida
            $pesticideSheet = $spreadsheet->createSheet();
            $pesticideSheet->setTitle('Pestisida');
            
            // Header untuk pestisida
            $pesticideSheet->setCellValue('A1', 'ID Pohon');
            $pesticideSheet->setCellValue('B1', 'Tanggal Aplikasi');
            $pesticideSheet->setCellValue('C1', 'Nama Pestisida');
            $pesticideSheet->setCellValue('D1', 'Jenis Pestisida');
            $pesticideSheet->setCellValue('E1', 'Dosis');
            $pesticideSheet->setCellValue('F1', 'Unit');
            
            // Ambil dan isi data pestisida
            $row = 2;
            foreach ($trees as $tree) {
                if ($tree->pesticides && $tree->pesticides->count() > 0) {
                    foreach ($tree->pesticides as $pesticide) {
                        $pesticideSheet->setCellValue('A' . $row, $tree->id);
                        $pesticideSheet->setCellValue('B' . $row, Carbon::parse($pesticide->tanggal_pestisida)->format('d/m/Y'));
                        $pesticideSheet->setCellValue('C' . $row, $pesticide->nama_pestisida);
                        $pesticideSheet->setCellValue('D' . $row, $pesticide->jenis_pestisida);
                        $pesticideSheet->setCellValue('E' . $row, $pesticide->dosis);
                        $pesticideSheet->setCellValue('F' . $row, $pesticide->unit);
                        $row++;
                    }
                }
            }
            
            // Sheet untuk panen
            $harvestSheet = $spreadsheet->createSheet();
            $harvestSheet->setTitle('Panen');
            
            // Header untuk panen
            $harvestSheet->setCellValue('A1', 'ID Pohon');
            $harvestSheet->setCellValue('B1', 'Tanggal Panen');
            $harvestSheet->setCellValue('C1', 'Jumlah Buah');
            $harvestSheet->setCellValue('D1', 'Total Berat');
            $harvestSheet->setCellValue('E1', 'Rata-rata Berat per Buah');
            $harvestSheet->setCellValue('F1', 'Kondisi Buah (%)');
            $harvestSheet->setCellValue('G1', 'Unit');
            
            // Ambil dan isi data panen
            $row = 2;
            foreach ($trees as $tree) {
                if ($tree->harvests && $tree->harvests->count() > 0) {
                    foreach ($tree->harvests as $harvest) {
                        $harvestSheet->setCellValue('A' . $row, $tree->id);
                        $harvestSheet->setCellValue('B' . $row, Carbon::parse($harvest->tanggal_panen)->format('d/m/Y'));
                        $harvestSheet->setCellValue('C' . $row, $harvest->fruit_count);
                        $harvestSheet->setCellValue('D' . $row, $harvest->total_weight);
                        $harvestSheet->setCellValue('E' . $row, $harvest->average_weight_per_fruit);
                        $harvestSheet->setCellValue('F' . $row, $harvest->fruit_condition);
                        $harvestSheet->setCellValue('G' . $row, $harvest->unit);
                        $row++;
                    }
                }
            }
            
            // Sheet untuk ZPT
            $zptSheet = $spreadsheet->createSheet();
            $zptSheet->setTitle('ZPT');
            
            // Header untuk ZPT
            $zptSheet->setCellValue('A1', 'ID Pohon');
            $zptSheet->setCellValue('B1', 'Tanggal Aplikasi');
            $zptSheet->setCellValue('C1', 'Nama ZPT');
            $zptSheet->setCellValue('D1', 'Merek');
            $zptSheet->setCellValue('E1', 'Jenis Senyawa');
            $zptSheet->setCellValue('F1', 'Konsentrasi');
            $zptSheet->setCellValue('G1', 'Volume Larutan');
            $zptSheet->setCellValue('H1', 'Fase Pertumbuhan');
            $zptSheet->setCellValue('I1', 'Metode Aplikasi');
            $zptSheet->setCellValue('J1', 'Unit');
            
            // Ambil dan isi data ZPT
            $row = 2;
            foreach ($trees as $tree) {
                if ($tree->zpts && $tree->zpts->count() > 0) {
                    foreach ($tree->zpts as $zpt) {
                        $zptSheet->setCellValue('A' . $row, $tree->id);
                        $zptSheet->setCellValue('B' . $row, Carbon::parse($zpt->tanggal_aplikasi)->format('d/m/Y'));
                        $zptSheet->setCellValue('C' . $row, $zpt->nama_zpt);
                        $zptSheet->setCellValue('D' . $row, $zpt->merek);
                        $zptSheet->setCellValue('E' . $row, $zpt->jenis_senyawa);
                        $zptSheet->setCellValue('F' . $row, $zpt->konsentrasi);
                        $zptSheet->setCellValue('G' . $row, $zpt->volume_larutan);
                        $zptSheet->setCellValue('H' . $row, $zpt->fase_pertumbuhan);
                        $zptSheet->setCellValue('I' . $row, $zpt->metode_aplikasi);
                        $zptSheet->setCellValue('J' . $row, $zpt->unit);
                        $row++;
                    }
                }
            }
            
            // Sheet untuk pertumbuhan
            $growthSheet = $spreadsheet->createSheet();
            $growthSheet->setTitle('Pertumbuhan');
            
            // Header untuk pertumbuhan
            $growthSheet->setCellValue('A1', 'ID Pohon');
            $growthSheet->setCellValue('B1', 'Tanggal');
            $growthSheet->setCellValue('C1', 'Fase');
            $growthSheet->setCellValue('D1', 'Tinggi (cm)');
            $growthSheet->setCellValue('E1', 'Diameter (cm)');
            
            // Ambil dan isi data pertumbuhan
            $row = 2;
            foreach ($trees as $tree) {
                if ($tree->growths && $tree->growths->count() > 0) {
                    foreach ($tree->growths as $growth) {
                        $growthSheet->setCellValue('A' . $row, $tree->id);
                        $growthSheet->setCellValue('B' . $row, Carbon::parse($growth->tanggal)->format('d/m/Y'));
                        $growthSheet->setCellValue('C' . $row, $growth->fase);
                        $growthSheet->setCellValue('D' . $row, $growth->tinggi);
                        $growthSheet->setCellValue('E' . $row, $growth->diameter);
                        $row++;
                    }
                }
            }
            
            // Sheet untuk kesehatan
            $healthSheet = $spreadsheet->createSheet();
            $healthSheet->setTitle('Kesehatan');
            
            // Header untuk kesehatan
            $healthSheet->setCellValue('A1', 'ID Pohon');
            $healthSheet->setCellValue('B1', 'Tanggal');
            $healthSheet->setCellValue('C1', 'Status Kesehatan');
            $healthSheet->setCellValue('D1', 'Gejala');
            $healthSheet->setCellValue('E1', 'Tindakan');
            
            // Ambil dan isi data kesehatan
            $row = 2;
            foreach ($trees as $tree) {
                if ($tree->healthProfiles && $tree->healthProfiles->count() > 0) {
                    foreach ($tree->healthProfiles as $health) {
                        $healthSheet->setCellValue('A' . $row, $tree->id);
                        $healthSheet->setCellValue('B' . $row, Carbon::parse($health->tanggal)->format('d/m/Y'));
                        $healthSheet->setCellValue('C' . $row, $health->status);
                        $healthSheet->setCellValue('D' . $row, $health->gejala);
                        $healthSheet->setCellValue('E' . $row, $health->tindakan);
                        $row++;
                    }
                }
            }
            
            // Format semua sheet agar kolom otomatis melebar sesuai konten
            foreach ($spreadsheet->getAllSheets() as $worksheet) {
                foreach (range('A', 'Z') as $column) {
                    $worksheet->getColumnDimension($column)->setAutoSize(true);
                }
            }
            
            // Buat file Excel
            $writer = new Xlsx($spreadsheet);
            $filename = 'Data_Pohon_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            // Save file ke storage
            $path = storage_path('app/public/' . $filename);
            $writer->save($path);
            
            // Download file
            return response()->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend();
            
        } catch (\Exception $e) {
            Log::error('Error saat ekspor data pohon: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengekspor data: ' . $e->getMessage());
        }
    }
    
    /**
     * Export data blok kebun ke Excel
     */
    public function exportPlantations(Request $request)
    {
        try {
            // Pastikan direktori storage/app/public tersedia
            $directory = storage_path('app/public');
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            
            // Buat spreadsheet baru
            $spreadsheet = new Spreadsheet();
            
            // Sheet untuk data blok kebun
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Data Blok Kebun');
            
            // Header untuk data blok kebun
            $sheet->setCellValue('A1', 'ID');
            $sheet->setCellValue('B1', 'Nama Blok');
            $sheet->setCellValue('C1', 'Latitude');
            $sheet->setCellValue('D1', 'Longitude');
            $sheet->setCellValue('E1', 'Luas Area (ha)');
            $sheet->setCellValue('F1', 'Tipe Tanah');
            $sheet->setCellValue('G1', 'Jumlah Pohon');
            
            // Ambil data blok kebun
            $plantations = Plantation::withCount('trees')->get();
            
            // Isi data blok kebun
            $row = 2;
            foreach ($plantations as $plantation) {
                $sheet->setCellValue('A' . $row, $plantation->id);
                $sheet->setCellValue('B' . $row, $plantation->name);
                $sheet->setCellValue('C' . $row, $plantation->latitude);
                $sheet->setCellValue('D' . $row, $plantation->longitude);
                $sheet->setCellValue('E' . $row, $plantation->luas_area);
                $sheet->setCellValue('F' . $row, $plantation->tipe_tanah);
                $sheet->setCellValue('G' . $row, $plantation->trees_count);
                $row++;
            }
            
            // Sheet untuk detail pohon di setiap blok
            $treeSheet = $spreadsheet->createSheet();
            $treeSheet->setTitle('Detail Pohon per Blok');
            
            // Header untuk detail pohon
            $treeSheet->setCellValue('A1', 'Nama Blok');
            $treeSheet->setCellValue('B1', 'ID Pohon');
            $treeSheet->setCellValue('C1', 'Varietas');
            $treeSheet->setCellValue('D1', 'Tahun Tanam');
            $treeSheet->setCellValue('E1', 'Status Kesehatan');
            
            // Ambil dan isi data detail pohon per blok
            $row = 2;
            foreach ($plantations as $plantation) {
                $trees = $plantation->trees;
                if ($trees && $trees->count() > 0) {
                    foreach ($trees as $tree) {
                        $treeSheet->setCellValue('A' . $row, $plantation->name);
                        $treeSheet->setCellValue('B' . $row, $tree->id);
                        $treeSheet->setCellValue('C' . $row, $tree->varietas);
                        $treeSheet->setCellValue('D' . $row, $tree->tahun_tanam);
                        $treeSheet->setCellValue('E' . $row, $tree->health_status);
                        $row++;
                    }
                }
            }
            
            // Format semua sheet agar kolom otomatis melebar sesuai konten
            foreach ($spreadsheet->getAllSheets() as $worksheet) {
                foreach (range('A', 'Z') as $column) {
                    $worksheet->getColumnDimension($column)->setAutoSize(true);
                }
            }
            
            // Buat file Excel
            $writer = new Xlsx($spreadsheet);
            $filename = 'Data_Blok_Kebun_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            // Save file ke storage
            $path = storage_path('app/public/' . $filename);
            $writer->save($path);
            
            // Download file
            return response()->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend();
            
        } catch (\Exception $e) {
            Log::error('Error saat ekspor data blok kebun: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengekspor data: ' . $e->getMessage());
        }
    }

    /**
     * Export data kegiatan ke Excel
     */
    public function exportKegiatan(Request $request)
    {
        try {
            // Pastikan direktori storage/app/public tersedia
            $directory = storage_path('app/public');
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            
            // Buat spreadsheet baru
            $spreadsheet = new Spreadsheet();
            
            // Sheet untuk data kegiatan
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Data Kegiatan');
            
            // Header untuk data kegiatan
            $sheet->setCellValue('A1', 'ID');
            $sheet->setCellValue('B1', 'Nama Kegiatan');
            $sheet->setCellValue('C1', 'Jenis Kegiatan');
            $sheet->setCellValue('D1', 'Deskripsi');
            $sheet->setCellValue('E1', 'Tanggal Mulai');
            $sheet->setCellValue('F1', 'Tanggal Selesai');
            $sheet->setCellValue('G1', 'Status');
            
            // Ambil data kegiatan
            $kegiatan = Kegiatan::all();
            
            // Isi data kegiatan
            $row = 2;
            foreach ($kegiatan as $item) {
                $sheet->setCellValue('A' . $row, $item->id);
                $sheet->setCellValue('B' . $row, $item->nama_kegiatan ?? 'N/A');
                $sheet->setCellValue('C' . $row, $item->jenis_kegiatan);
                $sheet->setCellValue('D' . $row, $item->deskripsi_kegiatan ?? $item->deskripsi);
                $sheet->setCellValue('E' . $row, $item->tanggal_mulai ? Carbon::parse($item->tanggal_mulai)->format('d/m/Y') : 'N/A');
                
                if($item->status == 'Selesai' || $item->selesai) {
                    $sheet->setCellValue('F' . $row, $item->tanggal_selesai ? 
                        Carbon::parse($item->tanggal_selesai)->format('d/m/Y') : 
                        ($item->tanggal ? Carbon::parse($item->tanggal)->format('d/m/Y') : 'N/A')
                    );
                } else {
                    $sheet->setCellValue('F' . $row, 'N/A');
                }
                
                $sheet->setCellValue('G' . $row, $item->status ?? ($item->selesai ? 'Selesai' : 'Belum Berjalan'));
                $row++;
            }
            
            // Format semua sheet agar kolom otomatis melebar sesuai konten
            foreach ($spreadsheet->getAllSheets() as $worksheet) {
                foreach (range('A', 'Z') as $column) {
                    $worksheet->getColumnDimension($column)->setAutoSize(true);
                }
            }
            
            // Buat file Excel
            $writer = new Xlsx($spreadsheet);
            $filename = 'Data_Kegiatan_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            // Save file ke storage
            $path = storage_path('app/public/' . $filename);
            $writer->save($path);
            
            // Download file
            return response()->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend();
            
        } catch (\Exception $e) {
            Log::error('Error saat ekspor data kegiatan: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengekspor data: ' . $e->getMessage());
        }
    }

    /**
     * Export data riwayat pohon tertentu ke Excel
     */
    public function exportTreeHistory(Request $request)
    {
        try {
            $treeId = $request->input('id');
            
            if (!$treeId) {
                return redirect()->back()->with('error', 'ID pohon tidak ditemukan');
            }
            
            $tree = Tree::with(['plantation', 'fertilizations', 'pesticides', 'harvests', 'zpts', 'growths', 'healthProfiles'])
                ->find($treeId);
            
            if (!$tree) {
                return redirect()->back()->with('error', 'Pohon tidak ditemukan');
            }
            
            // Pastikan direktori storage/app/public tersedia
            $directory = storage_path('app/public');
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            
            // Buat spreadsheet baru
            $spreadsheet = new Spreadsheet();
            
            // Sheet untuk data pohon
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Data Pohon #' . $treeId);
            
            // Header untuk data pohon
            $sheet->setCellValue('A1', 'ID Pohon');
            $sheet->setCellValue('B1', 'Blok');
            $sheet->setCellValue('C1', 'Varietas');
            $sheet->setCellValue('D1', 'Tahun Tanam');
            $sheet->setCellValue('E1', 'Status Kesehatan');
            $sheet->setCellValue('F1', 'Fase');
            $sheet->setCellValue('G1', 'Latitude');
            $sheet->setCellValue('H1', 'Longitude');
            $sheet->setCellValue('I1', 'Sumber Bibit');
            
            // Isi data pohon
            $sheet->setCellValue('A2', $tree->id);
            $sheet->setCellValue('B2', $tree->plantation ? $tree->plantation->name : '-');
            $sheet->setCellValue('C2', $tree->varietas);
            $sheet->setCellValue('D2', $tree->tahun_tanam);
            $sheet->setCellValue('E2', $tree->health_status);
            $sheet->setCellValue('F2', $tree->fase);
            $sheet->setCellValue('G2', $tree->latitude);
            $sheet->setCellValue('H2', $tree->longitude);
            $sheet->setCellValue('I2', $tree->sumber_bibit);
            
            // Sheet untuk pemupukan
            $fertilizationSheet = $spreadsheet->createSheet();
            $fertilizationSheet->setTitle('Pemupukan');
            
            // Header untuk pemupukan
            $fertilizationSheet->setCellValue('A1', 'ID Pohon');
            $fertilizationSheet->setCellValue('B1', 'Tanggal Pemupukan');
            $fertilizationSheet->setCellValue('C1', 'Nama Pupuk');
            $fertilizationSheet->setCellValue('D1', 'Jenis Pupuk');
            $fertilizationSheet->setCellValue('E1', 'Bentuk Pupuk');
            $fertilizationSheet->setCellValue('F1', 'Dosis');
            $fertilizationSheet->setCellValue('G1', 'Unit');
            
            // Isi data pemupukan
            $row = 2;
            if ($tree->fertilizations && $tree->fertilizations->count() > 0) {
                foreach ($tree->fertilizations as $fertilization) {
                    $fertilizationSheet->setCellValue('A' . $row, $tree->id);
                    $fertilizationSheet->setCellValue('B' . $row, Carbon::parse($fertilization->tanggal_pemupukan)->format('d/m/Y'));
                    $fertilizationSheet->setCellValue('C' . $row, $fertilization->nama_pupuk);
                    $fertilizationSheet->setCellValue('D' . $row, $fertilization->jenis_pupuk);
                    $fertilizationSheet->setCellValue('E' . $row, $fertilization->bentuk_pupuk);
                    $fertilizationSheet->setCellValue('F' . $row, $fertilization->dosis_pupuk);
                    $fertilizationSheet->setCellValue('G' . $row, $fertilization->unit);
                    $row++;
                }
            }
            
            // Sheet untuk pestisida
            $pesticideSheet = $spreadsheet->createSheet();
            $pesticideSheet->setTitle('Pestisida');
            
            // Header untuk pestisida
            $pesticideSheet->setCellValue('A1', 'ID Pohon');
            $pesticideSheet->setCellValue('B1', 'Tanggal Aplikasi');
            $pesticideSheet->setCellValue('C1', 'Nama Pestisida');
            $pesticideSheet->setCellValue('D1', 'Jenis Pestisida');
            $pesticideSheet->setCellValue('E1', 'Dosis');
            $pesticideSheet->setCellValue('F1', 'Unit');
            
            // Isi data pestisida
            $row = 2;
            if ($tree->pesticides && $tree->pesticides->count() > 0) {
                foreach ($tree->pesticides as $pesticide) {
                    $pesticideSheet->setCellValue('A' . $row, $tree->id);
                    $pesticideSheet->setCellValue('B' . $row, Carbon::parse($pesticide->tanggal_pestisida)->format('d/m/Y'));
                    $pesticideSheet->setCellValue('C' . $row, $pesticide->nama_pestisida);
                    $pesticideSheet->setCellValue('D' . $row, $pesticide->jenis_pestisida);
                    $pesticideSheet->setCellValue('E' . $row, $pesticide->dosis);
                    $pesticideSheet->setCellValue('F' . $row, $pesticide->unit);
                    $row++;
                }
            }
            
            // Sheet untuk panen
            $harvestSheet = $spreadsheet->createSheet();
            $harvestSheet->setTitle('Panen');
            
            // Header untuk panen
            $harvestSheet->setCellValue('A1', 'ID Pohon');
            $harvestSheet->setCellValue('B1', 'Tanggal Panen');
            $harvestSheet->setCellValue('C1', 'Jumlah Buah');
            $harvestSheet->setCellValue('D1', 'Total Berat');
            $harvestSheet->setCellValue('E1', 'Rata-rata Berat per Buah');
            $harvestSheet->setCellValue('F1', 'Kondisi Buah (%)');
            $harvestSheet->setCellValue('G1', 'Unit');
            
            // Isi data panen
            $row = 2;
            if ($tree->harvests && $tree->harvests->count() > 0) {
                foreach ($tree->harvests as $harvest) {
                    $harvestSheet->setCellValue('A' . $row, $tree->id);
                    $harvestSheet->setCellValue('B' . $row, Carbon::parse($harvest->tanggal_panen)->format('d/m/Y'));
                    $harvestSheet->setCellValue('C' . $row, $harvest->fruit_count);
                    $harvestSheet->setCellValue('D' . $row, $harvest->total_weight);
                    $harvestSheet->setCellValue('E' . $row, $harvest->average_weight_per_fruit);
                    $harvestSheet->setCellValue('F' . $row, $harvest->fruit_condition);
                    $harvestSheet->setCellValue('G' . $row, $harvest->unit);
                    $row++;
                }
            }
            
            // Sheet untuk ZPT
            $zptSheet = $spreadsheet->createSheet();
            $zptSheet->setTitle('ZPT');
            
            // Header untuk ZPT
            $zptSheet->setCellValue('A1', 'ID Pohon');
            $zptSheet->setCellValue('B1', 'Tanggal Aplikasi');
            $zptSheet->setCellValue('C1', 'Nama ZPT');
            $zptSheet->setCellValue('D1', 'Merek');
            $zptSheet->setCellValue('E1', 'Jenis Senyawa');
            $zptSheet->setCellValue('F1', 'Konsentrasi');
            $zptSheet->setCellValue('G1', 'Volume Larutan');
            $zptSheet->setCellValue('H1', 'Fase Pertumbuhan');
            $zptSheet->setCellValue('I1', 'Metode Aplikasi');
            $zptSheet->setCellValue('J1', 'Unit');
            
            // Isi data ZPT
            $row = 2;
            if ($tree->zpts && $tree->zpts->count() > 0) {
                foreach ($tree->zpts as $zpt) {
                    $zptSheet->setCellValue('A' . $row, $tree->id);
                    $zptSheet->setCellValue('B' . $row, Carbon::parse($zpt->tanggal_aplikasi)->format('d/m/Y'));
                    $zptSheet->setCellValue('C' . $row, $zpt->nama_zpt);
                    $zptSheet->setCellValue('D' . $row, $zpt->merek);
                    $zptSheet->setCellValue('E' . $row, $zpt->jenis_senyawa);
                    $zptSheet->setCellValue('F' . $row, $zpt->konsentrasi);
                    $zptSheet->setCellValue('G' . $row, $zpt->volume_larutan);
                    $zptSheet->setCellValue('H' . $row, $zpt->fase_pertumbuhan);
                    $zptSheet->setCellValue('I' . $row, $zpt->metode_aplikasi);
                    $zptSheet->setCellValue('J' . $row, $zpt->unit);
                    $row++;
                }
            }
            
            // Sheet untuk pertumbuhan
            $growthSheet = $spreadsheet->createSheet();
            $growthSheet->setTitle('Pertumbuhan');
            
            // Header untuk pertumbuhan
            $growthSheet->setCellValue('A1', 'ID Pohon');
            $growthSheet->setCellValue('B1', 'Tanggal');
            $growthSheet->setCellValue('C1', 'Fase');
            $growthSheet->setCellValue('D1', 'Tinggi (cm)');
            $growthSheet->setCellValue('E1', 'Diameter (cm)');
            
            // Isi data pertumbuhan
            $row = 2;
            if ($tree->growths && $tree->growths->count() > 0) {
                foreach ($tree->growths as $growth) {
                    $growthSheet->setCellValue('A' . $row, $tree->id);
                    $growthSheet->setCellValue('B' . $row, Carbon::parse($growth->tanggal)->format('d/m/Y'));
                    $growthSheet->setCellValue('C' . $row, $growth->fase);
                    $growthSheet->setCellValue('D' . $row, $growth->tinggi);
                    $growthSheet->setCellValue('E' . $row, $growth->diameter);
                    $row++;
                }
            }
            
            // Sheet untuk kesehatan
            $healthSheet = $spreadsheet->createSheet();
            $healthSheet->setTitle('Kesehatan');
            
            // Header untuk kesehatan
            $healthSheet->setCellValue('A1', 'ID Pohon');
            $healthSheet->setCellValue('B1', 'Tanggal');
            $healthSheet->setCellValue('C1', 'Status Kesehatan');
            $healthSheet->setCellValue('D1', 'Gejala');
            $healthSheet->setCellValue('E1', 'Tindakan');
            
            // Isi data kesehatan
            $row = 2;
            if ($tree->healthProfiles && $tree->healthProfiles->count() > 0) {
                foreach ($tree->healthProfiles as $health) {
                    $healthSheet->setCellValue('A' . $row, $tree->id);
                    $healthSheet->setCellValue('B' . $row, Carbon::parse($health->tanggal)->format('d/m/Y'));
                    $healthSheet->setCellValue('C' . $row, $health->status);
                    $healthSheet->setCellValue('D' . $row, $health->gejala);
                    $healthSheet->setCellValue('E' . $row, $health->tindakan);
                    $row++;
                }
            }
            
            // Format semua sheet agar kolom otomatis melebar sesuai konten
            foreach ($spreadsheet->getAllSheets() as $worksheet) {
                foreach (range('A', 'Z') as $column) {
                    $worksheet->getColumnDimension($column)->setAutoSize(true);
                }
            }
            
            // Buat file Excel
            $writer = new Xlsx($spreadsheet);
            $filename = 'Riwayat_Pohon_' . $treeId . '_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            // Save file ke storage
            $path = storage_path('app/public/' . $filename);
            $writer->save($path);
            
            // Download file
            return response()->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend();
            
        } catch (\Exception $e) {
            Log::error('Error saat ekspor riwayat pohon: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengekspor data: ' . $e->getMessage());
        }
    }
} 