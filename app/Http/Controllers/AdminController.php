<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Unit;
use App\Models\Profile;

class AdminController extends Controller
{
    // Default admin dashboard index
    public function index()
    {
        return response()->json(['message' => 'Welcome to the Admin Dashboard']);
    }

    // Fetch tenants with profiles and unit details
    public function getTenants()
    {
        $tenants = User::where('role', 'tenant')
        ->leftJoin('applications', 'users.email', '=', 'applications.email')
        ->select('users.id', 'users.name', 'applications.address') // Include desired fields
        ->get()
        ->map(function ($tenant) {
            return [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ];
        });
    
        // Map application details to the response
        $tenants = User::with('application')->get();

        $tenants->transform(function ($tenant) {
            return [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
                'address' => optional($tenant->application)->address,
                'contact_number' => optional($tenant->application)->contact_number,
                'check_in_date' => optional($tenant->application)->check_in_date,
                'duration' => optional($tenant->application)->duration,
                'occupation' => optional($tenant->application)->occupation,
                'valid_id' => optional($tenant->application)->valid_id,
                'unit_code' => $tenant->unit_code ?? null,
            ];
        });
        
        return response()->json($tenants);
    }
    
}
