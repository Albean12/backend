<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class PaymentReminderEvent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $tenantId;
    public $message;

    public function __construct($tenantId, $message)
    {
        $this->tenantId = $tenantId;
        $this->message = $message;
    }

    public function broadcastOn()
    {
        // Broadcast to a tenant-specific channel
        return new Channel("tenant-reminder.{$this->tenantId}");
    }

    public function broadcastWith()
    {
        return ['message' => $this->message];
    }
}
