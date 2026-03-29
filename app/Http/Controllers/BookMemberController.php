<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\BookInvitation;
use App\Notifications\InvitationReceived;

class BookMemberController extends Controller
{
    public function invite(Request $request, $slug)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'role' => 'nullable|in:editor,viewer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $book = Book::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        // Check if the user is authorized to send an invite (only owner)
        $member = \Illuminate\Support\Facades\DB::table('book_user')
            ->where('book_id', $book->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$member) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have access to this book'
            ], 403);
        }

        $role = $member->role;
        logger('ROLE: ' . $role);

        if ($role !== 'owner') {
            return response()->json([
                "status" => false, 
                "message" => "Only owner can invite"
            ], 403);
        }

        $invitedUser = User::where('email', $request->email)->first();

        // Prevent self-invitation
        if ($invitedUser->id === $user->id) {
            return response()->json([
                "status" => false, 
                "message" => "Cannot invite yourself"
            ], 400);
        }

        // Check if already a member
        if ($book->members()->where('user_id', $invitedUser->id)->exists()) {
             return response()->json([
                 'status' => false, 
                 'message' => 'User is already a member'
             ], 400);
        }

        // Check for existing invitation
        $invitation = BookInvitation::where('book_id', $book->id)
                            ->where('invited_user_id', $invitedUser->id)
                            ->first();

        if ($invitation) {
            if ($invitation->status === 'pending') {
                return response()->json([
                    'status' => false, 
                    'message' => 'User already has a pending invitation'
                ], 400);
            }

            // Update existing record (accepted/rejected)
            $invitation->update([
                'status' => 'pending',
                'invited_by' => $user->id,
                'role' => $request->role ?? 'viewer',
            ]);
        } else {
            // Create NEW Invitation
            $invitation = BookInvitation::create([
                'book_id' => $book->id,
                'invited_user_id' => $invitedUser->id,
                'invited_by' => $user->id,
                'role' => $request->role ?? 'viewer',
                'status' => 'pending'
            ]);
        }

        // Notify properly
        logger('Sending notification to user_id: ' . $invitedUser->id);
        \App\Models\Notification::create([
            'user_id' => $invitedUser->id,
            'type' => 'invitation_received',
            'title' => 'Book Invitation',
            'message' => $request->user()->name . ' invited you to join ' . $book->name,
            'book_id' => $book->id,
            'is_read' => false,
            'data' => [
                'invitation_id' => $invitation->id,
                'title' => 'Book Invitation',
                'message' => $request->user()->name . ' invited you to join ' . $book->name,
            ]
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Invitation sent successfully'
        ]);
    }
}
