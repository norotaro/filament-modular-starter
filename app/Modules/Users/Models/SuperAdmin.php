<?php

namespace App\Modules\Users\Models;

use Parental\HasParent;

/**
 * Super Admin User Model
 *
 * Represents super administrator users who have system-wide privileges
 * and are not bound to any specific organization.
 */
class SuperAdmin extends User
{
    use HasParent;
}
