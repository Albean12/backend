<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\BookingReminder;

class SendBookingReminders extends Command
{
    protected $signature = 'booking:send-reminders';
    protected $description = 'Send email reminders to users for their bookings scheduled for today';

    public function handle()
    {
        $today = now()->format('Y-m-d');
        $bookings = DB::table('booked_tour')
            ->whereDate('date_booked', $today)
            ->get();

        foreach ($bookings as $booking) {
            if ($booking->user_email) {
                Mail::to($booking->user_email)->send(new BookingReminder($booking));
                $this->info("Reminder sent to {$booking->user_email}");
            }
        }

        return 0;
    }
}
