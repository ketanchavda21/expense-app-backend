<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use App\Models\BookInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Notifications\InvitationReceived;
use App\Notifications\InvitationAccepted;
use App\Notifications\InvitationRejected;
class InvitationController extends Controller
{
    public function sendInvite(Request $request, $slug)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'role' => 'nullable|in:editor,viewer' // fallback to viewer if not provided
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $book = Book::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        // 1. Check if the user is authorized to send an invite (only owner)
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

        // 2. Check if already a member
        if ($book->members()->where('user_id', $invitedUser->id)->exists()) {
             return response()->json([
                 'status' => false, 
                 'message' => 'User is already a member'
             ], 400);
        }

        // 3. Check for existing invitation
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
            // 4. Create NEW Invitation
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
            'message' => "You have been invited to join {$book->name} by {$user->name}"
        ]);
    }

    public function index(Request $request)
    {
        $invitations = BookInvitation::with(['book', 'inviter'])
                        ->where('invited_user_id', $request->user()->id)
                        ->where('status', 'pending')
                        ->get();

        return response()->json([
            'status' => true,
            'data' => $invitations
        ]);
    }

    public function accept(Request $request, $id)
    {
        $invitation = BookInvitation::findOrFail($id);
        
        // Ensure the logged-in user is the one answering
        if ($invitation->invited_user_id !== $request->user()->id) {
            return response()->json([
                'status' => false, 
                'message' => 'Unauthorized actions'
            ], 403);
        }

        // Ensure invitation is not accepted twice
        if ($invitation->status !== 'pending') {
            return response()->json([
                'status' => false, 
                'message' => 'Invitation no longer valid or already processed'
            ], 400);
        }

        // Attach user dynamically based on the stored role
        $invitation->book->users()->attach($request->user()->id, [
            'role' => $invitation->role
        ]);

        // Mark as Accepted
        $invitation->update(['status' => 'accepted']);

        // Notify Owner
        \App\Models\Notification::create([
            'user_id' => $invitation->invited_by,
            'type' => 'invitation_accepted',
            'title' => 'Invitation Accepted',
            'message' => $request->user()->name . ' accepted your invitation for ' . $invitation->book->name,
            'book_id' => $invitation->book_id,
            'is_read' => false,
        ]);

        // Mark notification as read
        \App\Models\Notification::where('user_id', $request->user()->id)
            ->where('book_id', $invitation->book_id)
            ->where('type', 'invitation_received')
            ->update(['is_read' => true]);

        return response()->json([
            'status' => true,
            'message' => 'Invitation accepted successfully'
        ]);
    }

    public function reject(Request $request, $id)
    {
        $invitation = BookInvitation::findOrFail($id);
        
        // Ensure the logged-in user is the one answering
        if ($invitation->invited_user_id !== $request->user()->id) {
            return response()->json([
                'status' => false, 
                'message' => 'Unauthorized actions'
            ], 403);
        }

        if ($invitation->status !== 'pending') {
            return response()->json([
                'status' => false, 
                'message' => 'Invitation no longer valid or already processed'
            ], 400);
        }

        // Discard without attaching
        $invitation->update(['status' => 'rejected']);

        // Notify Owner
        \App\Models\Notification::create([
            'user_id' => $invitation->invited_by,
            'type' => 'invitation_rejected',
            'title' => 'Invitation Rejected',
            'message' => $request->user()->name . ' rejected your invitation for ' . $invitation->book->name,
            'book_id' => $invitation->book_id,
            'is_read' => false,
        ]);
        
        // Mark notification as read
        \App\Models\Notification::where('user_id', $request->user()->id)
            ->where('book_id', $invitation->book_id)
            ->where('type', 'invitation_received')
            ->update(['is_read' => true]);

        return response()->json([
            'status' => true,
            'message' => 'Invitation rejected successfully'
        ]);
    }
}
