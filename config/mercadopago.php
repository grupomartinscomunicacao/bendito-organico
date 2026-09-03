<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Credentials
    |--------------------------------------------------------------------------
    |
    | Credentials live exclusively in the environment. The access token is a
    | server-side secret and must never reach a Blade view or the JS bundle.
    | Only the public key is safe to expose to the browser.
    |
    */

    'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
    'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
    'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    */

    'base_url' => env('MERCADOPAGO_BASE_URL', 'https://api.mercadopago.com'),
    'timeout' => (int) env('MERCADOPAGO_TIMEOUT', 20),
    'retry_times' => (int) env('MERCADOPAGO_RETRY_TIMES', 3),
    'retry_sleep' => (int) env('MERCADOPAGO_RETRY_SLEEP', 250),

    /*
    |--------------------------------------------------------------------------
    | Checkout behaviour
    |--------------------------------------------------------------------------
    |
    | "statement_descriptor" is what the buyer sees on the card statement.
    | "expires_after" closes an abandoned preference so stock is not held
    | hostage by checkouts that were never paid.
    |
    */

    'statement_descriptor' => env('MERCADOPAGO_STATEMENT_DESCRIPTOR', 'BENDITOORGANICO'),
    'expires_after_minutes' => (int) env('MERCADOPAGO_EXPIRES_AFTER_MINUTES', 60),
    'binary_mode' => (bool) env('MERCADOPAGO_BINARY_MODE', false),
    'installments' => (int) env('MERCADOPAGO_INSTALLMENTS', 12),

    /*
    | Meios de pagamento bloqueados no checkout, por payment_type_id.
    |
    | A loja trabalha só com Pix e cartão, então boleto ("ticket") e pagamento
    | em caixa eletrônico ("atm") ficam de fora — ambos levam dias para
    | compensar e deixariam hortaliça perecível reservada nesse meio-tempo.
    | Continuam liberados: bank_transfer (Pix), credit_card, debit_card e
    | account_money (saldo Mercado Pago).
    */
    'excluded_payment_types' => array_filter(
        explode(',', (string) env('MERCADOPAGO_EXCLUDED_PAYMENT_TYPES', 'ticket,atm'))
    ),

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    |
    | When "verify_signature" is disabled the webhook still verifies the
    | payment against the API before touching an order, but the HMAC check is
    | skipped. Keep it enabled in production.
    |
    */

    'verify_signature' => (bool) env('MERCADOPAGO_VERIFY_SIGNATURE', true),
    'signature_tolerance' => (int) env('MERCADOPAGO_SIGNATURE_TOLERANCE', 300),

    /*
    | Public URL Mercado Pago posts notifications to. Leave empty to derive it
    | from APP_URL; set it explicitly when developing behind a tunnel.
    */
    'webhook_url' => env('MERCADOPAGO_WEBHOOK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Sandbox
    |--------------------------------------------------------------------------
    |
    | Test credentials ("TEST-…") return a sandbox_init_point that must be used
    | instead of init_point. Detected automatically, overridable here.
    |
    */

    'sandbox' => env('MERCADOPAGO_SANDBOX'),

];
