<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Book;

class TransactionPolicy
{
    private function getRole(User $user, Book $book) {
        if ($user->id === $book->user_id) {
            return 'owner';
        }
        $member = $book->members()->where('user_id', $user->id)->first();
        return $member ? $member->pivot->role : null;
    }

    public function viewAny(User $user, Book $book): bool
    {
        return $this->getRole($user, $book) !== null;
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $this->getRole($user, $transaction->book) !== null;
    }

    public function create(User $user, Book $book): bool
    {
        $role = $this->getRole($user, $book);
        return $role === 'owner' || $role === 'editor';
    }

    public function update(User $user, Transaction $transaction): bool
    {
        $role = $this->getRole($user, $transaction->book);
        return $role === 'owner' || $role === 'editor';
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        $role = $this->getRole($user, $transaction->book);
        return $role === 'owner';
    }
}
