<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TreePesticide;

class TreePesticideController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tree_id' => 'required|exists:trees,id',
            'tanggal_pestisida' => 'required|date',
            'nama_pestisida' => 'required|string',
            'jenis_pestisida' => 'required|string',
            'bentuk_pestisida' => 'nullable|string',
            'dosis' => 'required|numeric',
            'unit' => 'required|string',
        ]);

        TreePesticide::create($validated);
        return response()->json(['success' => true, 'message' => 'Pesticide data saved successfully.']);
    }
}
