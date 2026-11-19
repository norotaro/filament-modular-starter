<?php

namespace App\Panels\Commons\Enums;

use App\Modules\Users\Enums\UserType;

/**
 * Panel identifiers. Each panel maps to exactly one user type — strict 1:1:
 * no role reaches two panels and no panel admits two roles.
 *
 * Adding a panel means adding a case here, wiring it into `isAccessibleBy()`
 * below and extending the `eloquent-panel-user` provider query in
 * `CommonServiceProvider`. `Identity::canAccessPanel()` fails closed for any
 * panel id that is not in this enum.
 */
enum PanelID: string
{
    case Admin = 'admin';
    case SuperAdmin = 'super-admin';

    public function isAccessibleBy(UserType $userType): bool
    {
        return match ($this) {
            self::Admin => $userType === UserType::Admin,
            self::SuperAdmin => $userType === UserType::SuperAdmin,
        };
    }
}
