<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Book;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create an Owner
        $owner = User::create([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
        ]);

        // Create an Editor
        $editor = User::create([
            'name' => 'Editor User',
            'email' => 'editor@example.com',
            'password' => Hash::make('password'),
        ]);

        // Create a Viewer
        $viewer = User::create([
            'name' => 'Viewer User',
            'email' => 'viewer@example.com',
            'password' => Hash::make('password'),
        ]);

        // Create Book
        $book = Book::create([
            'user_id' => $owner->id,
            'name' => 'Household Expenses',
            'description' => 'Tracking daily expenses for the house.',
        ]);

        // Add creator as owner in pivot table
        $book->members()->attach($owner->id, ['role' => 'owner']);

        // Invite Members
        $book->members()->attach($editor->id, ['role' => 'editor']);
        $book->members()->attach($viewer->id, ['role' => 'viewer']);

        // Add Transactions
        Transaction::create([
            'book_id' => $book->id,
            'user_id' => $owner->id,
            'type' => 'income',
            'amount' => 5000.00,
            'title' => 'Salary',
            'note' => 'March Salary',
            'date' => Carbon::now()->subDays(5)->toDateString(),
        ]);

        Transaction::create([
            'book_id' => $book->id,
            'user_id' => $editor->id,
            'type' => 'expense',
            'amount' => 150.50,
            'title' => 'Groceries',
            'note' => 'Weekly groceries',
            'date' => Carbon::now()->subDays(2)->toDateString(),
        ]);

        Transaction::create([
            'book_id' => $book->id,
            'user_id' => $owner->id,
            'type' => 'expense',
            'amount' => 50.00,
            'title' => 'Internet Bill',
            'note' => 'Monthly broadband',
            'date' => Carbon::now()->subDays(1)->toDateString(),
        ]);
    }
}
