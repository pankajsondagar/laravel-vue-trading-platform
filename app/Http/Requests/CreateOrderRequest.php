<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'symbol' => 'required|string|in:BTC,ETH,SOL,XRP',
            'side' => 'required|string|in:buy,sell',
            'price' => [
                'required',
                'numeric',
                'gt:0',
                'regex:/^\d+(\.\d{1,8})?$/', // Max 8 decimal places
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
                'regex:/^\d+(\.\d{1,8})?$/', // Max 8 decimal places
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'symbol.in' => 'The selected symbol is invalid. Available: BTC, ETH, SOL, XRP.',
            'side.in' => 'The side must be either buy or sell.',
            'price.gt' => 'The price must be greater than 0.',
            'amount.gt' => 'The amount must be greater than 0.',
            'price.regex' => 'The price can have maximum 8 decimal places.',
            'amount.regex' => 'The amount can have maximum 8 decimal places.',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        
        // Convert to string for bcmath operations
        $validated['price'] = (string) $validated['price'];
        $validated['amount'] = (string) $validated['amount'];
        
        return $validated;
    }
}