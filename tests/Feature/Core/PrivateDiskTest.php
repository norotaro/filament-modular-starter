<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

it('registers no route that serves the private disk by path', function () {
    expect(Route::has('storage.local'))->toBeFalse()
        ->and(Route::has('storage.local.upload'))->toBeFalse();
});

it('refuses to mint a temporary URL for a private file', function () {
    Storage::disk('local')->temporaryUrl('private.txt', now()->addMinutes(5));
})->throws(RuntimeException::class);
