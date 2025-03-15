<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $action;

    /**
     * Create a new message instance.
     *
     * @param $booking
     * @param $action
     */
    public function __construct($booking, $action)
    {
        $this->booking = $booking;
        $this->action = $action;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = $this->action === 'confirm' ? 'Booking Confirmation' : 'Booking Cancellation';

        return $this->subject($subject)
                    ->view('emails.booking-notification');
    }
}