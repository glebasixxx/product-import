<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'external_code' => ['required', 'string', 'max:255', 'unique:products,external_code'],
            'name' => ['required', 'string', 'max:512'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'attributes' => ['sometimes', 'array'],
            'attributes.*.key' => ['required', 'string', 'max:255', 'distinct'],
            'attributes.*.value' => ['required', 'string'],
        ];
    }
}
