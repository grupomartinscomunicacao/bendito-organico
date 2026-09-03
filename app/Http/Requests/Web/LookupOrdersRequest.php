<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;

/**
 * "Meu pedido": the customer identifies themself by the phone number they
 * gave at checkout.
 *
 * The mask is stripped before validation so "(77) 99999-9999", "77 99999-9999"
 * and "77999999999" are all the same number by the time the rules run.
 */
class LookupOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'telefone' => Phone::normalize($this->input('telefone')),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // 10 digits for a landline, 11 for a mobile — the same shape the
            // checkout accepts, so a number that could place an order can
            // always find it again.
            'telefone' => ['required', 'string', 'digits_between:10,11'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['telefone' => 'telefone'];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'telefone.required' => 'Informe o telefone que você usou no pedido.',
            'telefone.digits_between' => 'Informe o telefone com DDD, ex.: (11) 98888-7777.',
        ];
    }

    /** The canonical digits to search for. */
    public function phone(): string
    {
        return (string) $this->validated()['telefone'];
    }
}
