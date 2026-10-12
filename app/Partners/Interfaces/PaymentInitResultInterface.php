<?php

namespace App\Partners\Interfaces;

use Illuminate\Support\Collection;

interface PaymentInitResultInterface
{
    public function getPartnerReference(): string;

    public function getRedirectUrl(): string;

    /**
     * @return Collection<string, mixed>
     */
    public function getPayload(): Collection;
}
