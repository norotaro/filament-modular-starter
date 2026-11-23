<?php

namespace App\Modules\Users\Models;

use Parental\HasParent;

/**
 * Admin User Model
 *
 * Represents administrator users, who manage the user population through
 * the admin panel.
 */
class Admin extends User
{
    use HasParent;
}
