# Filament Modular Starter

**A modular Laravel 13 + Filament 5 application skeleton**

[![Laravel](https://img.shields.io/badge/Laravel-13-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4-purple.svg)](https://php.net)

---

## Overview

Filament Modular Starter is a Laravel 13 + Filament 5 architecture skeleton extracted from the production Teuka codebase, stripped of its domain and its tenancy layer. What survives is the part that was doing the real work: a runtime module system, a multi-panel Filament presentation layer, a contract-driven partner/integration layer, and the file-layout conventions that let a team grow the app without stepping on itself.

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

| Area | Package | Constraint | Resolved |
|------|---------|-----------|----------|
| Language | PHP | `^8.4` | 8.4.23 |
| Framework | `laravel/framework` | `^13.0` | 13.43.0 |
| Admin UI | `filament/filament` | `^5.0` | 5.10.1 |
| Reactivity | `livewire/livewire` | transitive (via Filament) | 4.4.8 |
| CSS | `tailwindcss` | `^4.1` | 4.1.18 |
| Testing | `pestphp/pest` | `^5.0` | 5.3.7 |
| Test runner | `phpunit/phpunit` | transitive (via Pest) | 13.4.7 |
| Runtime | `laravel/octane` | `^2.21` | 2.21.0 |
| Static analysis | `larastan/larastan` | `^3.0` | 3.13.0 |
| Formatting | `laravel/pint` | `^1.24` | 1.32.1 |
| Roles | `tightenco/parental` | `^1.4` | 1.6.0 |
| Audit trail | `spatie/laravel-activitylog` | `^5.0` | 5.1.1 |

Node is pinned to 22 by `.nvmrc`; assets build with Vite (`laravel-vite-plugin`).

**Redis is optional.** Nothing in the shipped code uses it: queues, sessions and cache run on the database. When you turn Redis on, `predis/predis` is the default client (`REDIS_CLIENT=predis`); install `ext-redis` and set `REDIS_CLIENT=phpredis` for the faster native client.

**Primary keys:** bigint auto-increment on `users` and `example_items`. UUID/ULID strategies belong to domain models that were removed — pick per aggregate when you add them.

---

## 🚀 Create a New App

With the [Laravel installer](https://laravel.com/docs/installation):

```bash
laravel new my-app --using=norotaro/filament-modular-starter
```

Or with Composer alone:

```bash
composer create-project norotaro/filament-modular-starter my-app
```

Either way, Composer copies `.env.example` to `.env`, generates the app key, creates `database/database.sqlite` and runs the migrations. Then:

```bash
cd my-app
npm install && npm run build
composer dev
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

### AI Tooling

[Laravel Boost](https://laravel.com/docs/boost) is a dev dependency. `.mcp.json` and `opencode.json` register its MCP server (`php artisan boost:mcp`), and its agent skills live in `.agents/skills/`. Run `php artisan boost:install --skills` to regenerate the per-editor copies (`.claude/skills/`, `.cursor/`, `.junie/`), which stay out of git.

## 🧪 Testing

Pest 5 (on PHPUnit 13), two suites: `tests/Feature` and `tests/Unit` (`phpunit.xml`).

The suite runs on **in-memory sqlite** with a committed test-only `APP_KEY` — a deliberate departure from the production app it was extracted from (which required a local PostgreSQL). `composer test` works on a fresh clone with no `.env`, no database server, and no Redis. The repo commits a comment-only `.env.testing` anchor: with `APP_ENV=testing` (phpunit.xml), Laravel's env bootstrapper loads it instead of probing for an absent `.env`, whose suppressed read failure PHPUnit 13 would otherwise record as a warning on every booted test. Every test value still comes from phpunit.xml — keep the anchor comment-only.

`phpunit.xml` also forces `APP_ENV=testing`, `CACHE_STORE=array`, `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`, and `SESSION_DRIVER=array`. Schema is per test: individual test files opt into `RefreshDatabase`/`LazilyRefreshDatabase` — it is not global in `tests/Pest.php`.

```bash
composer test
php artisan test --testsuite=Feature
php artisan test --filter=some_test_name
```

---

## 🚀 Production (Coolify)

Coolify builds the image with Nixpacks, which reads `nixpacks.toml` at the repo root. The build phase runs `php artisan octane:install --server=frankenphp --no-interaction`, so no FrankenPHP server config is committed — it is generated at build time. At container start, `start.sh` transforms `nginx.template.conf` via Nixpacks' `prestart.mjs` (shipped by the Nixpacks PHP provider, not this repo) and hands off to supervisor, which runs four processes:

| Process | Command |
|---------|---------|
| `worker-nginx` | `nginx -c /etc/nginx.conf` — listens on `${PORT}`, proxies to `127.0.0.1:8000` |
| `worker-octane` | `php artisan octane:start --server=frankenphp --host=127.0.0.1 --port=8000 --workers=auto --max-requests=500` |
| `worker-laravel` | `php artisan queue:work --sleep=30 --tries=3 --max-time=3600` (`QUEUE_CONNECTION=database`) |
| `worker-scheduler` | `while true; do php artisan schedule:run; sleep 60; done` |

Two deliberate choices carried over from the source app:

- **`composer install --no-dev --ignore-platform-reqs`** in the install phase. `--no-dev` keeps Pest, PHPStan and Faker out of the image, so `php artisan db:seed` does not run in production: the seeders use factories, which need Faker. `--ignore-platform-reqs` is kept from the source app's deploy, where it covered a hard `ext-redis` requirement that no longer exists; the image's PHP extensions have not been checked against the lock file, so drop the flag only after a test deploy.
- **nginx `deny` patch** on `location ~ ^/storage/.*\.php$`: blocks serving or executing PHP files dropped under the `public/storage` symlink.

`config/octane.php` is the published stock config — nothing in it is deployment-specific; supervisor passes `--server=frankenphp` explicitly, so the `OCTANE_SERVER` default (roadrunner) never takes effect in production.

**Octane caveat:** workers are long-lived, so per-request state must not live in singletons.

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

## 📄 License

MIT. See [LICENSE](LICENSE).
