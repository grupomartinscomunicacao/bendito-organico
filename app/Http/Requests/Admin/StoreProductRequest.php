<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\ProductUnit;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'slug' => filled($this->input('slug')) ? Str::slug((string) $this->input('slug')) : null,
            // The form posts Brazilian decimals ("12,90"); normalise before
            // the numeric rules run.
            'price' => $this->normalizeDecimal($this->input('price')),
            'compare_at_price' => $this->normalizeDecimal($this->input('compare_at_price')),
            'stock' => $this->normalizeDecimal($this->input('stock')) ?? 0,
            'is_active' => $this->boolean('is_active'),
            'is_featured' => $this->boolean('is_featured'),
            'track_stock' => $this->boolean('track_stock'),
        ]);
    }

    protected function normalizeDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = str_replace(['.', ' '], '', (string) $value);

        return str_replace(',', '.', $value);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'slug' => ['nullable', 'string', 'max:170', 'alpha_dash', Rule::unique(Product::class, 'slug')],
            'short_description' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0.01', 'max:99999.99', 'gt:price'],
            'unit' => ['required', Rule::enum(ProductUnit::class)],
            'stock' => ['required', 'numeric', 'min:0', 'max:99999.999'],
            'track_stock' => ['boolean'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            // Extension *and* MIME are both checked, and the file is re-named
            // on write, so an "image" cannot smuggle in something executable.
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=200,min_height=200'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:300'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'slug' => 'URL amigável',
            'short_description' => 'descrição curta',
            'description' => 'descrição',
            'price' => 'preço',
            'compare_at_price' => 'preço comparativo',
            'unit' => 'unidade',
            'stock' => 'estoque',
            'image' => 'foto',
            'sort_order' => 'ordem',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'compare_at_price.gt' => 'O preço comparativo precisa ser maior que o preço de venda.',
            'image.dimensions' => 'Use uma imagem com pelo menos 200×200 pixels.',
            'image.max' => 'A foto pode ter no máximo 4 MB.',
        ];
    }
}
