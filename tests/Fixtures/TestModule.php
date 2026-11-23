<?php

namespace Tests\Fixtures;

/**
 * Module identifiers owned by the test suite, so Core's module system is tested
 * without depending on any module the application ships.
 */
enum TestModule: string
{
    case Alpha = 'alpha';
    case Beta = 'beta';
}
