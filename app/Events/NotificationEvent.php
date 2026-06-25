<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $title;
    public $message;
    public $type;

    public function __construct($title, $message, $type = 'success')
    {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
    }

    public function broadcastOn(): array
    {
        // This targets the exact channel the frontend is listening to
        return [
            new Channel('notifications-channel'),
        ];
    }

    public function broadcastAs(): string
    {
        // This is the exact event name the frontend expects
        return 'NotificationEvent';
    }

    public function broadcastWith(): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
        ];
    }
}