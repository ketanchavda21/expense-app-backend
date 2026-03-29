<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Book;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    private function getBookBySlug($slug) {
        return Book::where('slug', $slug)->first();
    }

    public function index(Request $request, $slug)
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
        
        $transactions = $book->transactions()
            ->with('creator')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);
            
        return TransactionResource::collection($transactions);
    }

    public function store(StoreTransactionRequest $request, $slug)
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

        if ($role === 'viewer') {
            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to add transactions'
            ], 403);
        }
        
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $transaction = $book->transactions()->create($data);
        
        NotificationService::notifyTransactionAdded($data['user_id'], $book, $request->user()->name);
        
        return new TransactionResource($transaction->load('creator'));
    }

    public function update(UpdateTransactionRequest $request, $id)
    {
        $transaction = Transaction::with('book')->find($id);
        if (!$transaction) return response()->json(["status" => false, "message" => "Transaction not found"], 404);
        
        $book = $transaction->book;
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

        if ($role === 'viewer') {
            return response()->json(["status" => false, "message" => "You are not authorized to edit transactions"], 403);
        }

        // Ownership enforcement: Only creator can edit
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'You can only modify your own transaction'
            ], 403);
        }
        
        $transaction->update($request->validated());
        
        return new TransactionResource($transaction->load('creator'));
    }

    public function destroy(Request $request, $id)
    {
        $transaction = Transaction::with('book')->find($id);
        if (!$transaction) return response()->json(["status" => false, "message" => "Transaction not found"], 404);
        
        $book = $transaction->book;
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

        if ($role === 'viewer') {
             return response()->json(["status" => false, "message" => "You are not authorized to delete transactions"], 403);
        }

        // Ownership enforcement: Only creator can delete
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'You can only modify your own transaction'
            ], 403);
        }
        
        $transaction->delete();
        
        return response()->json(['status' => true, 'message' => 'Transaction deleted successfully.']);
    }
}
