<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The checkout form: who is buying and where it goes.
 *
 * Money is deliberately absent. Whatever totals the page rendered were a
 * convenience for the customer; the authoritative figures are recalculated in
 * CreateOrder from the product row.
 */
class StoreOrderRequest extends FormRequest
{
    /** Brazilian states, used to keep the address sane. */
    private const STATES = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS',
        'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC',
        'SP', 'SE', 'TO',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise before validating so masks typed by the customer
     * ("(11) 98888-7777") do not trip the format rules.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_name' => $this->squish('customer_name'),
            'customer_email' => mb_strtolower(trim((string) $this->input('customer_email'))),
            'customer_phone' => preg_replace('/\D/', '', (string) $this->input('customer_phone')) ?: null,
            'zip_code' => preg_replace('/\D/', '', (string) $this->input('zip_code')) ?: null,
            'state' => mb_strtoupper(trim((string) $this->input('state'))),
            'city' => $this->squish('city'),
            'district' => $this->squish('district'),
            'street' => $this->squish('street'),
            'number' => $this->squish('number'),
            'complement' => $this->squish('complement') ?: null,
            'reference' => $this->squish('reference') ?: null,
        ]);
    }

    /**
     * Collapses whitespace without ever losing the value.
     *
     * preg_replace() with the /u modifier returns null when the subject is not
     * valid UTF-8. Defaulting that to an empty string would silently discard
     * what the customer typed and then blame them for leaving the field blank,
     * so malformed input is repaired rather than dropped.
     */
    private function squish(string $key): string
    {
        $value = (string) $this->input($key);

        if (! mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        }

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Dados pessoais
            'customer_name' => ['required', 'string', 'min:3', 'max:120', 'regex:/\p{L}+\s+\p{L}+/u'],
            // DNS validation only in production: it needs a live resolver and
            // would otherwise make checkout fail on an offline dev machine.
            'customer_email' => [
                'required',
                'string',
                app()->isProduction() ? 'email:rfc,dns' : 'email:rfc',
                'max:180',
            ],
            // 10 digits for a landline, 11 for a mobile.
            'customer_phone' => ['required', 'string', 'digits_between:10,11'],

            // Endereço
            'zip_code' => ['required', 'string', 'digits:8'],
            'state' => ['required', 'string', 'size:2', Rule::in(self::STATES)],
            'city' => ['required', 'string', 'max:120'],
            'district' => ['required', 'string', 'max:120'],
            'street' => ['required', 'string', 'max:180'],
            'number' => ['required', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:120'],
            'reference' => ['nullable', 'string', 'max:180'],

            // Pedido
            'notes' => ['nullable', 'string', 'max:1000'],
            'terms' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'customer_name' => 'nome completo',
            'customer_email' => 'e-mail',
            'customer_phone' => 'telefone',
            'zip_code' => 'CEP',
            'state' => 'estado',
            'city' => 'cidade',
            'district' => 'bairro',
            'street' => 'rua',
            'number' => 'número',
            'complement' => 'complemento',
            'reference' => 'ponto de referência',
            'notes' => 'observações',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'customer_name.regex' => 'Informe o nome e o sobrenome.',
            'customer_email.email' => 'Informe um e-mail válido — é por ele que enviamos a confirmação.',
            'customer_phone.digits_between' => 'Informe o telefone com DDD, ex.: (11) 98888-7777.',
            'zip_code.digits' => 'O CEP precisa ter 8 dígitos.',
            'state.in' => 'Selecione um estado válido.',
            'terms.accepted' => 'É preciso confirmar os dados do pedido para continuar.',
        ];
    }

    /**
     * @return array{
     *     customer_name: string, customer_email: string, customer_phone: string,
     *     notes: string|null,
     *     address: array<string, string|null>,
     *     ip_address: string|null, user_agent: string|null
     * }
     */
    public function orderData(): array
    {
        $validated = $this->validated();

        return [
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'notes' => $validated['notes'] ?? null,
            'address' => [
                'zip_code' => $this->formatZipCode($validated['zip_code']),
                'state' => $validated['state'],
                'city' => $validated['city'],
                'district' => $validated['district'],
                'street' => $validated['street'],
                'number' => $validated['number'],
                'complement' => $validated['complement'] ?? null,
                'reference' => $validated['reference'] ?? null,
            ],
            'ip_address' => $this->ip(),
            'user_agent' => substr((string) $this->userAgent(), 0, 500),
        ];
    }

    private function formatZipCode(string $digits): string
    {
        return substr($digits, 0, 5).'-'.substr($digits, 5);
    }
}
