<h1>Reminder: Your Tour Booking is Today</h1>
<p>Dear {{ $booking->name }},</p>
<p>This is a friendly reminder that your tour booking is scheduled for today:</p>
<ul>
    <li><strong>Date:</strong> {{ $booking->date_booked }}</li>
    <li><strong>Time:</strong> {{ $booking->time_slot }}</li>
</ul>
<p>We look forward to seeing you!</p>
<p>Thank you,<br>The Tour Team</p>
