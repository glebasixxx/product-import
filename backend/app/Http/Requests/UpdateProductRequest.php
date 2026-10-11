<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'external_code' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('products', 'external_code')->ignore((int) $this->route('product')),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:512'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:9999999999'],
            'purchase_price' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999'],
            'attributes' => ['sometimes', 'array'],
            'attributes.*.key' => ['required', 'string', 'max:255', 'distinct'],
            'attributes.*.value' => ['required', 'string'],
        ];
    }
}
