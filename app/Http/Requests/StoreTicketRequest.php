<?php

namespace App\Http\Requests;

use App\Rules\MaxTicketsPerEvent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        //check if the user is signed before anything
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'number' => 'required|integer|min:1|max:10',// validate the number of tickets applied for
            new MaxTicketsPerEvent($this->route('id')) //when this form is submitted and the validation is done get the wildcard(id) of this incoming https request and give me the actual value eg id =5 then the value is handed to the rule
        ];
    }
}
