<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function store(StoreTicketRequest $request, $id)
    {
        //validation first
        $ticketnumber = $request->validated();
        //then we save the integer inside the validated array in a variable
        $count = $ticketnumber['number'];
        //Find the event the user even selected
        $event = Event::find($id);
        //does the event even exist
        if (!$event) {
            return response()->json([
                'message' => 'This event does not exist'
            ], 404);
        }



        //here we are using a try and catch method because we are updating our databse with multiple entries so we have to check for errors 
        try {
            DB::beginTransaction();
            //begin the transaction which is our vault
            //find the event where the id of the event is eqaul to the one the user gave us and lock it for update for the first person to interact with it
            $event = Event::where('id', $id)->lockForUpdate()->first();
            //now that the event cant be tampered with we check how many seats are left for this event to event give out is it less than the amount we need to buy
            if ($event->capacity < $count) {
                return response()->json([
                    'message' => 'We dont have enough tickets'
                ], 403);
            }
            //if not less create two variables and prepare them to hold arrays
            $ticketdetails = [];
            $codes = [];
            //let i=0 as long as i is less than the amount of tickets we want to buy load up the array with each row being a new ticket
            for ($i = 0; $count > $i; $i++) {
                //Every time the loop runs, the line $ticketdetails[] = [...] acts like a "Push" command.
                // 1st Loop: It takes the 1st ticket's data and puts it in position 0 of the tray.
                // 2nd Loop: It takes the 2nd ticket's data and puts it in position 1 of the tray.
                // 3rd Loop: It takes the 3rd ticket's data and puts it in position 2.
                $Singlecode = Str::random(10); //save a random 10 letter string inside of the variable 
                $codes[] = $Singlecode; //put the random id generated into my array and keep arranging it in my array so the second time the loop runs it pushes the last value forward to make room for the next one
                $ticketdetails[] = [
                    'id' => Str::uuid()->toString(),
                    'ticket_code' => $Singlecode, // set my ticket code to the random generated string
                    'event_id' => $event->id,
                    'attendee_id' => $request->user()->id,
                    'status' => 'booked',
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            // Perform the Bulk Insert insert all the data at once.
            Ticket::insert($ticketdetails);

            //for idempotency when this runs laravel immediately decrements what it is your database immediately and not bring it into php memory it is better than doing the math here, it decreases by the integer stored in the $count variable
            $event->decrement('capacity', $count);
            //save changes into db
            DB::commit();

            // to allow users to be able to see all their purchased tickets they just bought we need to check tickets where th event_id is equal to the $event->id and the same for user. sort it with the list we got from the array. any code on the list that is inside the 'ticket_code' will be gotten
            $ticketdetails = Ticket::where('event_id', $event->id)->where('attendee_id', $request->user()->id)->whereIn('ticket_code', $codes)->get();

            //return to the frontend

            return response()->json([
                'message' => 'Ticket has been created',
                'ticket-details' => TicketResource::collection($ticketdetails), //this is the format that our json ticket data will look like
                'capacity-left' => $event->capacity - $count, //this is number of tickets left||not sure if this is necessary tbh
            ], 201);
        } catch (\Exception $e) {
            //if the server crashes and for some reason the code doesnt fully execute, the database will be rolled back how to how it was before the transaction
            DB::rollBack();
            Log::error("Ticket Purchase Failed: " . $e->getMessage()); //log the error so we know what went wrong.
            return response()->json([
                "message" => "Error: " . $e->getMessage(),
                "line" => $e->getLine() //a message to our frontend
            ], 500);
        }
    }
    //show all tickets that user owns
    public function index(Request $request)
    {
        //get the user id
        $user = $request->user()->id;
        //load all tickets with all the related events where the attendee id = user's id and get it
        $allTickets = Ticket::with('event')->where('attendee_id', $user)->get();
        //return it to our frontend and format it with our resource
        return response()->json([
            'message' => 'Tickets fetched Successfully',
            'data' => TicketResource::collection($allTickets)
        ]);
    }
    //cancel a ticket
    public function update(Request $request, $id)
    {
        //find the ticket you want to cancel 
        $ticket = Ticket::findorfail($id);
        // if the person trying to cancel the cicket does not have the same id as the id of the person that own's the ticket show them the message and return an error
        if ($ticket->attendee_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized you cannot cancel a ticket you do not own',
            ], 403);
        }
        //if ticket is already cancelled tell them 
        if ($ticket->status === 'cancelled') {
            return response()->json([
                'message' => 'This Ticket has already been cancelled',
            ], 400);
        }
        //when altering a the database where two tables rely on each other start a try and catch method
        try {
            DB::beginTransaction();
            //start the vault
            $ticket->status = 'cancelled'; //change the status
            $ticket->save(); //save it 
            //find the event where the id is the same as the event id on the ticket and then lock it for update to the first person
            $event = Event::where('id', $ticket->event_id)->lockForUpdate()->first();
            //increase the capcity of the event
            $event->increment('capacity');
            return response()->json([
                'message' => 'Ticket has been successfully cancelled',
                'capacity_left' => $event->capacity //tell us the capacity left
            ], 200);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ticket Cancellation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'system error during ticket cancellation'
            ], 500);
        }
    }
    public function show($id)
    {
        //show a singular ticket
        //ticket where the event where the selected id is found
        $ticket = Ticket::with('event')->findOrFail($id);
        //return a formatted view of it
        return new TicketResource($ticket);
    }
}
