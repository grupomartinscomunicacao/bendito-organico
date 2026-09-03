<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Fazer pedido" on a product page: which product, and how much of it.
 *
 * Note what is *not* accepted here — no price, no total. Those are computed
 * from the database when the order is created.
 */
class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'integer',
                Rule::exists(Product::class, 'id')->where('is_active', true)->whereNull('deleted_at'),
            ],
            'quantity' => [
                'required',
                'numeric',
                'min:0.001',
                'max:'.config('bendito.checkout.max_quantity', 99),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Selecione um produto para continuar.',
            'product_id.exists' => 'Este produto não está mais disponível.',
            'quantity.required' => 'Informe a quantidade desejada.',
            'quantity.numeric' => 'A quantidade precisa ser um número.',
            'quantity.min' => 'A quantidade precisa ser maior que zero.',
            'quantity.max' => 'Quantidade acima do limite por pedido.',
        ];
    }

    public function product(): Product
    {
        return Product::query()
            ->active()
            ->findOrFail($this->integer('product_id'));
    }

    public function quantity(): float
    {
        return round((float) $this->input('quantity'), 3);
    }
}
