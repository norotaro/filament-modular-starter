<?php

namespace App\Modules\Users\Models;

use Parental\HasParent;

/**
 * Reviewer User Model
 *
 * Skeleton role model: one table read through the Parental inheritance
 * chain. Domain relations for this role belong to the modules that own
 * them: define them in the module, not here.
 */
class Reviewer extends User
{
    use HasParent;
}
