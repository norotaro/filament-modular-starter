<?php

namespace App\Modules\Core\Support\Modules;

use App\Modules\Core\Enums\ModuleIdentifier;

/**
 * Immutable description of one optional module.
 *
 * The applicability closure decides whether the module is active for the
 * request's scoping context. The skeleton ships with no tenancy, so the
 * context is always null; a multi-tenant application passes its tenant
 * model (or any context object) through `ModuleScope::forContext()`.
 */
final readonly class ModuleDefinition
{
    /**
     * @param  \Closure(?object): bool  $applicabilityCheck
     * @param  array<string, mixed>  $panelPages
     * @param  array<int, class-string>  $panelSubNavigation
     */
    public function __construct(
        public ModuleIdentifier $identifier,
        public \Closure $applicabilityCheck,
        public array $panelPages = [],
        public array $panelSubNavigation = [],
    ) {}

    /** Evaluates the module's applicability closure against the given context. */
    public function isApplicableTo(?object $context): bool
    {
        return ($this->applicabilityCheck)($context);
    }
}
