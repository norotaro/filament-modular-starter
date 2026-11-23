<?php

namespace App\Modules\Users\Models;

use Parental\HasParent;

/**
 * Super Admin User Model
 *
 * Represents super administrator users, who have system-wide privileges.
 */
class SuperAdmin extends User
{
    use HasParent;
}
