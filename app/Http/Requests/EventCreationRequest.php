<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class EventCreationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (Auth::check());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
       
        return [
           
            'title' => [
                'required',
                'string',
                'min:5',
                'max:50'
            ],
            'description' => [
                'required',
                'string',
                'max:300',
            ],
            'location' => [
                'required',
                'string',
            ],
            'capacity' => [
                'required',
                'integer'
            ],
            'date' => [
                'required',
                'date'
            ]
        ];
    }
}
