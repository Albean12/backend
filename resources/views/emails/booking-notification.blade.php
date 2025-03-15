<!DOCTYPE html>
<html>
<head>
    <title>Booking Notification</title>
</head>
<body>
    <h1>{{ $action === 'confirm' ? 'Booking Confirmed' : 'Booking Canceled' }}</h1>
    <p>Dear {{ $booking->name }},</p>
    <p>
        {{ $action === 'confirm' 
            ? 'We are pleased to confirm your booking.' 
            : 'We regret to inform you that your booking has been canceled.' }}
    </p>
    <p><strong>Booking Details:</strong></p>
    <ul>
        <li>Date: {{ $booking->date_booked ?? 'N/A' }}</li>
        <li>Time: {{ $booking->time_slot ?? 'N/A' }}</li>
        <li>Number of Visitors: {{ $booking->num_visitors }}</li>
    </ul>
    <p>
        {{ $action === 'confirm' 
            ? 'We look forward on seeing you on Seagold Dormitory.' 
            : 'Thank you for your interest in our Dormitory.' }}
</body>
</html>
