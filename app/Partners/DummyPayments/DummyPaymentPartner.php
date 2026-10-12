<?php

namespace App\Partners\DummyPayments;

use App\Modules\Core\Support\Result;
use App\Partners\DummyPayments\DTOs\PaymentResultDTO;
use App\Partners\Enums\PartnerId;
use App\Partners\Interfaces\PaymentPartnerInterface;
use App\Partners\Support\PaymentIntent;
use Illuminate\Support\Str;

/**
 * Minimal reference gateway.
 *
 * Always succeeds, talks to no external API, and carries a demo payment form
 * view. Use it as the template for a real gateway — see the Partner Gateway
 * Development Guide in app/README.md.
 */
class DummyPaymentPartner implements PaymentPartnerInterface
{
    public function getPartnerId(): PartnerId
    {
        return PartnerId::Dummy;
    }

    /** {@inheritDoc} */
    public function initializePayment(PaymentIntent $intent): Result
    {
        // Dummy implementation always returns success
        return Result::success(new PaymentResultDTO(
            paymentReference: 'DUMMY-'.$intent->reference.'-'.uniqid(),
            redirectLink: 'https://dummy-payment-gateway.test/pay/'.$intent->reference,
            payload: [
                'data' => 'This is the payload from the Dummy partner',
            ],
        ));
    }

    /** {@inheritDoc} */
    public function revertPayment(PaymentIntent $intent): Result
    {
        return Result::success([
            'status' => 'reverted',
            'message' => 'Dummy payment reverted successfully',
        ]);
    }

    /** {@inheritDoc} */
    public function getDisplayPayload(PaymentIntent $intent): ?array
    {
        return [
            'type' => 'Dummy Payment',
            'data' => Str::of((string) ($intent->metadata['data'] ?? ''))->limit(100)->toString(),
        ];
    }
}
