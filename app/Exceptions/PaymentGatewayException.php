<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Raised when the payment gateway cannot be reached or refuses a request.
 *
 * The message is written for a log, never for a customer: controllers catch
 * this and show a generic notice so gateway internals stay private.
 */
class PaymentGatewayException extends RuntimeException
{
    /** @var array<string, mixed> */
    protected array $context;

    /** @param  array<string, mixed>  $context */
    public function __construct(string $message, array $context = [], int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->context = $context;
    }

    /** @return array<string, mixed> */
    public function context(): array
    {
        return $this->context;
    }

    public static function notConfigured(): self
    {
        return new self('Mercado Pago credentials are missing. Set MERCADOPAGO_ACCESS_TOKEN in .env.');
    }

    /** @param  array<string, mixed>  $context */
    public static function requestFailed(string $endpoint, int $status, array $context = []): self
    {
        return new self(
            "Mercado Pago request to [{$endpoint}] failed with HTTP {$status}.",
            $context + ['endpoint' => $endpoint, 'status' => $status],
        );
    }
}
