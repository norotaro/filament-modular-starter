<?php

namespace App\Modules\Core\Support\Modules;

/**
 * Immutable description of one optional module.
 *
 * The applicability closure decides whether the module is active for the
 * request's scoping context. The skeleton ships with no tenancy, so the
 * context is always null; a multi-tenant application passes its tenant
 * model (or any context object) through `ModuleScope::forContext()`.
 *
 * The identifier may come from any string-backed enum, but the registry keys
 * definitions by its backing value alone: two enums sharing a value collide.
 */
final readonly class ModuleDefinition
{
    /**
     * @param  \Closure(?object): bool  $applicabilityCheck
     * @param  array<string, mixed>  $panelPages
     * @param  array<int, class-string>  $panelSubNavigation
     */
    public function __construct(
        public \BackedEnum $identifier,
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
