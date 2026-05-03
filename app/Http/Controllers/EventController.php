<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventCreationRequest;
use App\Http\Requests\EventUpdateRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\Tag;
use App\Services\HelperFunction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);

        $query = Event::query()
            ->with([
                'user:id,name',
                'ticketTiers:id,event_id,name,price,capacity',
                'tags:id,name,slug',
            ])
            ->withCount('tickets')
            ->whereDate('date', '>=', today()); // ← hide past events

        // Category filter
        $query->when($request->filled('category'), function ($q) use ($request) {
            $q->where('category', $request->category);
        });

        // Search
        $query->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            $q->where(function ($inner) use ($searchTerm) {
                $inner->where('title', 'LIKE', $searchTerm)
                    ->orWhere('location', 'LIKE', $searchTerm)
                    ->orWhereHas('user', fn($u) => $u->where('name', 'LIKE', $searchTerm));
            });
        });

        // Tag filter (by slug)
        $query->when($request->filled('tags'), function ($q) use ($request) {
            $tagSlugs = array_map('trim', explode(',', $request->tags));

            $q->whereIn('id', function ($sub) use ($tagSlugs) {
                $sub->select('event_id')
                    ->from('event_tag')
                    ->join('tags', 'tags.id', '=', 'event_tag.tag_id')
                    ->whereIn('tags.slug', $tagSlugs);
            });
        });
        $sort = $request->get('sort', 'recent');
        if ($sort == 'recent') {
            $events = $query->latest('date')
                ->paginate($perPage)
                ->withQueryString();
        } else {
            $events = $query->orderBy('date')
                ->paginate($perPage)
                ->withQueryString();
        }
        return EventResource::collection($events);
    }

    public function show(Event $event)
    {
        $event->load([
            'user:id,name',
            'ticketTiers:id,event_id,name,price,capacity',
            'tags:id,name,slug',
        ]);

        return new EventResource($event);
    }

    public function store(EventCreationRequest $request)

    {
        HelperFunction::attachLogData($request, [
            'level' => 'info',
            'message' => 'Inspecting incoming frontend payload for location data',
            'incoming_data' => $request->all(),
        ]);

        $data = $request->validated();
        $data['organizer_id'] = $request->user()->id;

        if ($request->hasFile('banner')) {
            $path = $request->file('banner')->store('events/banners', 's3');

            if (!$path) {
                return response()->json(['message' => 'Banner upload failed'], 500);
            }

            $data['banner'] = $path;
        }

        $event = DB::transaction(function () use ($data, $request) {
            $event = Event::create($data);

            // FIX: Use has() instead of filled(), and format the tags properly
            if ($request->has('tags')) {
                $this->syncTags($event, $request->input('tags'));
            }

            return $event;
        });
        event(new \App\Events\EventCreated($event));
        return (new EventResource($event->load([
            'user:id,name',
            'tags:id,name,slug'
        ])))->response()->setStatusCode(201);
    }

    public function update(Request $request, Event $event)
    {
        Gate::authorize('update', $event);

        $changes = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'location' => 'sometimes|string|max:255',
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
            'capacity' => 'sometimes|integer|min:1',
            'category' => 'sometimes|string|max:100',
            'date' => 'sometimes|date|after_or_equal:today',
            'is_free' => 'sometimes|boolean',
            'price' => 'nullable|numeric|min:0',
            'banner' => 'nullable|image|max:2048',
            // FIX: Removed strict 'array' validation so it accepts FormData strings
            'tags' => 'nullable'
        ]);

        if ($request->hasFile('banner')) {
            $path = $request->file('banner')->store('events/banners', 's3');

            if (!$path) {
                return response()->json(['message' => 'Banner upload failed'], 500);
            }

            if ($event->banner) {
                try {
                    Storage::disk('s3')->delete($event->banner);
                } catch (\Exception $e) {
                    report($e);
                }
            }

            $changes['banner'] = $path;
        }

        DB::transaction(function () use ($event, $changes, $request) {
            $event->update($changes);

            // FIX: Use has() instead of filled()
            if ($request->has('tags')) {
                $this->syncTags($event, $request->input('tags'));
            }
        });

        return new EventResource($event->load([
            'user:id,name',
            'ticketTiers:id,event_id,name,price,capacity',
            'tags:id,name,slug',
        ]));
    }

    // FIX: Removed strict "array" type hint so it accepts strings from FormData
    private function syncTags(Event $event, $tagsInput): void
    {
        // If the tags came in as a comma-separated string from FormData, turn it into an array
        if (is_string($tagsInput)) {
            $tagNames = explode(',', $tagsInput);
        } else {
            // Otherwise, it's already an array (or null)
            $tagNames = (array) $tagsInput;
        }

        $tagNames = array_unique(array_filter(array_map('trim', $tagNames)));

        $tagIds = collect($tagNames)->map(function ($name) {
            return Tag::firstOrCreate(
                ['name' => $name],
                ['slug' => Str::slug($name)]
            )->id;
        })->toArray();

        // This will successfully clear tags if an empty array/string was passed!
        $event->tags()->sync($tagIds);
    }



    public function destroy(Event $event)
    {
        Gate::authorize('delete', $event);

        if ($event->banner) {
            try {
                Storage::disk('s3')->delete($event->banner);
            } catch (\Exception $e) {
                report($e);
            }
        }

        $event->delete();

        return response()->json(['message' => 'Event deleted successfully.']);
    }
}
