<?php

namespace App\Modules\Users\Models;

use Parental\HasParent;

/**
 * Participant User Model
 *
 * Skeleton role model: one table read through the Parental inheritance
 * chain. Domain relations for this role belong to the modules that own
 * them — add them back in the module, not here.
 */
class Participant extends User
{
    use HasParent;
}
