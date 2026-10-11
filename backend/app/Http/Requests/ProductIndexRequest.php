<?php

namespace App\Http\Requests;

use App\DTO\ProductFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductIndexRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'price_min' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_max' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'sort' => ['sometimes', Rule::in(ProductFilter::SORTABLE)],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $min = $this->input('price_min');
                $max = $this->input('price_max');

                if (is_numeric($min) && is_numeric($max) && (float) $min > (float) $max) {
                    $validator->errors()->add('price_max', 'The price max must be greater than or equal to price min.');
                }
            },
        ];
    }

    public function toFilter(): ProductFilter
    {
        return ProductFilter::fromArray($this->validated());
    }
}
