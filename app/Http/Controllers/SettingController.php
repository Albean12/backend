<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    // Fetch Landlord Contact
    public function getLandlordContact()
    {
        $contact = DB::table('settings')->where('key', 'landlord_contact')->first();
        return response()->json([
            'phone_number' => $contact->phone_number,
            'email' => $contact->email,
        ]);
    }
    

    // Update Landlord Contact
    public function updateLandlordContact(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:20',
            'email' => 'required|email|max:255',
        ]);
    
        DB::table('settings')
            ->where('key', 'landlord_contact')
            ->update([
                'phone_number' => $request->phone_number,
                'email' => $request->email,
                'updated_at' => now(),
            ]);
    
        return response()->json(['message' => 'Landlord contact updated successfully!']);
    }
    
    }

