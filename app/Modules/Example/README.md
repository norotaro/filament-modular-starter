# Example Module

Reference module that demonstrates every skeleton convention in one place: provider auto-discovery, module registry participation, module-local migrations, factories, a Filament atom, and per-module translations. Delete it when the first real module lands — or copy it as the starting point.

## What this module owns

`ExampleItem` (the demo model), `ExampleServiceProvider` (the registry declaration), one table (`example_items`), one column atom, and an `es.json`.

## How it works

- **Auto-discovery.** `Core\ModuleManagerServiceProvider` globs `app/Modules/*/*ServiceProvider.php` and registers `ExampleServiceProvider` without any `bootstrap/providers.php` entry. The filename equals the class name and the class sits at the module root namespace — otherwise `class_exists()` fails and the provider is silently skipped, with no warning anywhere.
- **Registry declaration in `register()`.** The provider calls `ModuleRegistry::define()` from `register()`, never from `boot()`: `CoreServiceProvider::boot()` locks the registry and Core boots first, so a late `define()` throws `RuntimeException`.
- **Two modules, two defaults.** `ModuleIdentifier::Example` is active unless `config('modules.example')` says otherwise; `ModuleIdentifier::Sample` is inactive unless `config('modules.sample')` enables it. That asymmetry makes both the active path and the fail-closed `module.active` 403 demonstrable without editing code. `ModuleScope::resolve()` returns `null` in the shipped skeleton — the default scope carries no context and no bypass, so it cannot decide until a tenant context is re-introduced (see the scoping section in [`../../README.md`](../../README.md)).
- **Gating.** To guard a route: `Route::middleware('module.active:sample')`. The middleware is fail-closed — an unknown identifier aborts 403 with `'Module not active.'`, an inactive scope aborts 403 with `'Module not active for this context.'`.
- **Module-local everything.** Migrations load via `loadMigrationsFrom()`, translations via `loadJsonTranslationsFrom()`. Factories resolve inside the module through `Core\Concerns\HasModuleFactory`, which requires models to live under `Models/` — a model outside a `Models\` segment yields a bogus factory path and a fatal.

## Filament atom placement

`Filament/Tables/Columns/ExampleNameColumn` is a column atom: reusable, domain-carrying building blocks live in the owning module, panels compose them. See the Filament Integration Guide in [`../../README.md`](../../README.md).

## Layout

Standard module convention: [`../../README.md`](../../README.md). `Example/` contains only the directories a module actually needs — there is no Services/, Policies/ or routes/ here because this demo has none.
