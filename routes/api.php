<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\BookMemberController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\TransactionController;

// Auth Routes (Public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Google Auth
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth Routes (Protected)
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    
    // Books
    Route::get('books', [BookController::class, 'index']);
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('books', [BookController::class, 'store']);
    Route::get('books/{slug}', [BookController::class, 'show']);
    Route::put('books/{slug}', [BookController::class, 'update']);
    Route::delete('books/{slug}', [BookController::class, 'destroy']);
    
    // Book Members
    
    // Book Invitations Workflow
    Route::post('/books/{slug}/invite', [BookMemberController::class, 'invite']);
    Route::get('/invitations', [InvitationController::class, 'index']);
    Route::post('/invitations/{id}/accept', [InvitationController::class, 'accept']);
    Route::post('/invitations/{id}/reject', [InvitationController::class, 'reject']);
    Route::put('/books/{slug}/members/{userId}', [BookController::class, 'updateRole']);
    Route::delete('/books/{slug}/remove-user/{userId}', [BookController::class, 'removeUser']);
    
    // Transactions
    Route::get('/books/{slug}/transactions', [TransactionController::class, 'index']);
    Route::post('/books/{slug}/transactions', [TransactionController::class, 'store']);
    Route::put('/transactions/{id}', [TransactionController::class, 'update']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);
});
