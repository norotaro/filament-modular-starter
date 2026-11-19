<?php

namespace App\Partners\Interfaces;

use App\Modules\Core\Support\Result;
use App\Partners\Enums\PartnerId;
use App\Partners\Support\PaymentIntent;

interface PaymentPartnerInterface
{
    public function getPartnerId(): PartnerId;

    /**
     * Initialize a payment with the partner.
     *
     * @return Result<PaymentInitResultInterface>
     */
    public function initializePayment(PaymentIntent $intent): Result;

    /**
     * Revert a payment with the partner.
     *
     * @return Result<array<string, mixed>>
     */
    public function revertPayment(PaymentIntent $intent): Result;

    /**
     * Get a relevant subset of the partner payload for display in the admin panel.
     *
     * @return array<string, string>|null
     */
    public function getDisplayPayload(PaymentIntent $intent): ?array;
}
