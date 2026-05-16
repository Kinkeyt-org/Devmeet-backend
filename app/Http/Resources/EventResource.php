<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class EventResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'capacity' => $this->capacity,
            'banner' => $this->banner,
            'is_free' => $this->is_free,
            'category' => 'required|string|max:100',
            'price' => $this->price,
            
            // FIX: Added the nullsafe operator (?) to both date methods
            'event_date' => $this->date?->format('Y-m-d g:i A'),
            'event_date_human' => $this->date?->diffForHumans(),
            
            // Optional but recommended: protect created_at just in case!
            'created_at' => $this->created_at?->diffForHumans(),
            
            'is_sold_out' => $this->tickets_count >= $this->capacity,
            'organizer' => new UserResource($this->whenLoaded('organizer'))
        ];
    }
}