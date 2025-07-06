<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stock;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StockController extends Controller
{
    // Tampilkan halaman stok dengan data
    public function index()
    {
        $stocks = [
            'bibit_pohon' => Stock::where('category', 'bibit_pohon')->get(),
            'pupuk' => Stock::where('category', 'pupuk')->get(),
            'pestisida_fungisida' => Stock::where('category', 'pestisida_fungisida')->get(),
            'alat_perlengkapan' => Stock::where('category', 'alat_perlengkapan')->get(),
            'zat_pengatur_tumbuh' => Stock::where('category', 'zat_pengatur_tumbuh')->get(),
        ];
        return view('pages.stok', compact('stocks'));
    }

    // Tambah stok baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
            'quantity' => 'required|integer',
            'unit' => 'nullable|string',
            'date_added' => 'required|date',
            'type' => 'nullable|string|in:in,out',
        ]);

        Stock::create($request->all());

        return redirect()->route('stok')->with('success', 'Stok berhasil ditambahkan!');
    }

    // Update stok
    public function update(Request $request, $id)
    {
        // Validasi ID terlebih dahulu
        $stock = Stock::find($id);

        if (!$stock) {
            return redirect()->route('stok')->with('error', 'Stok tidak ditemukan!');
        }

        $request->validate([
            'name' => 'required|string',
            'quantity' => 'required|integer',
            'unit' => 'nullable|string',
            'date_added' => 'required|date',
            'type' => 'nullable|string|in:in,out',
        ]);

        $stock->update($request->all());

        return redirect()->route('stok')->with('success', 'Stok berhasil diperbarui!');
    }

    // Hapus stok
    public function destroy($id)
    {
        $stock = Stock::find($id);

        if (!$stock) {
            return redirect()->route('stok')->with('error', 'Stok tidak ditemukan!');
        }

        $stock->delete();
        return redirect()->route('stok')->with('success', 'Stok berhasil dihapus!');
    }

    // Export data stok ke Excel
    public function exportExcel(Request $request)
    {
        $category = $request->input('category', 'all');
        $exportType = $request->input('export_type', 'all');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set header
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Nama');
        $sheet->setCellValue('C1', 'Kategori');
        $sheet->setCellValue('D1', 'Jumlah');
        $sheet->setCellValue('E1', 'Satuan');
        $sheet->setCellValue('F1', 'Tipe');
        $sheet->setCellValue('G1', 'Tanggal');

        // Style header
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCFFCC');

        // Query data
        $query = Stock::query();

        if ($category !== 'all') {
            $query->where('category', $category);
        }

        if ($exportType === 'in') {
            $query->where(function ($q) {
                $q->where('type', 'in')->orWhereNull('type');
            });
        } elseif ($exportType === 'out') {
            $query->where('type', 'out');
        }

        $stocks = $query->get();

        // Fill data
        $row = 2;
        foreach ($stocks as $index => $stock) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $stock->name);
            $sheet->setCellValue('C' . $row, ucwords(str_replace('_', ' ', $stock->category)));
            $sheet->setCellValue('D' . $row, $stock->quantity);
            $sheet->setCellValue('E' . $row, $stock->unit);
            $sheet->setCellValue('F' . $row, $stock->type === 'out' ? 'Keluar' : 'Masuk');
            $sheet->setCellValue('G' . $row, $stock->date_added);
            $row++;
        }

        // Auto size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create file
        $writer = new Xlsx($spreadsheet);
        $filename = 'stok_' . date('Y-m-d') . '.xlsx';

        // Save to storage
        $path = storage_path('app/public/exports/' . $filename);

        // Create directory if not exists
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $writer->save($path);

        return response()->download($path)->deleteFileAfterSend();
    }
}
