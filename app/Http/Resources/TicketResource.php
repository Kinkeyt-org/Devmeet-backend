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
            'id'=> $this->id,
            'ticket_code' => $this->ticket_code,
            'status'=> strtoupper($this->string),
            'booked_at'=> $this-> created_at->format('Y-m-d H:i:s'),
            'event_info'=>[
                'name'=> $this->event->title ?? 'N/A',
                'location'=> $this->event->location 
            ],
        ];
    }
}
