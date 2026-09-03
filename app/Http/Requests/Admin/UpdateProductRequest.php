<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Validation\Rule;

/**
 * Same shape as StoreProductRequest, with the unique slug rule taught to
 * ignore the record being edited.
 */
class UpdateProductRequest extends StoreProductRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product
            && ($this->user()?->can('update', $product) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = parent::rules();

        /** @var Product $product */
        $product = $this->route('product');

        $rules['slug'] = [
            'nullable',
            'string',
            'max:170',
            'alpha_dash',
            // The database unique index spans soft-deleted rows, so the rule
            // must consider them too or the save fails at the driver level.
            Rule::unique(Product::class, 'slug')->ignoreModel($product),
        ];

        return $rules;
    }
}
