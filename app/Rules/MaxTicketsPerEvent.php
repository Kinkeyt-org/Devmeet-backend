<?php
namespace App\Rules;

use App\Models\Ticket;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;


// lets understand rules. I am already familiar with validation checks
// like 'required|string' but they are not smart enough to check for the number of a specific user's count.


class MaxTicketsPerEvent implements ValidationRule
{
    protected $eventId;

    // since this rule is a seperate class. it doesnt automatically know which event the user is trying to book. this variable acts like this rule's internal memory. it hold onto the Id so it can be used later in the database query.
    // it receives the id from the request and then validates whatever the id garabs according top the rule swe specify here

    
    public function __construct($eventId)
    {
        $this->eventId = $eventId;
    }
//this is the entry point. when you call new MaxTicketsPerEvent($id) in your form request the id from the url is passed here and saved into the internal memory we just mentioned.
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // $attribute is the name of the field we want to validate in this case ['number']
        // $value is the actual data the user sent
        // $fail is a red flag function is you call it validation stops and snds an error back to the user
        // 2. Check how many tickets where the event_id related to th e ticket is the same with the one our request just sent to us and also check if the attendee_id on the ticket is the same as the person currently logged in and count the number of them and store them in a variable
        $alreadyOwned = Ticket::where('event_id', $this->eventId)
            ->where('attendee_id', Auth::id())
            ->count();

        // 3. The logic check: (Existing + Requested)
        if (($alreadyOwned + $value) > 10) {
            $fail(" You already own {$alreadyOwned} tickets. You can only own a maximum of 10 total.");
        }
    }
}