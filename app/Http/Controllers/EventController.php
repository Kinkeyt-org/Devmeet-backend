<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventCreationRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate as FacadesGate;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(Request $request)
    {

        $query = Event::with(['user', 'ticketTiers'])->withCount('tickets');
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', $searchTerm)
                    ->orWhere('location', 'LIKE', $searchTerm)
                    ->orWhereHas('user', function ($organizeQuery) use ($searchTerm) {
                        $organizeQuery->where('name', 'LIKE', $searchTerm);
                    });
            });
        }
        $events = $query->latest('date')->paginate(10)->withQueryString();
        return EventResource::collection($events);
    }
    public function show(Event $event)
    {
        //note we only use the request class if a user is submitting a form or typing in something to us
        $event->load(['user', 'ticketTiers', 'tags']);
        return new EventResource($event);
    }
    public function store(EventCreationRequest $request)
    {
        $eventData = $request->validated();
        $eventData['organizer_id'] = $request->user()->id;
        // 1. Handle Banner Upload to S3
       if ($request->hasFile('banner')) {
            $eventData['banner'] = $request->file('banner')->store('events/banners', 's3');
        }
        $newEvent = Event::create($eventData);

        if ($request->has('tags')) {
            $tagIds = [];
            foreach ($request->input('tags') as $tagName) {
                $tag = Tag::firstOrCreate([
                    'name' => trim($tagName),
                    'slug' => Str::slug($tagName)
                ]);
                $tagIds[] = $tag->id;
            }
            $newEvent->tags()->sync($tagIds);
        }
        return new EventResource($newEvent->load('tags'));
    }
    public function update(Request $request, Event $event)
    {

        //check for ownership of event using my event policy which returns a boolean granting access or not
       FacadesGate::authorize('update', $event);

        $changes = $request->validate([
            'title'       => ['string', 'min:5', 'max:50'],
            'description' => ['string', 'max:300'],
            'location'    => ['string'],
            'capacity'    => ['integer'],
            'date'        => ['date'],
            'banner'      => ['nullable', 'image', 'max:2048'] // Validate banner in update too
        ]);

        // 1. If a new banner is uploaded, swap it out on S3
        if ($request->hasFile('banner')) {
            $changes['banner'] = $request->file('banner')->store('events/banners', 's3');
        }

        $event->update($changes);

        // 2. Sync tags in update as well (Essential!)
        if ($request->has('tags')) {
            $tagIds = [];
            foreach ($request->input('tags') as $tagName) {
                $tag = Tag::firstOrCreate([
                    'name' => trim($tagName),
                    'slug' => Str::slug($tagName)
                ]);
                $tagIds[] = $tag->id;
            }
            $event->tags()->sync($tagIds);
        }

        return new EventResource($event->load(['user', 'ticketTiers', 'tags']));
    }
    public  function destroy(Event $event)
    {
        FacadesGate::authorize('delete', $event);
        $event->delete();
        return response()->json([
            'message' => 'Event deleted successfully.',
        ], 200);
    }
}
