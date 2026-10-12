<?php

namespace App\Partners\DummyPayments\DTOs;

use App\Partners\Interfaces\PaymentInitResultInterface;
use Illuminate\Support\Collection;

final readonly class PaymentResultDTO implements PaymentInitResultInterface
{
    public function __construct(
        public string $paymentReference,
        public string $redirectLink,
        public ?array $payload = null,
    ) {}

    public function getPartnerReference(): string
    {
        return $this->paymentReference;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectLink;
    }

    public function getPayload(): Collection
    {
        return collect($this->payload ?? []);
    }
}
