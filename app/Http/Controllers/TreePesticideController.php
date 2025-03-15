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
            'nama_pestisida' => 'nullable|string',
            'jenis_pestisida' => 'nullable|string',
            'dosis' => 'nullable|numeric',
        ]);

        TreePesticide::create($validated);
        return response()->json(['success' => true, 'message' => 'Pesticide data saved successfully.']);
    }
}
