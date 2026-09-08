<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Store identity
    |--------------------------------------------------------------------------
    |
    | Everything the storefront, the printed order card and the SEO tags need
    | to describe the business. Keeping it here avoids hardcoding contact data
    | across a dozen Blade files.
    |
    */

    'name' => env('STORE_NAME', 'Bendito Orgânico'),
    'tagline' => env('STORE_TAGLINE', 'Verduras, legumes e hortaliças'),
    'description' => env(
        'STORE_DESCRIPTION',
        'Hortaliças e verduras orgânicas colhidas no dia e entregues frescas na sua casa. Sem agrotóxicos, direto do produtor.'
    ),

    'contact' => [
        'email' => env('STORE_EMAIL', 'benditoorganico@gmail.com'),
        'phone' => env('STORE_PHONE', '(77) 99956-3904'),
        'whatsapp' => env('STORE_WHATSAPP', '5577999563904'),
        'address' => env('STORE_ADDRESS', 'Chacara Bendito, Serra do Aeroporto — Zona Rural'),
        'city' => env('STORE_CITY', 'Barreiras'),
        'state' => env('STORE_STATE', 'BA'),
    ],

    'social' => [
        'instagram' => env('STORE_INSTAGRAM', 'https://instagram.com/bendito.organico'),
        'facebook' => env('STORE_FACEBOOK'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Brand assets
    |--------------------------------------------------------------------------
    |
    | The official artwork shipped with the project. "full" is the canonical
    | horizontal lockup; "light" is the same lockup knocked out to white for
    | dark surfaces; "mark" is the circular badge for tight spaces.
    |
    */

    'logo' => [
        'full' => 'images/brand/logo.png',
        'light' => 'images/brand/logo-light.png',
        'mark' => 'images/brand/logo-mark.png',
        'og' => 'images/brand/og-image.png',
    ],

    /*
    | Foto de capa do hero, em tamanhos responsivos. O arquivo original
    | entregue ("hero-section.png") é WebP por dentro; estas variantes têm
    | extensão coerente com o conteúdo, o que evita depender do navegador
    | adivinhar o formato quando o Content-Type vem do nome do arquivo.
    */
    'hero' => [
        'webp' => 'images/brand/hero-section.webp',
        'webp_1280' => 'images/brand/hero-section-1280.webp',
        'webp_768' => 'images/brand/hero-section-768.webp',
        'jpg' => 'images/brand/hero-section.jpg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Créditos
    |--------------------------------------------------------------------------
    */

    'developer' => [
        'name' => env('DEVELOPER_NAME', 'Origin Hub'),
        'url' => env('DEVELOPER_URL', 'https://originhub.com.br'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Catalog & checkout rules
    |--------------------------------------------------------------------------
    */

    'catalog' => [
        'per_page' => (int) env('CATALOG_PER_PAGE', 12),
        'featured_limit' => (int) env('CATALOG_FEATURED_LIMIT', 8),
        'cache_ttl' => (int) env('CATALOG_CACHE_TTL', 300),
    ],

    'checkout' => [
        'max_quantity' => (int) env('CHECKOUT_MAX_QUANTITY', 99),
        'delivery_fee' => (float) env('CHECKOUT_DELIVERY_FEE', 0),

        /*
        | Valor mínimo do pedido, em reais.
        |
        | Comparado contra o SUBTOTAL (produto × quantidade), não contra o
        | total: a taxa de entrega é combinada depois pelo WhatsApp e não
        | deve contar para alcançar o mínimo.
        |
        | O checkout é de um produto por pedido, então o mínimo se traduz em
        | uma quantidade mínima daquele item — a página do produto já abre com
        | ela preenchida. Zero desliga a regra.
        */
        'minimum_order' => (float) env('CHECKOUT_MINIMUM_ORDER', 50),
        'delivery_notice' => env(
            'CHECKOUT_DELIVERY_NOTICE',
            'Entregamos de terça a sábado. Após a confirmação do pagamento entramos em contato pelo WhatsApp para combinar o melhor horário.'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Order numbering
    |--------------------------------------------------------------------------
    |
    | Public numbers double as the route key, so they carry a random suffix:
    | readable for humans, not enumerable by a stranger.
    |
    */

    'orders' => [
        'number_prefix' => env('ORDER_NUMBER_PREFIX', 'BO'),
    ],

];
