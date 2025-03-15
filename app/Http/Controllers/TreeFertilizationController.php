<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TreeFertilization;

class TreeFertilizationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tree_id' => 'required|exists:trees,id',
            'nama_pupuk' => 'nullable|string',
            'jenis_pupuk' => 'nullable|in:Organik,Anorganik',
            'bentuk_pupuk' => 'nullable|string',
            'dosis_pupuk' => 'nullable|numeric',
            'sumber_pupuk' => 'nullable|string',
        ]);

        TreeFertilization::create($validated);
        return response()->json(['success' => true, 'message' => 'Fertilization data saved successfully.']);
    }
}
