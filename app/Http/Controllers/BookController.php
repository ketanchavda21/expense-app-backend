<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Requests\InviteUserRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\BookResource;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    private function getBookBySlug($slug) {
        return Book::where('slug', $slug)->with(['owner', 'members', 'transactions'])->first();
    }

    public function index(Request $request)
    {
        $user = $request->user();
        
        $books = Book::whereHas('members', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with(['owner', 'members', 'transactions'])
            ->get();
            
        return BookResource::collection($books);
    }

    public function store(StoreBookRequest $request)
    {
        $book = $request->user()->books()->create($request->validated());
        
        // Add creator as owner in pivot table
        if (!$book->users()->where('user_id', $request->user()->id)->exists()) {
            $book->users()->attach($request->user()->id, ['role' => 'owner']);
        }
        
        return new BookResource($book->load(['owner', 'users', 'members', 'transactions']));
    }

    public function show(Request $request, $slug)
    {
        $book = Book::where('slug', $slug)->with('users')->first();
        
        if (!$book) {
            return response()->json(["status" => false, "message" => "Book not found"], 404);
        }

        $member = $book->users()
            ->where('user_id', $request->user()->id)
            ->first();

        $book->role = $member ? $member->pivot->role : null;

        if (!$book->role) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have access to this book'
            ], 403);
        }

        logger('ROLE: ' . $book->role);
        
        $book->load('users');
        $members = $book->users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot->role,
            ];
        });

        return response()->json([
            'id' => $book->id,
            'name' => $book->name,
            'slug' => $book->slug,
            'role' => $book->role,
            'members' => $members
        ]);
    }

    public function update(UpdateBookRequest $request, $slug)
    {
        $book = $this->getBookBySlug($slug);
        
        if (!$book) {
            return response()->json(["status" => false, "message" => "Book not found"], 404);
        }

        $member = DB::table('book_user')
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
            return response()->json(["status" => false, "message" => "You do not have permission to edit this book"], 403);
        }
        
        $book->update($request->validated());
        
        return new BookResource($book);
    }

    public function destroy(Request $request, $slug)
    {
        $book = $this->getBookBySlug($slug);
        
        if (!$book) {
            return response()->json(["status" => false, "message" => "Book not found"], 404);
        }

        $member = DB::table('book_user')
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
                "message" => "Only owner can delete this book",
                "role" => $role
            ], 403);
        }

        $book->delete();
        
        return response()->json([
            "status" => true, 
            "message" => "Book deleted successfully.",
            "role" => "owner"
        ]);
    }



    public function updateRole(UpdateRoleRequest $request, $slug, $userId) 
    {
        $book = $this->getBookBySlug($slug);
        
        if (!$book) return response()->json(["status" => false, "message" => "Book not found"], 404);
        
        $member = DB::table('book_user')
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
             return response()->json(["status" => false, "message" => "Only owner can update roles"], 403);
        }

        if ($request->user()->id == $userId) {
            return response()->json([
                "status" => false,
                "message" => "Owner cannot change their own role"
            ], 400);
        }

        $member = $book->members()->where('user_id', $userId)->first();
        if (!$member) {
            return response()->json(['status' => false, 'message' => 'User is not a member of this book.'], 400);
        }

        $book->members()->updateExistingPivot($userId, ['role' => $request->role]);

        return response()->json(['status' => true, 'message' => 'Role updated successfully.']);
    }

    public function removeUser(Request $request, $slug, $userId)
    {
        $book = $this->getBookBySlug($slug);
        
        if (!$book) return response()->json(["status" => false, "message" => "Book not found"], 404);
        
        $member = DB::table('book_user')
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
             return response()->json(["status" => false, "message" => "Only owner can manage members"], 403);
        }
        
        if (!$book->members()->where('user_id', $userId)->exists()) {
            return response()->json(['status' => false, 'message' => 'User is not a member.'], 400);
        }
        
        $book->members()->detach($userId);
        
        return response()->json(['status' => true, 'message' => 'User removed successfully.']);
    }
}
