<?php

namespace App\Modules\Core\Exceptions;

use RuntimeException;

class NoResultsFoundException extends RuntimeException
{
    public function __construct(string $message = 'No results found for the given filters')
    {
        parent::__construct($message);
    }
}
