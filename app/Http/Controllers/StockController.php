<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stock;

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
            'unit' => 'required|string',
            'date_added' => 'required|date',
        ]);

        Stock::create($request->all());

        return redirect()->route('stok')->with('success', 'Stok berhasil ditambahkan!');
    }

    // Update stok
    public function update(Request $request, Stock $stock)
    {
        $request->validate([
            'name' => 'required|string',
            'quantity' => 'required|integer',
            'unit' => 'required|string',
            'date_added' => 'required|date',
        ]);

        $stock->update($request->all());

        return redirect()->route('stok')->with('success', 'Stok berhasil diperbarui!');
    }

    // Hapus stok
    public function destroy(Stock $stock)
    {
        $stock->delete();
        return redirect()->route('stok')->with('success', 'Stok berhasil dihapus!');
    }
}
