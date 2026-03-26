<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventCreationRequest;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        return Event::orderBy('date', 'asc')->paginate(10);
    }
    public function show($id)
    {
        //note we only use the request class if a user is submitting a form or typing in something to us
        $eventfinder = Event::findorFail($id);
        return response([
            'message' => 'Found Event details',
            'data' => [
                'details' => $eventfinder
            ]
        ]);
    }
    public function store(EventCreationRequest $request)
    {
        $event = $request->validated();
        $event['organizer_id'] = $request->user()->id;
        $newEvent = Event::create($event);
        return response()->json([
            'message' => 'Event created successfully',
            'details' => $newEvent
        ], 201);
    }
    public function update(Request $request, $id)
    {
        // Find the event first (Throws 404 automatically if it doesn't exist)
        $eventfinder = Event::findorfail($id);
        // 2. Check ownership using the securely authenticated user's ID
        if ($request->user()->id !== $eventfinder->organizer_id) {
            return response()->json([
                'message' => 'You cannot make changes to this event because it does not belong to you',
            ], 403);
        }
        // 3. Now that we know they own it, validate the incoming data
        $changes =  $request->validate([
            'title' => [

                'string',
                'min:5',
                'max:50'
            ],
            'description' => [

                'string',
                'max:300',
            ],
            'location' => [

                'string',
            ],
            'capacity' => [

                'integer'
            ],
            'date' => [

                'date'
            ]
        ]);

        $eventfinder->update($changes);
        return response()->json([
            'message' => 'success',

        ], 200);
    }
    public  function destroy(Request $request, $id)
    {

        $eventfinder = Event::findorFail($id);
        if ($request->user()->id !== $eventfinder->organizer_id) {
            return response()->json([
                'message' => 'You cannot delete this event because it does not belong to you',
            ], 403);
        }
        $eventfinder->delete();
        return response()->json([
            'message' => 'Event deleted successfully.',
        ], 200);
    }
}
