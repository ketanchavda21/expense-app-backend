<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'title' => 'required|string|max:255',
            'note' => 'nullable|string',
            'date' => 'required|date',
        ];
    }
}
