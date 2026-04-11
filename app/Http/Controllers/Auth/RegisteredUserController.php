<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')],
            'role'     => ['sometimes', 'string', Rule::in(['attendee', 'organizer'])],
            'password' => ['required', 'confirmed', Password::defaults()],
            'profile_picture' => [
                'required_if:role,organizer',
                'nullable',
                'image',
                'mimes:jpg,png,jpeg,webp',
                'max:2048',
            ],
        ], [
            'profile_picture.required_if' => 'Organizers must upload a profile picture to create events.',
        ]);

        // Determine role first — needed for upload logic
        $role = $request->input('role', 'attendee');

        // Handle S3 upload before user creation, with explicit failure check
        $picturePath = null;
        if ($request->hasFile('profile_picture')) {
            $picturePath = $request->file('profile_picture')->store('avatars', 's3');

            // store() returns false on failure — never save false to the DB
            if ($picturePath === false) {
                return response()->json([
                    'message' => 'Profile picture upload failed. Please try again.',
                ], 500);
            }
        }

        try {
            $user = User::create([
                'name'            => $request->name,
                'email'           => $request->email,
                'password'        => Hash::make($request->string('password')),
                'role'            => $role,
                'profile_picture' => $picturePath,
            ]);
        } catch (\Throwable $e) {
            // Rollback orphaned S3 file if user creation fails
            if ($picturePath) {
                Storage::disk('s3')->delete($picturePath);
            }

            throw $e; // Re-throw so Laravel's handler responds correctly
        }

        event(new Registered($user));

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new UserResource($user),
        ], 201);
    }
}