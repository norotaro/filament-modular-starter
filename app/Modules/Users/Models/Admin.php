<?php

namespace App\Modules\Users\Models;

use Parental\HasParent;

/**
 * Admin User Model
 *
 * Represents administrator users who have administrative privileges
 * within their organization.
 */
class Admin extends User
{
    use HasParent;
}
