<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

      return response()->json([
            'message' => 'Login successful.',
            'user'    => $user->only(['id', 'name', 'email', 'role', 'profile_picture']),
            'token'   => $token,
        ]);
    }

    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = User::findOrFail(Auth::id());

        $validated = $request->validate([
            'name'  => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes', 'string', 'lowercase',
                'email', 'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'role' => ['sometimes', 'string', Rule::in(['attendee', 'organizer'])],
            'profile_picture' => [
                'required_if:role,organizer',
                'nullable',
                'image',
                'mimes:jpg,png,jpeg,webp',
                'max:2048',
            ],
        ], [
            'profile_picture.required_if' => 'Organizers must upload a profile picture.',
        ]);

        if ($request->hasFile('profile_picture')) {
            // Delete old picture from S3 before uploading new one
            if ($user->profile_picture) {
                Storage::disk('s3')->delete($user->profile_picture);
            }

            $path = $request->file('profile_picture')->store('avatars', 's3');

          
            if ($path === false) {
                return response()->json([
                    'message' => 'Profile picture upload failed. Please try again.',
                ], 500);
            }

            $validated['profile_picture'] = $path;
        } else {
            unset($validated['profile_picture']);
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user'    => $user->only(['id', 'name', 'email', 'role', 'profile_picture']),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}