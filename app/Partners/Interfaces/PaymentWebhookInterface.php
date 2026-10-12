<?php

namespace App\Partners\Interfaces;

use App\Modules\Core\Support\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

interface PaymentWebhookInterface
{
    /**
     * Validate the request.
     *
     * @return Result<Collection<string, mixed>>
     */
    public function validateRequest(Request $request): Result;

    /**
     * Handle the request.
     *
     * @return Result<string>
     */
    public function handle(Collection $data): Result;
}
