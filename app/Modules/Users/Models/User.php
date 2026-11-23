<?php

namespace App\Modules\Users\Models;

use App\Modules\Users\Enums\UserType;
use Parental\HasChildren;

/**
 * User Model
 *
 * Polimorphic user model that automatically casts records to their specific
 * types (SuperAdmin, Admin) based on the 'type' column.
 */
class User extends Identity
{
    use HasChildren;

    protected $childTypes = [
        UserType::SuperAdmin->value => SuperAdmin::class,
        UserType::Admin->value => Admin::class,
    ];
}
