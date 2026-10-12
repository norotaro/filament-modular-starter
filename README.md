# Modular Panels Starter

**A modular Laravel 12 + Filament 5 application skeleton**

[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4-purple.svg)](https://php.net)

---

## Overview

Modular Panels Starter is a Laravel 12 + Filament 5 architecture skeleton extracted from the production Teuka codebase, stripped of its domain and its tenancy layer. What survives is the part that was doing the real work: a runtime module system, a multi-panel Filament presentation layer, a contract-driven partner/integration layer, and the file-layout conventions that let a team grow the app without stepping on itself.

What is in the box:

- **`app/Modules/Core`** — the module system: `ModuleRegistry`, `ModuleDefinition`, `ModuleScope`, provider auto-discovery, the `module.active` middleware, shared utilities (`Result`, `RedirectUrlSigner`, `HasModuleFactory`), and the shared Filament `PanelProvider` base every panel extends.
- **`app/Modules/Users`** — the minimal auth slice: one `users` table read through a Parental single-table-inheritance role chain, per-user timezone (the load-bearing `FilamentTimezone` resolver), `EditProfile`, and a role-per-panel access rule.
- **`app/Modules/Example`** — a reference module demonstrating every convention; delete or copy it when the first real module lands.
- **`app/Panels`** — Admin and SuperAdmin as empty skeleton panels, plus `Commons` (the `PanelID` enum and per-panel authentication query).
- **`app/Partners`** — the outbound-integration layer: payment-gateway contracts, `PartnerId`, and a Dummy gateway as the reference implementation.

There is **no tenancy and no domain** here. Module applicability closures take a nullable context object; the skeleton always passes `null` and reads enablement flags from `config/modules.php` (see `app/Modules/Example/README.md` for both the active and the fail-closed path). Where the original kept its per-organization enablement, you plug your own context type back in — `ModuleScope::forContext()` and `ModuleDefinition::$applicabilityCheck` are the two seams.

Source strings are English. Every module and panel ships an `es.json`, and `.env.example` sets `APP_LOCALE=es` with `APP_FALLBACK_LOCALE=en`: the Spanish catalog translates the English sources, and anything missing falls back to the English key itself. **This convention is a skeleton rule** — write English literals in code, add one `es.json` per module/panel, no other translation shape is supported.

Everything beyond this paragraph — the module system internals, where Filament classes belong, how to add a payment gateway, and the documentation conventions these files follow — lives in **[app/README.md](app/README.md)**.

### Technology Stack

Constraints are from `composer.json` / `package.json`.

| Area | Package | Constraint |
|------|---------|-----------|
| Language | PHP | `^8.4` (with `ext-redis`) |
| Framework | `laravel/framework` | `^12.0` |
| Admin UI | `filament/filament` | `^5.0` |
| Reactivity | `livewire/livewire` | transitive (via Filament) |
| CSS | `tailwindcss` | `^4.1` |
| Testing | `pestphp/pest` | `^4.0` |
| Static analysis | `larastan/larastan` | `^3.0` |
| Formatting | `laravel/pint` | `^1.24` |
| Roles | `tightenco/parental` | `^1.4` |
| Audit trail | `spatie/laravel-activitylog` | `^4.10` |

Node is pinned to 22 by `.nvmrc`; assets build with Vite (`laravel-vite-plugin`).

**Hard runtime requirement:** `ext-redis` is a `require` entry in `composer.json` — `composer install` fails without it. It is inherited from the production stack; `predis/predis` is also installed so you can drop the extension requirement if you never run Redis.

**Primary keys:** bigint auto-increment on `users` and `example_items`. UUID/ULID strategies belong to domain models that were removed — pick per aggregate when you add them.

---

## 🚀 Using This as a Template

Create a new project from this repo, then rename the identity to match it:

```bash
gh repo create my-project -p norotaro/modular-panels-starter --clone
```

Rename checklist: `composer.json` (`name`/`description`), `package-lock.json` (`name`), the `config/app.php` default app name, `APP_NAME`/`DB_DATABASE` in `.env.example` and `.env`, and this README's title and intro. Then build your first module by copying `app/Modules/Example/` — do **not** add its provider to `bootstrap/providers.php`; the Core glob auto-discovers it. Tenancy (per-context module enablement, tenant routes) is intentionally absent; wire it back through `ModuleScope::forContext()` and `ModuleDefinition::$applicabilityCheck` only if your project needs it.

---

## 🛠️ Development

### Prerequisites

- PHP 8.4+ with the `redis` extension
- Composer, Node.js 22 (`.nvmrc`) and npm
- No database server needed: development and tests run on **sqlite**

### Local Setup

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

`php artisan migrate --seed` runs module-local migrations (loaded by each provider via `loadMigrationsFrom`), then `Database\Seeders\DatabaseSeeder`, which seeds one Super Admin and one Admin (password `password`).

### Panels

| Panel | URL path | Role that can sign in |
|-------|----------|-----------------------|
| Admin | `/admin` | Admin |
| SuperAdmin | `/super-admin` | SuperAdmin |

Both extend the shared base in `App\Modules\Core\Filament\PanelProvider` (login, password reset, email verification, database notifications, profile page, environment indicator). Add a third panel by copying one of them and extending `PanelID` — unknown panel ids are failed closed by `Identity::canAccessPanel()` alone (the per-role login query lets them through unfiltered).

### Key Commands

All defined in `composer.json`; prefer them over invoking the tools directly.

```bash
composer dev          # serve + queue:listen + pail logs + vite, via concurrently
composer test         # php artisan test --parallel --compact --ansi
composer test:serial  # same, without --parallel (easier debugging)
composer test:fresh   # config:clear, then the parallel suite
composer lint         # ./vendor/bin/pint --diff=main, then ./vendor/bin/phpstan analyse
```

---

## 🧪 Testing

Pest 4, two suites: `tests/Feature` and `tests/Unit` (`phpunit.xml`).

The suite runs on **in-memory sqlite** with a committed test-only `APP_KEY` — a deliberate departure from the production app it was extracted from (which required a local PostgreSQL). `composer test` works on a fresh clone with no `.env`, no database server, and no Redis.

`phpunit.xml` also forces `APP_ENV=testing`, `CACHE_STORE=array`, `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`, and `SESSION_DRIVER=array`. Schema is per test: individual test files opt into `RefreshDatabase`/`LazilyRefreshDatabase` — it is not global in `tests/Pest.php`.

```bash
composer test
php artisan test --testsuite=Feature
php artisan test --filter=some_test_name
```

---

## 👤 Users & Roles

Four roles, defined in `App\Modules\Users\Enums\UserType`, mapped 1:1 to panels through `App\Panels\Commons\Enums\PanelID`: SuperAdmin and Admin each own a skeleton panel; Reviewer and Participant keep their role models and enum cases so a second-level panel has its audience ready — they simply cannot sign in until a panel claims them.

The full rule set — the Parental inheritance chain and when to type-hint `Identity` over `User`, the policy, and the timezone resolver — is owned by [app/Modules/Users/README.md](app/Modules/Users/README.md).

---

## 📖 Documentation

- [App Architecture](app/README.md) — layers, module system, Filament integration guide, partner gateway guide, documentation conventions
- [Module READMEs](app/Modules/) — one per module under `app/Modules/*/README.md`
- [Docs conventions](docs/README.md) — what lives in `docs/`

---

<p align="center">
  <strong>A skeleton is only useful if you can boot it in five minutes. This one boots in four.</strong>
</p>
