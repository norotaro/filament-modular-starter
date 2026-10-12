<?php

namespace App\Modules\Core\Support\Modules;

use App\Modules\Core\Enums\ModuleIdentifier;

/**
 * Per-request resolver that decides which modules are active.
 *
 * The skeleton has no tenancy: the default scope carries a null context and
 * every module decides its own applicability from configuration. A
 * multi-tenant application re-adds a context object (for example its tenant
 * model) and re-binds this scope per request with `forContext()`.
 */
class ModuleScope
{
    /**
     * @var array<string, bool> Per-request cache of isActive() results.
     *                          Automatically cleared between requests because ModuleScope is request-scoped (scoped binding).
     */
    private array $cache = [];

    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly ?object $context = null,
        private readonly bool $bypass = false,
    ) {}

    public static function forContext(ModuleRegistry $registry, ?object $context): self
    {
        return new self($registry, $context);
    }

    public static function withBypass(ModuleRegistry $registry): self
    {
        return new self($registry, bypass: true);
    }

    /** Checks whether a module is active for the current request context at runtime. */
    public function isActive(ModuleIdentifier $identifier): bool
    {
        if ($this->bypass) {
            return true;
        }

        $key = $identifier->value;

        if (! array_key_exists($key, $this->cache)) {
            $definition = $this->registry->get($identifier);
            $this->cache[$key] = $definition !== null
                && $definition->isApplicableTo($this->context);
        }

        return $this->cache[$key];
    }

    public function context(): ?object
    {
        return $this->context;
    }

    /** Whether this scope was created with bypass (all modules active regardless of enablement). */
    public function isBypass(): bool
    {
        return $this->bypass;
    }

    /**
     * Pages contributed by modules that are active for this scope.
     *
     * @return array<string, mixed>
     */
    public function activePanelPages(): array
    {
        $pages = [];

        foreach ($this->registry->all() as $definition) {
            if ($this->isActive($definition->identifier)) {
                $pages = array_merge($pages, $definition->panelPages);
            }
        }

        return $pages;
    }

    /**
     * Sub-navigation items contributed by modules that are active for this scope.
     *
     * @return array<int, class-string>
     */
    public function activePanelSubNavigation(): array
    {
        $items = [];

        foreach ($this->registry->all() as $definition) {
            if ($this->isActive($definition->identifier)) {
                $items = array_merge($items, $definition->panelSubNavigation);
            }
        }

        return $items;
    }

    /**
     * Resolves whether a module is active using the current request scope.
     *
     * Returns true/false when scope is properly initialized (bypass or a
     * context object). Returns null when scope cannot decide (module not
     * registered in the registry, or auto-resolved with no context and no
     * bypass), so callers can use the null-coalescing operator (`??`) to
     * fall through to their own persistence check.
     *
     * Pattern: `ModuleScope::resolve(ModuleIdentifier::X) ?? $someEnablementCheck`
     */
    public static function resolve(ModuleIdentifier $identifier): ?bool
    {
        if (! app(ModuleRegistry::class)->has($identifier)) {
            return null;
        }

        $scope = app(self::class);

        if ($scope->isBypass() || $scope->context() !== null) {
            return $scope->isActive($identifier);
        }

        return null;
    }
}
