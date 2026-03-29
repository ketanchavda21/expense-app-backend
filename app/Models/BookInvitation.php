<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id', 'invited_by', 'invited_user_id', 'role', 'status'
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }
}
