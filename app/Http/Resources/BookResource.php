<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $transactions = $this->whenLoaded('transactions');
        $totalIncome = 0;
        $totalExpense = 0;
        
        if ($transactions) {
            $totalIncome = $transactions->where('type', 'income')->sum('amount');
            $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        } else {
            // Optional fallback if not loaded, but best to load
            $totalIncome = $this->transactions()->where('type', 'income')->sum('amount');
            $totalExpense = $this->transactions()->where('type', 'expense')->sum('amount');
        }

        $user = $request->user();
        $currentUserMember = $this->members->where('id', $user?->id)->first();
        $userRole = $currentUserMember ? $currentUserMember->pivot->role : null;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'role' => $userRole,
            'name' => $this->name,
            'description' => $this->description,
            'owner_id' => $this->user_id,
            'owner_name' => $this->whenLoaded('owner', fn() => $this->owner->name, $this->owner?->name),
            'total_income' => round($totalIncome, 2),
            'total_expense' => round($totalExpense, 2),
            'balance' => round($totalIncome - $totalExpense, 2),
            'members' => $this->whenLoaded('members', function() {
                return $this->members->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'role' => $member->pivot->role,
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
