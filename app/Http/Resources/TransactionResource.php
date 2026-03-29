<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'book_id' => $this->book_id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('creator', function() {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'title' => $this->title,
            'note' => $this->note,
            'date' => $this->date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
