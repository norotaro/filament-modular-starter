<?php

namespace App\Modules\Core\Enums;

/**
 * Code-level module identifier used in middleware, routing, and the programmatic ModuleRegistry.
 *
 * This is the application's internal module vocabulary. Add one case per
 * optional module (like `Example` below); the backing string is what the
 * `module.active` middleware parameter and the registry keys use.
 *
 * If you re-introduce a persistence layer for module enablement (for example
 * a per-tenant `module_key` enum stored in a database table), keep it
 * separate from this enum: letting the two evolve independently is exactly
 * why the module system never reads stored strings directly. The coupling
 * between the code vocabulary and the persisted vocabulary is a manual
 * invariant — nothing round-trips between them.
 */
enum ModuleIdentifier: string
{
    case Example = 'example';
    case Sample = 'sample';
}
