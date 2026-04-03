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
        // Only allow logged-in users who have the 'organizer' role
        return $this->user() && $this->user()->role === 'organizer';
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
                'integer',
                'min:1'
            ],
            'banner' => [
                'required',
                'file',
                'image', // Ensures Laravel verifies the file's binary content is actually an image
                'mimes:jpeg,png,jpg,webp', // Only allow these specific modern web formats
                'max:2048', // Maximum file size in Kilobytes (2048 KB = 2 Megabytes)
                'dimensions:min_width=800,min_height=400'
            ],
            'tags' => ['nullable', 'array', 'max:5'],
            'tags.*' => ['string', 'max:20', 'distinct', 'alpha_num'],
            'date' => [
                'required',
                'date',
                'after:today'
            ]
        ];
    }
    public function messages(): array
    {
        return [
            'banner.dimensions' => 'The banner must be at least 800x400 pixels (landscape).',
            'date.after' => 'The event date must be a future date.',
            'tags.*.max' => 'Each tag must be 20 characters or less.',
        ];
    }
}
