<?php

namespace App\Partners\Support;

/**
 * Domain-agnostic payment intent handed to a partner gateway.
 *
 * The skeleton ships no payments module, so gateways are typed against this
 * DTO instead of a domain `Payment` model. Applications that add a payments
 * module map their payment record to an intent at the call site (a mapper in
 * that module), keeping `app/Partners/` free of domain imports.
 */
final readonly class PaymentIntent
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $reference,
        public int $amountMinor,
        public string $currency = 'USD',
        public array $metadata = [],
    ) {}
}
