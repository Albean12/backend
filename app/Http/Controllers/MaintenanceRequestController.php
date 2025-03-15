<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaintenanceRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log; // For debugging purposes
use App\Models\Notification;
use App\Models\User;
class MaintenanceRequestController extends Controller
{
    // Store a new maintenance requestuse App\Models\Notification; // Import Notification model
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'description' => 'required|string',
            'status' => 'required|string|in:pending,in_progress,completed,rejected',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,mp4|max:20480',
            'schedule' => 'nullable|date_format:Y-m-d H:i:s',
        ]);
    
        $filePath = null;
    
        // Handle file upload
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('maintenance_uploads', 'public');
            $filePath = str_replace('public/', 'storage/', $filePath);
        }
    
        // Create maintenance request
        $maintenanceRequest = MaintenanceRequest::create([
            'user_id' => $validated['user_id'],
            'description' => $validated['description'],
            'status' => $validated['status'],
            'file_path' => $filePath,
            'schedule' => $validated['schedule'] ?? null,
        ]);
    
        // Fetch tenant details
        $tenant = User::with('unit')->find($validated['user_id']);
        $unitCode = optional($tenant->unit)->unit_code;
        $tenantName = $tenant->name;
    
        \Log::info('Tenant Details:', [
            'Name' => $tenantName,
            'Unit Code' => $unitCode,
        ]);
    
        // Create notifications for all admins only
        $admins = User::where('role', 'admin')->get();
        if ($admins->isEmpty()) {
            \Log::error('No admins found for maintenance request notification.');
            return response()->json(['message' => 'Maintenance request submitted, but notification failed.'], 500);
        }
        
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id, // Admin user ID
                'title' => 'New Maintenance Request',
                'message' => "New maintenance request from $tenantName (Unit: $unitCode).",
                'type' => 'maintenance_request',
                'related_id' => $maintenanceRequest->id,
                'is_read' => false,
            ]);
        }
        
    
        \Log::info('Maintenance request notifications created successfully for all admins.');
    
        return response()->json($maintenanceRequest, 201);
    }
    
    // Cancel Function
    public function cancel($id)
{
    $request = MaintenanceRequest::findOrFail($id);
    $request->update(['status' => 'canceled']);

    return response()->json(['message' => 'Maintenance request has been canceled successfully.']);
}


    // Fetch all maintenance requests
    public function index()
    {
        $maintenanceRequests = MaintenanceRequest::with(['user:id,name,unit_id', 'user.unit:id,unit_code'])
            ->select('id', 'user_id', 'description', 'status', 'schedule', 'file_path', 'created_at', 'updated_at')
            ->get()
            ->map(function ($request) {
                $request->unit_code = optional($request->user->unit)->unit_code; // Safely get unit_code
                return $request;
            });
    
        return response()->json($maintenanceRequests);
    }
    
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate(['status' => 'required|string|in:pending,in_progress,completed,canceled']);
    
        $maintenanceRequest = MaintenanceRequest::findOrFail($id);
        $maintenanceRequest->setAttribute('status', $validated['status']);
        $maintenanceRequest->save();
    
        // Notify the tenant when the status is completed
        if ($validated['status'] === 'completed') {
            $tenant = User::findOrFail($maintenanceRequest->user_id);
            Notification::create([
                'user_id' => $tenant->id,
                'title' => 'Maintenance Completed',
                'message' => 'Your maintenance request has been completed.',
                'is_read' => false,
            ]);
        }
    
        return response()->json(['message' => 'Status updated successfully.']);
    }
    
    
    public function schedule(Request $request, $id)
{
    $validated = $request->validate([
        'schedule' => 'required|date_format:Y-m-d\TH:i',
    ]);

    \Log::info('Schedule Input:', ['schedule' => $validated['schedule']]); // Log the input

    $maintenanceRequest = MaintenanceRequest::findOrFail($id);
    $maintenanceRequest->setAttribute('schedule', $validated['schedule']);
    $maintenanceRequest->setAttribute('status', 'scheduled');
    $maintenanceRequest->save();

    // Notify the tenant
    $tenant = User::findOrFail($maintenanceRequest->user_id);
    Notification::create([
        'user_id' => $tenant->id,
        'title' => 'Maintenance Scheduled',
        'message' => "Your maintenance request has been scheduled for {$validated['schedule']}.",
        'is_read' => false,
    ]);

    return response()->json(['message' => 'Maintenance scheduled successfully.']);
}
    public function tenantRequests(Request $request)
    {
        $userId = $request->user()->id;
    
        $requests = MaintenanceRequest::where('user_id', $userId)
            ->select('id', 'description', 'status', 'schedule', 'file_path', 'created_at')
            ->get()
            ->map(function ($request) {
                $request->file_url = $request->file_path 
                    ? Storage::url($request->file_path) // Dynamically generate storage URL
                    : null;
                return $request;
            });
            
            
    
        return response()->json($requests);
    }
    

    public function cancelRequest($id)
    {
        $maintenanceRequest = MaintenanceRequest::findOrFail($id);
    
        // Safely access the 'status' attribute
        if ($maintenanceRequest->getAttribute('status') === 'completed') {
            return response()->json(['message' => 'Cannot cancel a completed request.'], 400);
        }
    
        $maintenanceRequest->delete();
    
        // Notify the tenant
        $tenant = User::findOrFail($maintenanceRequest->user_id);
        Notification::create([
            'user_id' => $tenant->id,
            'title' => 'Maintenance Request Canceled',
            'message' => 'Your maintenance request has been canceled or rejected by the admin.',
            'is_read' => false,
        ]);
    
        return response()->json(['message' => 'Request canceled successfully.']);
    }
    
    
    public function destroy($id)
    {
        $maintenanceRequest = MaintenanceRequest::find($id);
    
        if (!$maintenanceRequest) {
            return response()->json(['message' => 'Maintenance request not found.'], 404);
        }
    
        // Delete the file if it exists
        if ($maintenanceRequest->file_path) {
            $filePath = str_replace('storage/', 'public/', $maintenanceRequest->file_path);
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
                Log::info("Deleted file: " . $filePath);
            } else {
                Log::warning("File not found for deletion: " . $filePath);
            }
        }
    
        // Delete the maintenance request
        $maintenanceRequest->delete();
    
        return response()->json(['message' => 'Maintenance request deleted successfully.']);
    }
    
            
}
