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
    ->in('Feature');

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
