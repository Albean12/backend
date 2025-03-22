<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Models\Application;
use App\Models\User;
use App\Models\Unit;
class ApplicationController extends Controller
{
    // Fetch all pending applications

    public function index()
    {
        // Fetch all pending applications
        $applications = Application::where('status', 'pending')->get();
    
        // Fetch units with tenant count and status
        $units = Unit::select('id', 'name', 'unit_code', 'capacity', 'price', 'status')
                     ->withCount('users')
                     ->get();
    
        return response()->json([
            'applications' => $applications,
            'units' => $units,
        ]);
    }
    

    // Save a new application
    public function store(Request $request)
    {
        \Log::info('Store method triggered', $request->all());
    
        // Validate incoming request
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'email' => 'required|email|unique:applications,email',
            'address' => 'required|string',
            'contact_number' => 'required|string|max:20',
            'occupation' => 'required|string|max:100',
            'check_in_date' => 'required|date',
            'duration' => 'required|integer',
            'reservation_details' => 'required|string',
            'id_type' => 'required|string',
            'valid_id' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048', // Ensure it's a file
        ]);
    
        // Handle file upload
        $validIdPath = null;
        if ($request->hasFile('valid_id')) {
            $validIdPath = $request->file('valid_id')->store('uploads/valid_ids', 'public');
        }
        // Check if user has already applied
        $existingApplication = Application::where('email', $validated['email'])->first();

        if ($existingApplication) {
            return response()->json([
                'message' => 'You have already submitted an application.',
            ], 409);
        }

        // Fetch the unit_id and set_price from the units table using reservation_details (unit_code)
        $unit = Unit::where('unit_code', $validated['reservation_details'])->first();
    
        if (!$unit) {
            return response()->json(['message' => 'Unit code not found.'], 400);
        }
    
        // Auto-set status and set_price based on unit
        $status = 'pending'; // Default status for new applications
        $setPrice = $unit->price; // Auto-fetch the unit price
    
        // Create the application
        $application = Application::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'middle_name' => $validated['middle_name'],
            'email' => $validated['email'],
            'address' => $validated['address'],
            'contact_number' => $validated['contact_number'],
            'check_in_date' => $validated['check_in_date'],
            'duration' => $validated['duration'],
            'reservation_details' => $validated['reservation_details'], // Store unit_code
            'unit_id' => $unit->id, // Save unit_id
            'occupation' => $validated['occupation'],
            'id_type' => $validated['id_type'],
            'valid_id' => $validIdPath,
            'status' => $status, // Auto-set status
            'set_price' => $setPrice, // Auto-fetch price
        ]);
    
        return response()->json(['message' => 'Application submitted successfully!', 'application' => $application], 201);
    }
    
    
// Accept an application
public function accept(Request $request, $id)
{
    try {
        // Validate the incoming request
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
        ]);

        // Find the application
        $application = Application::findOrFail($id);

        // Retrieve the unit_code from reservation_details
        $unitCode = $application->reservation_details;

        // Fetch the unit_id based on the unit_code
        $unit = \DB::table('units')->where('unit_code', $unitCode)->first();

        if (!$unit) {
            return response()->json(['message' => 'Unit code not found for the selected unit.'], 400);
        }

        \Log::info('Unit Found', ['unit_id' => $unit->id, 'unit_code' => $unitCode]);

        // Check if a user with this email already exists
        $existingUser = User::where('email', $validated['email'])->first();
        if ($existingUser) {
            return response()->json([
                'message' => 'A user with this email already exists.'
            ], 409);
        }

        // Generate random credentials
        $password = substr(md5(time()), 0, 8);

        // Explicitly cast unit_id to ensure it's passed correctly
        $unitId = (int) $unit->id;

        // Create a new tenant account in the users table
        $user = User::create([
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($password),
            'unit_id' => $unitId, // Assign unit_id
            'role' => 'tenant',
        ]);

        \Log::info('User Created', ['user_id' => $user->id, 'unit_id' => $user->unit_id]);

        // Update the application's status to 'Accepted' and assign the unit_id
        $application->status = 'Accepted';
        $application->unit_id = $unitId;
        $application->save();

        \Log::info('Application Updated', ['application_id' => $application->id, 'unit_id' => $application->unit_id]);

        // Send credentials to the tenant via email
        Mail::send([], [], function ($message) use ($validated, $password) {
            $message->to($validated['email'])
                ->subject('Your Tenant Account Details')
                ->text("Dear {$validated['first_name']} {$validated['last_name']},\n\nYour account has been successfully created.\nUsername: {$validated['email']}\nPassword: {$password}\n\nThank you!");
        });

        return response()->json(['message' => 'Tenant account created successfully, and unit assigned.']);

    } catch (\Exception $e) {
        \Log::error('Error accepting application: ' . $e->getMessage());

        return response()->json([
            'message' => 'An error occurred while accepting the application.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

// Decline an application
public function decline($id)
{
    try {
        // Find the application by ID
        $application = Application::findOrFail($id);

        // Update the application status to 'Declined'
        $application->status = 'Declined';
        $application->save();

        return response()->json(['message' => 'Application declined successfully.']);
    } catch (\Exception $e) {
        return response()->json(['error' => 'An error occurred while declining the application.'], 500);
    }
}


public function update(Request $request, $id)
{
    $request->validate([
        'price_option' => 'required|in:unit,custom',
        'set_price' => 'nullable|numeric|min:0',
    ]);

    $application = Application::findOrFail($id);

    // Save set_price based on the selected option
    if ($request->price_option === 'custom') {
        $application->set_price = $request->set_price;
    } else {
        $application->set_price = null;
    }

    $application->save();

    return redirect()->route('applications.index')->with('success', 'Application updated successfully.');
}

}
