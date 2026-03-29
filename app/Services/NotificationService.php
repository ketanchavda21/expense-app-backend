<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Book;

class NotificationService
{
    /**
     * Notify a user when they are added to a book.
     */
    public static function notifyMemberAdded($userId, Book $book)
    {
        Notification::create([
            'user_id' => $userId,
            'title' => 'Added to Book',
            'message' => "You were added to a book: {$book->name}",
            'type' => 'member_added',
            'book_id' => $book->id,
            'is_read' => false,
        ]);
    }

    /**
     * Notify members when a transaction is added to a book.
     */
    public static function notifyTransactionAdded($creatorId, Book $book, $creatorName)
    {
        // Get all members and the owner
        $membersToNotify = $book->members()->pluck('users.id')->toArray();
        $membersToNotify[] = $book->user_id; // Add owner

        // Filter out the creator
        $membersToNotify = array_diff(array_unique($membersToNotify), [$creatorId]);

        foreach ($membersToNotify as $userId) {
            Notification::create([
                'user_id' => $userId,
                'title' => 'New Transaction',
                'message' => "{$creatorName} added a transaction in {$book->name}",
                'type' => 'transaction_added',
                'book_id' => $book->id,
                'is_read' => false,
            ]);
        }
    }
}
