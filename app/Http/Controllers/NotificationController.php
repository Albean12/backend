<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
    
        $notificationsQuery = Notification::query();
    
        if ($user->role === 'admin') {
            // Admin gets global notifications and their own notifications
            $notificationsQuery->where(function ($query) use ($user) {
                $query->whereNull('user_id') // Global notifications
                      ->orWhere('user_id', $user->id); // Admin-specific notifications
            });
        } else {
            // Tenant gets only their own notifications
            $notificationsQuery->where('user_id', $user->id);
        }
    
        // Retrieve notifications ordered by creation date
        $notifications = $notificationsQuery->orderBy('created_at', 'desc')->get();
    
        return response()->json($notifications);
    }
    
    public function markAsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->update(['is_read' => true]);
    
        return response()->json(['message' => 'All notifications marked as read']);
    }
    

    public function destroy($id)
    {
        $notification = Notification::findOrFail($id);
    
        if ($notification->user_id === auth()->id()) {
            $notification->delete();
            return response()->json(['message' => 'Notification deleted successfully']);
        }
    
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    

    public function createBookingNotification($bookingId)
    {
        $booking = DB::table('booked_tour')->where('id', $bookingId)->first();
    
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }
    
        Notification::create([
            'user_id' => auth()->id(), // Assuming admin ID for now
            'message' => "New booking made by {$booking->name} for {$booking->date_booked} at {$booking->time_slot}.",
            'is_read' => false,
        ]);
    
        return response()->json(['message' => 'Notification created successfully']);
    }
    
}
