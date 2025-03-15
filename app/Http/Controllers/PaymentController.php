<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
class PaymentController extends Controller
{

    public function unpaidTenants()
    {
        $currentMonth = now()->format('F');
    
        // Fetch tenants who have not paid for the current month
        $unpaidTenants = User::where('role', 'tenant')
            ->whereDoesntHave('payments', function ($query) use ($currentMonth) {
                $query->where('payment_period', $currentMonth)->where('status', 'Confirmed');
            })
            ->with('unit')
            ->get();
    
        return response()->json($unpaidTenants);
    }
    
    // Store Payment
public function store(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json(['message' => 'Unauthorized.'], 401);
    }

    $request->validate([
        'amount' => 'required|numeric',
        'payment_type' => 'required|string|max:50',
        'reference_number' => 'nullable|string|max:100',
        'payment_for' => 'required|date', // Ensure this is validated properly
        'receipt' => 'nullable|file|mimes:png,jpg,jpeg,pdf|max:2048',
    ]);

    $receiptPath = null;
    if ($request->hasFile('receipt')) {
        $receiptPath = $request->file('receipt')->store('uploads/receipts', 'public');
    } else {
        $receiptPath = null;
    }
    
    $payment = Payment::create([
        'user_id' => $user->id,
        'unit_id' => $user->unit_id,
        'amount' => $request->amount,
        'payment_type' => $request->payment_type,
        'reference_number' => $request->reference_number,
        'payment_period' => $request->payment_for,
        'receipt_path' => $receiptPath, // Save only 'uploads/receipts/filename.jpg'
        'status' => 'pending',
    ]);
    

    return response()->json(['message' => 'Payment created successfully!', 'payment' => $payment]);
}

    
public function updateSpecificPayment($id)
{
    $payment = Payment::where('id', $id)->where('status', 'Pending')->firstOrFail();
    $payment->update(['status' => 'Confirmed']);

    return response()->json(['message' => 'Payment confirmed successfully!', 'payment' => $payment]);
}

public function confirmLatestPayment($user_id)
{
    $payment = Payment::where('user_id', $user_id)
        ->where('status', 'Pending')
        ->latest('payment_date') // Fetch the most recent pending payment
        ->first();

    if (!$payment) {
        return response()->json(['message' => 'No pending payment found for this user.'], 404);
    }

    $payment->update(['status' => 'Confirmed']);

    return response()->json(['message' => 'Payment confirmed successfully!', 'payment' => $payment]);
}

public function rejectLatestPayment($user_id)
{
    $payment = Payment::where('user_id', $user_id)
        ->where('status', 'Pending')
        ->latest('payment_date') // Fetch the most recent pending payment
        ->first();

    if (!$payment) {
        return response()->json(['message' => 'No pending payment found for this user.'], 404);
    }

    $payment->update(['status' => 'Rejected']);

    return response()->json(['message' => 'Payment rejected successfully!', 'payment' => $payment]);
}

public function updateStatus($user_id)
{
    // Find the pending payment for the current month
    $currentMonth = now()->format('Y-m'); // e.g., "2024-06"
    
    $payment = Payment::where('user_id', $user_id)
                ->where('status', 'Pending')
                ->where('payment_period', $currentMonth)
                ->first();

    if (!$payment) {
        return response()->json(['message' => 'No pending payment found for the current month.'], 404);
    }

    $payment->update(['status' => 'Confirmed']);

    return response()->json([
        'message' => 'Payment confirmed successfully!',
        'payment' => $payment,
    ]);
}



    public function rejectPayment($user_id)
    {
        $payment = Payment::findOrFail($user_id);
        $payment->update(['status' => 'Rejected']);
    
        return response()->json(['message' => 'Payment rejected successfully!']);
    }
        
    // Fetch All Payments
    public function index(Request $request)
    {
        // Query Parameters
        $status = $request->query('status'); // Pending, Confirmed, Rejected
        $startDate = $request->query('start_date'); // Start Date
        $endDate = $request->query('end_date'); // End Date
    
        // Build the query
        $query = Payment::with(['user', 'unit']);
    
        if ($status) {
            $query->where('status', $status);
        }
    
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
    
        $payments = $query->get();
    
        // Format response
        $formattedPayments = $payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'tenant_name' => $payment->user?->name ?? 'N/A',
                'unit_code' => $payment->unit?->unit_code ?? 'N/A',
                'amount' => $payment->amount,
                'payment_type' => $payment->payment_type,
                'reference_number' => $payment->reference_number,
                'submitted_at' => $payment->created_at,
                'status' => $payment->status,
                'receipt_path' => $payment->receipt_path
                    ? asset('storage/' . $payment->receipt_path)
                    : null, // Correct absolute URL
            ];
        });
        
        return response()->json($formattedPayments);
    }
    
    public function paymentSummary()
    {
        try {
            $totalConfirmed = Payment::where('status', 'Confirmed')->sum('amount');
            $pendingPayments = Payment::where('status', 'Pending')->count();
    
            // Dynamically calculate the outstanding balance for tenants
            $outstandingBalance = User::where('role', 'tenant')
                ->with('unit', 'payments')
                ->get()
                ->sum(function ($tenant) {
                    $totalDue = optional($tenant->unit)->price * optional($tenant->application)->duration ?? 1;
                    $totalPaid = $tenant->payments->where('status', 'Confirmed')->sum('amount');
                    return max($totalDue - $totalPaid, 0); // Ensure non-negative balance
                });
    
            return response()->json([
                'total_confirmed' => $totalConfirmed,
                'pending_payments' => $pendingPayments,
                'outstanding_balance' => $outstandingBalance,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch summary', 'details' => $e->getMessage()], 500);
        }
    }
    
    
        
    public function getTenantPayments($id)
    {
        // Fetch tenant's user details
        $user = \App\Models\User::findOrFail($id);
    
        // Fetch tenant's payments with the associated unit
        $payments = Payment::where('user_id', $id)->with('unit')->get();
    
        // Retrieve check-in date and duration using the tenant's email
        $application = \App\Models\Application::where('email', $user->email)->first();
    
        $checkInDate = $application && $application->check_in_date
            ? Carbon::parse($application->check_in_date)->startOfDay()
            : Carbon::now()->startOfDay();
    
        $duration = $application->duration ?? 1;
    
        // Get the last confirmed payment period
        $lastPayment = Payment::where('user_id', $id)
            ->where('status', 'Confirmed')
            ->orderBy('payment_period', 'desc')
            ->first();
    
        // Determine the next due date
        if ($lastPayment) {
            $nextDueDate = Carbon::parse($lastPayment->payment_period)->addMonth();
        } else {
            $nextDueDate = $checkInDate->copy()->addMonth();
        }
    
        // Ensure the due date does not exceed the contract duration
        $finalDueDate = $checkInDate->copy()->addMonths($duration);
        if ($nextDueDate->greaterThan($finalDueDate)) {
            $nextDueDate = $finalDueDate;
        }
    
        return response()->json([
            'payments' => $payments,
            'due_date' => $nextDueDate->toDateString(),
            'check_in_date' => $checkInDate->toDateString(),
            'duration' => $duration,
            'unit_price' => $user->unit ? $user->unit->price : 0, // Fetch the unit price
        ]);
    }
    

}
