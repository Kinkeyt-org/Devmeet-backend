<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'profile_picture' => [
                'required_if:role,organizer',
                'nullable', // Allows attendees to leave it blank
                'image',
                'mimes:jpg,png,jpeg,webp',
                'max:2048'
            ],
        ], [
            'profile_picture.required_if' => 'Organizers must upload a profile picture to create events.'
        ]);
        
        $picturePath = null;
        if ($request->hasFile('profile_picture')) {
            // This saves the image to our s3 server bucket
           $picturePath = $request->file('profile_picture')->store('avatars', 's3');
        }
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->string('password')),
            'role'     => 'attendee', // Will be updated
           'profile_picture' => $picturePath,
        ]);

        event(new Registered($user));


      
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new UserResource($user),
        ], 201);
       
    }
}
