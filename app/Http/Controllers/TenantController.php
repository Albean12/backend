<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Unit;
use App\Models\User;
use App\Mail\PaymentReminder;
use Illuminate\Support\Facades\Mail;
use App\Events\PaymentReminderEvent;
use App\Models\Notification;

class TenantController extends Controller
{
    public function getTenants()
    {
        try {
            $tenants = User::where('role', 'tenant')
                ->leftJoin('applications', 'users.email', '=', 'applications.email')
                ->select(
                    'users.id',
                    'users.name',
                    'users.email',
                    'applications.address',
                    'applications.contact_number',
                    'applications.check_in_date',
                    'applications.duration',
                    'applications.occupation',
                    'applications.reservation_details as unit_code',
                    \DB::raw("CONCAT('" . asset('storage') . "/', applications.valid_id) as valid_id_url")
                )
                ->get();
    
            return response()->json($tenants, 200);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['error' => 'Failed to fetch tenants.'], 500);
        }
    }
    
    
    
    public function getAssignedUnit(Request $request)
    {
        $user = $request->user();
    
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }
    
        $unit = Unit::find($user->unit_id);
    
        if (!$unit) {
            return response()->json(['message' => 'No unit assigned to this tenant.'], 404);
        }
    
        return response()->json($unit, 200);
    }
    

    public function unpaidTenants(Request $request)
    {
        $month = $request->input('month'); // Month filter input (e.g., "02" for February)
    
        $unpaidTenants = User::where('role', 'tenant')
            ->leftJoin('applications', 'users.email', '=', 'applications.email')
            ->leftJoin('payments', function ($join) {
                $join->on('users.id', '=', 'payments.user_id');
            })
            
            ->selectRaw("
                users.id,
                users.name,
                COALESCE(applications.reservation_details, 'N/A') as unit_code,
                applications.check_in_date,
                applications.duration,
                applications.set_price,
                COALESCE(SUM(payments.amount), 0) as total_paid,
                MAX(payments.payment_type) as payment_type,
                MAX(payments.payment_date) as payment_date,
                MAX(payments.reference_number) as reference_number,


                CASE 
                    WHEN MAX(payments.status) = 'Pending' THEN 'Pending'
                    WHEN MAX(payments.status) = 'Confirmed' THEN 'Confirmed'
                    ELSE 'Unpaid'
                END as payment_status

            ")
            ->groupBy(
                'users.id', 'users.name', 'applications.reservation_details', 
                'applications.check_in_date', 'applications.duration', 'applications.set_price'
            )
            

            ->select(
                'users.id',
                'users.name',
                \DB::raw('COALESCE(applications.reservation_details, "N/A") as unit_code'),
                'applications.check_in_date',
                'applications.duration',
                'applications.set_price',
                \DB::raw('COALESCE(SUM(payments.amount), 0) as total_paid'),
                \DB::raw('MAX(payments.status) as payment_status')
            )
            
            ->groupBy(
                'users.id', 'users.name', 'applications.reservation_details', 
                'applications.check_in_date', 'applications.duration', 'applications.set_price'
            )
            ->get()
            ->map(function ($tenant) {
                // Retrieve default unit price based on unit_code
                $unitPrice = Unit::where('unit_code', $tenant->unit_code)->value('price') ?? 0;
            
                // Prioritize set_price over unit_price
                $pricePerMonth = $tenant->set_price ?? $unitPrice;
            
                // Calculate total due and balance
                $totalDue = $pricePerMonth;
                $balance = $totalDue - $tenant->total_paid;
            
                // Calculate due date
                $dueDate = $this->calculateNextDueDate($tenant->check_in_date, $tenant->duration);
            
                $remainingMonths = 0;
            
                if ($tenant->check_in_date && $tenant->duration) {
                    $checkInDate = Carbon::parse($tenant->check_in_date);
                    $endDate = $checkInDate->copy()->addMonths($tenant->duration);
                    $remainingMonths = max($endDate->diffInMonths(Carbon::now()), 0);
                }
            
                // Format receipt_path
                $receiptPath = $tenant->receipt_path
                    ? asset('storage/' . $tenant->receipt_path)
                    : null;
            
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'unit_code' => $tenant->unit_code,
                    'total_due' => $totalDue,
                    'total_paid' => $tenant->total_paid ?? 0,
                    'balance' => max($balance, 0),
                    'due_date' => $dueDate,
                    'status' => $tenant->payment_status ?? 'No Payments',
                    'remaining_months' => $remainingMonths,
                    'remaining_balance' => max($tenant->set_price - $tenant->total_paid, 0) ?? 0,
                    'receipt_path' => $receiptPath, // Correct absolute path
                ];
            });
            
    
            if (!empty($month) && $month !== '00') {
                $unpaidTenants = $unpaidTenants->filter(function ($tenant) use ($month) {
                    // Check if due_date is valid
                    if (empty($tenant['due_date']) || !strtotime($tenant['due_date'])) {
                        return false; // Skip invalid due_date
                    }
            
                    // Safely parse and filter the month
                    return Carbon::parse($tenant['due_date'])->format('m') === str_pad($month, 2, '0');
                });
            }
            
        return response()->json($unpaidTenants->values());
    }
    
    
    private function calculateNextDueDate($checkInDate, $duration)
    {
        if (!$checkInDate) {
            return 'N/A';
        }
    
        $checkIn = Carbon::parse($checkInDate);
    
        // Ensure the first payment is due one month after the check-in date
        $nextDueDate = $checkIn->copy()->addMonth();
    
        // Calculate the final due date based on duration
        $finalDueDate = $checkIn->copy()->addMonths($duration ?? 1);
    
        // If the next due date is greater than the final duration, mark as "Completed"
        return $nextDueDate->greaterThan($finalDueDate) ? 'Completed' : $nextDueDate->toDateString();
    }
    
    
    public function getNotifications(Request $request)
    {
        $user = $request->user(); // Get the authenticated user
    
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    
        // Fetch notifications for this user
        $notifications = \App\Models\Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    
        return response()->json($notifications);
    }
    
    
    public function sendReminder($id)
    {
        $tenant = User::findOrFail($id);
    
        // Fetch tenant's application details
        $application = \App\Models\Application::where('email', $tenant->email)->first();
    
        // Calculate the next due date
        $checkInDate = Carbon::parse(optional($application)->check_in_date ?? now());
        $duration = optional($application)->duration ?? 1; // Duration in months
    
        // Final due date based on duration
        $finalDueDate = $checkInDate->copy()->addMonths($duration);
    
        // Calculate the next due date (monthly payments)
        $currentDate = Carbon::now();
        $monthsElapsed = $currentDate->diffInMonths($checkInDate);
        $nextDueDate = $checkInDate->copy()->addMonths($monthsElapsed + 1);
    
        // Ensure the next due date does not exceed the final due date
        if ($nextDueDate->greaterThan($finalDueDate)) {
            $nextDueDate = $finalDueDate->toDateString();
        } else {
            $nextDueDate = $nextDueDate->toDateString();
        }
    
        // Construct email content
        $messageBody = view('emails.payment_reminder_inline', [
            'tenantName' => $tenant->name,
            'dueDate' => $nextDueDate,
        ])->render();
    
        // Send email
        Mail::send([], [], function ($message) use ($tenant, $messageBody) {
            $message->to($tenant->email)
                    ->subject('Payment Reminder')
                    ->html($messageBody);
        });
        // Create notification
        Notification::create([
            'user_id' => $tenant->id,
            'title' => 'Payment Reminder',
            'message' => "Your payment is due on {$nextDueDate}. Please pay promptly to avoid penalties.",
        ]);
        // Broadcast event
        event(new PaymentReminderEvent($tenant->id, "Dear {$tenant->name}, your payment is due on {$nextDueDate}."));
    
        return response()->json(['message' => "Payment reminder sent to {$tenant->name}."]);
    }
    
}
