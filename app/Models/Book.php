<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Book extends Model
{
    protected $fillable = ['user_id', 'name', 'slug', 'description'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($book) {
            do {
             $slug = Str::random(21);
            } while (static::where('slug', $slug)->exists());

            $book->slug = $slug;
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'book_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'book_user')->withPivot('role');
    }
}
