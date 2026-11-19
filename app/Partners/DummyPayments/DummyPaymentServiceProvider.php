<?php

namespace App\Partners\DummyPayments;

use Illuminate\Support\ServiceProvider;

class DummyPaymentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'dummy-payment');
    }
}
