<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Get authenticated user's profile.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ]);
    }

    /**
     * Update authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->name = $validated['name'];

        // Update phone ONLY if currently null
        if (is_null($user->phone) && isset($validated['phone'])) {
            $user->phone = $validated['phone'];
        }

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Profile updated',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ]
        ]);
    }
}
