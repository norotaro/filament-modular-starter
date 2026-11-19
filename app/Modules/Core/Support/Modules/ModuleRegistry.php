<?php

namespace App\Modules\Core\Support\Modules;

use App\Modules\Core\Enums\ModuleIdentifier;
use Illuminate\Support\Collection;
use RuntimeException;

class ModuleRegistry
{
    /** @var array<string, ModuleDefinition> */
    private array $definitions = [];

    private bool $locked = false;

    public function define(ModuleDefinition $definition): self
    {
        if ($this->locked) {
            throw new RuntimeException('ModuleRegistry is locked after boot.');
        }

        $key = $definition->identifier->value;

        if (isset($this->definitions[$key])) {
            throw new RuntimeException("Module '{$key}' already registered.");
        }

        $this->definitions[$key] = $definition;

        return $this;
    }

    /**
     * Locks the registry against further registrations.
     * This is irreversible by design — in tests, create a fresh ModuleRegistry instance rather than trying to unlock.
     */
    public function lock(): void
    {
        $this->locked = true;
    }

    public function get(ModuleIdentifier $identifier): ?ModuleDefinition
    {
        return $this->definitions[$identifier->value] ?? null;
    }

    /** Checks whether a module identifier is registered in the catalog. */
    public function has(ModuleIdentifier $identifier): bool
    {
        return isset($this->definitions[$identifier->value]);
    }

    /**
     * @return Collection<string, ModuleDefinition>
     */
    public function all(): Collection
    {
        return collect($this->definitions);
    }
}
