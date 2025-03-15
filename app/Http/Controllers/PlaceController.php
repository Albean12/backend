<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Place;

class PlaceController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('category');

        $query = Place::query();
        if ($category && $category !== 'All') {
            $query->where('category', $category);
        }

        return response()->json($query->get());
    }
}
