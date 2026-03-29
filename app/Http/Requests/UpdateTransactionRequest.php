<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'sometimes|in:income,expense',
            'amount' => 'sometimes|numeric|min:0.01',
            'title' => 'sometimes|string|max:255',
            'note' => 'nullable|string',
            'date' => 'sometimes|date',
        ];
    }
}
