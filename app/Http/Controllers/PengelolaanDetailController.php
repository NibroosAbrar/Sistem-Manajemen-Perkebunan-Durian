<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tree;
use App\Models\Fertilization;
use App\Models\Pesticide;

class PengelolaanDetailController extends Controller
{
    public function index()
    {
        $trees = Tree::all();
        $fertilizations = Fertilization::all();
        $pesticides = Pesticide::all();

        return view('pages.pengelolaan_detail', compact('trees', 'fertilizations', 'pesticides'));
    }
}
