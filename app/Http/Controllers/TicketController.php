<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function store(Request $request, $id)
    {
        $event = Event::find($id);
        if (!$event) {
            return response()->json([
                'message' => 'This event does not exist'
            ], 404);
        }

        if ($event->capacity <=  0) {
            return response()->json([
                'message' => 'Sorry this event is sold out',
            ], 401);
        };
        Ticket::create([
            'ticket_code' => Str::random(10),
            'event_id' => $event->id,
            'attendee_id' => $request->user()->id,
            'status' => 'booked'
        ]);
        $ticketdetails= Ticket::where('attendee_id', $request->user()->id)->get();
        //for idempotency
        //when this runs laravel immediately decrements what it is your database immediately and not bring it into php memory it is better than doing the math here
        $event->decrement('capacity');


        return response()->json([
            'message' => 'Ticket has been created',
            'ticket-details'=> $ticketdetails,
            'capacity-left' => $event->capacity,
        ], 201);
    }
    public function index(Request $request)
    {
        $user = $request->user()->id;
        //we need to load the associated event with the ticket too so the frontend can style

        //okay so Ticket::with goes into the ticket model and checks if there is a method name event and notes the relationship. It then loads the all the tickets with the attendee id the same as the person who requested it. It then loads it with the associated event
        $allTickets = Ticket::with('event')->where('attendee_id', $user)->get();
        return response()->json([
            'message' => 'Tickets fetched Successfully',
            'data' => $allTickets
        ]);
    }
    public function update(Request $request, $id)
    {
        $ticket = Ticket::findorfail($id);
        if ($ticket->attendee_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized you cannot cancel a ticket you do not own',
            ], 403);
        }
        if ($ticket->status === 'cancelled') {
            return response()->json([
                'message' => 'This Ticket has already been cancelled',
            ]);
        }
        $ticket->status = 'cancelled';
        $ticket->save();

        $event = Event::findorFail($ticket->event_id);
        $event->increment('capacity');
        return response()->json([
            'message' => 'Ticket has been successfully cancelled',
            'capacity_left' => $event->capacity
        ], 200);
    }
}
