<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Product;
use App\Support\Money;
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
            Money::quantity($available),
            $product->unit->abbreviationFor($available),
            $product->name,
        ));
    }

    public static function emptyBasket(): self
    {
        return new self('Seu pedido expirou. Escolha o produto novamente para continuar.');
    }

    /**
     * O pedido não alcançou o mínimo da loja.
     *
     * A mensagem diz quanto falta, não só que faltou: o cliente resolve com um
     * ajuste na quantidade em vez de ficar adivinhando o número.
     */
    public static function belowMinimum(float $subtotal): self
    {
        $minimum = (float) config('bendito.checkout.minimum_order', 0);

        return new self(sprintf(
            'O pedido mínimo é de %s. Faltam %s — aumente a quantidade para continuar.',
            Money::brl($minimum),
            Money::brl(max(0, round($minimum - $subtotal, 2))),
        ));
    }

    /**
     * O estoque inteiro do item não chega ao pedido mínimo, então ele não pode
     * ser comprado sozinho.
     */
    public static function cannotMeetMinimum(Product $product): self
    {
        return new self(sprintf(
            'Não temos %s suficiente para alcançar o pedido mínimo de %s. Escolha outro item do catálogo.',
            $product->name,
            Money::brl((float) config('bendito.checkout.minimum_order', 0)),
        ));
    }

    public static function invalidQuantity(): self
    {
        return new self('Informe uma quantidade válida para continuar.');
    }
}
