# Project instructions for coding agents

This app is built on Filament Modular Starter: a Laravel 13 + Filament 5 skeleton with a runtime module system, a multi-panel Filament presentation layer, and a contract-driven partner layer for outbound integrations. `README.md` describes what ships in the box; `app/README.md` is the architecture contract.

## Architecture contract

- `app/README.md` answers every placement question. Read the relevant section before deciding where a class goes; placement is never answered from memory or from the nearest file's style, and delegated writers receive the same sections.
  - `## Layers` and its `### Panels`, `### Modules`, `### Partners` subsections: the direction `Panels → Modules → Partners`, what each layer may own, and the module directory convention.
  - `## Module System`: provider auto-discovery, the registry, scoping, and middleware.
  - `## Filament Integration Guide` → `### Where Things Go`: which Filament classes live in a module and which in a panel.
  - `## Partner Gateway Development Guide`: the procedure for adding an integration.
  - `## Runtime Prerequisites`: what the app needs from its environment.
  - `## Documentation conventions`: how `README.md` files under `app/` are written.
- `tests/Unit/ArchitectureTest.php` enforces the mechanical part of that contract. When it fails, fix the placement, not the test.

## Placement rules

These are the starter's default rules for what you write; change them here when your project decides otherwise. They extend `app/README.md`, `### Modules` and `## Filament Integration Guide`; where those sections state a rule, they are the authority.

- **A Filament atom names the permission; it never computes it.** Enforce with `->authorize('<ability>')`, which already hides an unauthorized atom, so `->hidden()` is for a reason that is not the gate. The verdict itself lives in `Policies/` or a service method.
- **An HTTP controller stays flat**: resolve the input, call one seam, format the response. A permission verdict, a business effect (an audit row written inline), or a derived response value (filename, MIME type, slug) belongs to the seam. Turning a seam's typed refusal into 404 or 403 belongs to the controller.
- **A class asks for its collaborators.** Use constructor parameters where the container builds the object, a resolved closure parameter where it does not (`Action::__construct()` is final, Livewire instantiates components with a bare `new`), and a hand-resolved `app()` only where neither shape exists, once per class. This one is not taste: under Octane, an object that outlives the request and captured `ModuleScope` keeps serving the first request's scope (`app/README.md`, `### Modules`, explains the lifetime rule).
- **Presentation mapping is a module seam**: `{Module}/Mappers/` for the shape a view reads, `{Module}/DTOs/` when a mapped value crosses a boundary, never an inline body in a controller.
- **A model holds state, not policy.** Read predicates over its own loaded state stay on the model; a verdict, or an existence read across other rows, goes to `Policies/` or a service. Framework-required methods such as `FilamentUser::canAccessPanel()` are exempt by name.
- Existing code that predates a rule stands; there is no scrub pass. Cleaning up around a change you are already making is welcome.

## Comment rules

- Comments explain **why**, never **what**: write one only when, without it, someone could make a wrong change later.
- Living docs cite code by symbol name, never by line number.

## Testing and verification

- Test-first: write a failing test (RED) before the fix or feature, then make it pass. Report the real pass/fail counts you observed.
- `vendor/bin/pest`: the suite. `composer test` runs it in parallel through `php artisan test --parallel --compact`.
- `vendor/bin/phpstan analyse`: static analysis (Larastan, configured in `phpstan.neon`).
- `vendor/bin/pint`: formatter. `vendor/bin/pint --test` checks without writing; `composer lint` runs `pint` plus PHPStan.
- Tests assert observable behavior through the public interface, not string matches over source files.

## Working agreements

- Conventional commits. Keep code, tests, and docs for one piece of behavior in the same commit.
- Laravel Boost is a dev-only dependency. Its MCP server runs as `php artisan boost:mcp`, and its agent skills live in `.agents/skills/`, the tracked cross-editor copy. The per-editor mirrors (`.claude/skills/`, `.cursor/`, `.junie/`) are gitignored; `php artisan boost:install --skills` recreates them.
- Boost skills are framework guidance, not this project's policy. When a generated skill disagrees with this file, this file wins.
- Never deploy with dev dependencies installed or with `APP_DEBUG=true`.
