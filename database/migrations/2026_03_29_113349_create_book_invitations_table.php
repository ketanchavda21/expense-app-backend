<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
            $table->foreignId('invited_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('invited_user_id')->constrained('users')->onDelete('cascade');
            $table->string('role')->default('viewer');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();
            
            $table->unique(['book_id', 'invited_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_invitations');
    }
};
