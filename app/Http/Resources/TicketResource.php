<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'ticket_code' => $this->ticket_code,
            'status' => strtoupper($this->status),
            'booked_at' => $this->created_at->format('Y-m-d H:i:s'),
            'event_info' => [
                'name' => $this->event->title ?? 'N/A',
                'banner' => $this->event->banner,
                'is_free' => $this->event->is_free,
                'price' => $this->event->price,
                'event_date' => $this->event->date?->format('Y-m-d g:i A'),
                'location' => $this->event->location
            ],
        ];
    }
}
