<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature', 'Browser');

/*
|--------------------------------------------------------------------------
| Database in tests
|--------------------------------------------------------------------------
|
| RefreshDatabase is deliberately NOT applied globally: files that touch the
| schema opt in individually with LazilyRefreshDatabase. The suite runs on
| in-memory sqlite (phpunit.xml), so a fresh clone needs no database server.
|
*/

// Browser steps wait on Livewire round trips; under the parallel suite a
// single step can outlast Playwright's 5s default without anything being wrong.
pest()->browser()->timeout(10_000);
