<?php

use App\Modules\Core\ModuleManagerServiceProvider;
use App\Panels\Admin\AdminPanelProvider;
use App\Panels\Commons\CommonServiceProvider;
use App\Panels\SuperAdmin\SuperAdminPanelProvider;

return [
    // ModuleManagerServiceProvider MUST be first: it registers Core
    // unconditionally and then auto-discovers every other module and partner
    // provider via glob. Do NOT add module providers below this line.
    ModuleManagerServiceProvider::class,
    AdminPanelProvider::class,
    CommonServiceProvider::class,
    SuperAdminPanelProvider::class,
];
