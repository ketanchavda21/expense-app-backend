<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    private function getRole(User $user, Book $book) {
        $member = $book->members()->where('user_id', $user->id)->first();
        return $member ? $member->pivot->role : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Book $book): bool
    {
        return $this->getRole($user, $book) !== null;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Book $book): bool
    {
        $role = $this->getRole($user, $book);
        return $role === 'owner' || $role === 'editor';
    }

    public function delete(User $user, Book $book): bool
    {
        return $book->members()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'owner')
            ->exists();
    }

    public function manageMembers(User $user, Book $book): bool
    {
        return $this->getRole($user, $book) === 'owner';
    }
}
