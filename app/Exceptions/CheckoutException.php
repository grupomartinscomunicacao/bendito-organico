<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Product;
use RuntimeException;

/**
 * A checkout that cannot proceed for a reason the customer can act on.
 *
 * Unlike PaymentGatewayException, the message here is safe to show: it
 * describes the catalog, not our internals.
 */
class CheckoutException extends RuntimeException
{
    public static function productUnavailable(): self
    {
        return new self('Este produto não está mais disponível. Escolha outro item do catálogo.');
    }

    public static function insufficientStock(Product $product): self
    {
        $available = (float) $product->stock;

        if ($available <= 0) {
            return new self("Infelizmente {$product->name} acabou de esgotar.");
        }

        return new self(sprintf(
            'Temos apenas %s %s de %s no momento. Ajuste a quantidade para continuar.',
            rtrim(rtrim(number_format($available, 3, ',', '.'), '0'), ','),
            $product->unit->abbreviation(),
            $product->name,
        ));
    }

    public static function emptyBasket(): self
    {
        return new self('Seu pedido expirou. Escolha o produto novamente para continuar.');
    }

    public static function invalidQuantity(): self
    {
        return new self('Informe uma quantidade válida para continuar.');
    }
}
