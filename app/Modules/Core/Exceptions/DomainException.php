<?php

namespace App\Modules\Core\Exceptions;

use RuntimeException;

/**
 * Base class for business-rule violations a module throws on purpose, so the
 * application layer can catch them apart from programming errors.
 */
class DomainException extends RuntimeException {}
