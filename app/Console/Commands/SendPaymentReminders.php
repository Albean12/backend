<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Models\User;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Broadcast;

class SendPaymentReminders extends Command
{
    protected $signature = 'payments:send-reminders';
    protected $description = 'Send payment reminders to tenants 3 days before the due date and for overdue payments';

    public function handle()
    {
        $today = Carbon::now()->startOfDay();
        $threeDaysFromNow = $today->copy()->addDays(3);

        // 1. Send reminders for payments due in 3 days
        $this->sendUpcomingPaymentReminders($threeDaysFromNow);

        // 2. Send reminders for overdue payments
        $this->sendOverduePaymentReminders($today);

        return 0;
    }

    private function sendUpcomingPaymentReminders($dueDate)
    {
        $tenants = User::where('role', 'tenant')
            ->whereDoesntHave('payments', function ($query) use ($dueDate) {
                $query->whereDate('payment_period', $dueDate)
                      ->where('status', 'confirmed'); // Ensure only unpaid payments are considered
            })
            ->with('unit')
            ->get();

        foreach ($tenants as $tenant) {
            // Save notification in the database
            Notification::create([
                'user_id' => $tenant->id,
                'title' => 'Payment Reminder',
                'message' => "Your payment is due on {$dueDate->toDateString()}. Submit it before the due date!",
                'is_read' => false,
            ]);

            // Send email reminder
            Mail::raw(
                "Dear {$tenant->name}, your payment is due on {$dueDate->toDateString()}. Please submit it before the due date.",
                function ($message) use ($tenant) {
                    $message->to($tenant->email)->subject('Upcoming Payment Reminder');
                }
            );

            // Log the reminder for the console
            $this->info("Upcoming payment reminder sent to: {$tenant->email}");
        }
    }

    private function sendOverduePaymentReminders($today)
    {
        // Fetch tenants with overdue payments or no payment records at all
        $tenants = User::where('role', 'tenant')
            ->where(function ($query) use ($today) {
                $query->whereHas('payments', function ($subQuery) use ($today) {
                    $subQuery->where('status', '!=', 'confirmed')
                             ->whereDate('payment_period', '<', $today); // Overdue payments
                })
                ->orWhereDoesntHave('payments'); // Tenants with no payment records
            })
            ->with('unit') // Include unit details
            ->get();
    
        foreach ($tenants as $tenant) {
            $message = null;
    
            if ($tenant->payments->isEmpty()) {
                // No payment records exist
                $message = "Your payment for the period {$latestOverdue->payment_period} is overdue. Please pay as soon as possible to avoid penalties.";
            } else {
                // Payments exist but overdue
                $latestOverdue = $tenant->payments->last();
                $message = "Your payment for the period {$latestOverdue->payment_period} is overdue. Please pay as soon as possible to avoid penalties.";
            }
    
            // Save notification in the database
            Notification::create([
                'user_id' => $tenant->id,
                'title' => 'Overdue Payment Reminder',
                'message' => $message,
                'is_read' => false,
            ]);
    
            // Send email reminder
            Mail::raw(
                "Dear {$tenant->name}, {$message}",
                function ($message) use ($tenant) {
                    $message->to($tenant->email)->subject('Overdue Payment Reminder');
                }
            );
    
            // Log the reminder for the console
            $this->info("Overdue payment reminder sent to: {$tenant->email}");
        }
    }
    
}
