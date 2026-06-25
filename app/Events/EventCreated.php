<?php

namespace App\Events;

use App\Models\Event;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    
    public $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('events'),
        ];
    }
    public function broadcastAs(): string
{
    return 'event.created';
}
public function broadcastWith(): array
{
    return [
        'id' => $this->event->id,
        'title' => $this->event->title,
        'user' => $this->event->user, 
        'tags' => $this->event->tags,
        'created_at' => $this->event->created_at->format('Y-m-d H:i:s'),
        'message' => 'A new event was just created!'
    ];
}
}
