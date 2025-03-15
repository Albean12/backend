<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Mail\BookingNotification;
use App\Models\TourAvailability;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\Notification;

class TourAvailabilityController extends Controller
{
    public function toggleAvailability(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'time' => 'required|string',
            'status' => 'required|in:available,unavailable',
        ]);

        // Find or create the availability record
        $availability = TourAvailability::firstOrCreate(
            ['date' => $validated['date'], 'time' => $validated['time']],
            ['status' => $validated['status']]
        );

        // Update the status if it already exists
        $availability->status = $validated['status'];
        $availability->save();

        return response()->json(['success' => true, 'availability' => $availability]);
    }

    public function makeAllUnavailable(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);
    
        $date = $validated['date'];
    
        // Update all time slots for the given date to "unavailable"
        TourAvailability::where('date', $date)->update(['status' => 'unavailable']);
    
        return response()->json(['success' => true, 'message' => 'All slots marked as unavailable']);
    }

    public function getBookings()
    {
        $bookings = DB::table('booked_tour')
            ->select('id', 'date_booked as date', 'time_slot as time', 'name')
            ->get()
            ->map(function ($booking) {
                $booking->date = $booking->date ?? 'N/A';
                $booking->time = $booking->time ?? 'N/A';
                $booking->name = $booking->name ?? 'Unknown';
                return $booking;
            });
    
        return response()->json(['bookings' => $bookings]);
    }

    public function confirmBooking($id)
    {
        // Fetch the booking from the database
        $booking = DB::table('booked_tour')->where('id', $id)->first();

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        // Update booking status to "Confirmed"
        DB::table('booked_tour')->where('id', $id)->update(['status' => 'Confirmed']);

        // Send confirmation email
        Mail::to($booking->user_email)->send(new BookingNotification($booking, 'confirm'));

        return response()->json(['success' => true, 'message' => 'Booking confirmed.']);
    }

    public function cancelBooking($id)
    {
        // Fetch the booking from the database
        $booking = DB::table('booked_tour')->where('id', $id)->first();
    
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }
    
        // Reset the availability of the time slot
        $availability = TourAvailability::where('date', $booking->date_booked)
            ->where('time', $booking->time_slot)
            ->first();
    
        if ($availability) {
            $availability->status = 'available'; // Change status back to 'available'
            $availability->save(); // Save the updated status
        }
    
        // Delete the booking
        DB::table('booked_tour')->where('id', $id)->delete();
    
        // Optional: Add logging for successful cancellation
        \Log::info('Booking canceled and time slot made available.', [
            'bookingId' => $id,
            'date' => $booking->date_booked,
            'time' => $booking->time_slot,
        ]);
    
        return response()->json(['success' => true, 'message' => 'Booking canceled and time slot made available.']);
    }
    
    
    public function getAvailability()
    {
        $availability = TourAvailability::all();
        return response()->json(['availability' => $availability]);
    }

    public function getSlots(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);
    
        $slots = TourAvailability::where('date', $validated['date'])->get(['time', 'status']);
    
        return response()->json(['slots' => $slots]);
    }
    

    public function getAvailableDates()
    {
        $dates = TourAvailability::where('status', 'available')
            ->pluck('date')
            ->unique()
            ->values();

        return response()->json(['dates' => $dates]);
    }
    public function getCalendar(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:1900|max:2100',
        ]);
    
        $month = $validated['month'];
        $year = $validated['year'];
    
        $startOfMonth = now()->setDate($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
    
        $dates = collect();
    
        foreach (range(1, $endOfMonth->day) as $day) {
            $currentDate = $startOfMonth->copy()->setDay($day)->format('Y-m-d');
    
            // Fetch slots for the current date from the database
            $slots = TourAvailability::where('date', $currentDate)->get(['time', 'status']);
    
            $status = $slots->contains('status', 'available') ? 'available' : 'unavailable';
    
            $dates->push([
                'date' => $currentDate,
                'status' => $status,
                'slots' => $slots,
            ]);
        }
    
        return response()->json(['calendar' => $dates]);
    }
    public function bookTour(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
    
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:15',
            'num_visitors' => 'required|integer|min:1',
            'date_booked' => 'required|date',
            'time_slot' => 'required|string',
        ]);
    
        try {
            // Check slot availability
            $availability = TourAvailability::where('date', $validated['date_booked'])
                ->where('time', $validated['time_slot'])
                ->where('status', 'available')
                ->first();
    
            if (!$availability) {
                return response()->json(['error' => 'Slot unavailable'], 400);
            }
    
            // Save booking
            $bookingId = DB::table('booked_tour')->insertGetId([
                'user_email' => auth()->user()->email,
                'name' => $validated['name'],
                'phone_number' => $validated['phone_number'],
                'num_visitors' => $validated['num_visitors'],
                'date_booked' => $validated['date_booked'],
                'time_slot' => $validated['time_slot'],
            ]);
    
            // Mark slot as booked
            $availability->update(['status' => 'booked']);
    
            // Trigger notification for admin
            $this->createBookingNotification($bookingId);
    
            return response()->json(['success' => true, 'message' => 'Tour booked successfully.']);
        } catch (\Exception $e) {
            Log::error('Error booking tour: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to book tour.'], 500);
        }
    }
    public function createBookingNotification($bookingId)
    {
        // Fetch booking details
        $booking = DB::table('booked_tour')->where('id', $bookingId)->first();
    
        if (!$booking) {
            \Log::error('Failed to create booking notification: Booking not found.', ['id' => $bookingId]);
            return;
        }
    
        // Fetch all admin users
        $admins = User::where('role', 'admin')->get();
    
        if ($admins->isEmpty()) {
            \Log::error('Failed to create booking notification: No admins found.');
            return;
        }
    
        // Create notifications for all admins
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id, // Assign to each admin
                'title' => 'New Tour Booking',
                'message' => "A new booking has been made by {$booking->name} for {$booking->date_booked} at {$booking->time_slot}.",
                'is_read' => false,
            ]);
        }
    
        \Log::info('Booking notifications created successfully for all admins.', ['bookingId' => $bookingId]);
    }
    
}