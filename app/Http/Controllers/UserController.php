<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        // Fetch users with the 'tenant' role
        $tenants = User::where('role', 'tenant')->get();

        return response()->json($tenants);
    }
}
