<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use app\Http\Resources\UserResource;

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
            'id'=>$this->id,
           'title'=>$this->title,
           'description'=>$this->description,
           'location'=>$this->location,
           'capacity' => $this->capacity,
           'event_date'=> $this->date->format(),
           'created_at'=>$this->created_at->diffforHumans(),
           //returns a simple boolean value
           'is_sold_out'=>$this->capacity <= 0,
           'organizer'=>new UserResource ($this->whenLoaded('organizer'))
        ];
    }
}
