<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::any('/', fn () => redirect('/admin'));
});
