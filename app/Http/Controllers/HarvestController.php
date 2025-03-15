<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Harvest;

class HarvestController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tree_id' => 'required|exists:trees,id',
            'total_weight' => 'nullable|numeric',
            'fruit_count' => 'nullable|integer',
            'average_weight_per_fruit' => 'nullable|numeric',
            'fruit_condition' => 'nullable|string',
        ]);

        Harvest::create($validated);
        return response()->json(['success' => true, 'message' => 'Harvest data saved successfully.']);
    }
}
