# Filament Modular Starter

A Laravel 13 + Filament 5 starter kit for applications that grow as modules and serve each audience through its own Filament panel.

[![Laravel](https://img.shields.io/badge/Laravel-13-red.svg)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-5-orange.svg)](https://filamentphp.com)
[![PHP](https://img.shields.io/badge/PHP-8.4-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## Quick start

```bash
laravel new my-app --using=norotaro/filament-modular-starter
cd my-app
php artisan db:seed
npm install && npm run build
composer dev
```

Without the Laravel installer, start with `composer create-project norotaro/filament-modular-starter my-app` instead.

Creating the project copies `.env.example` to `.env`, generates the app key, creates the sqlite database and runs the migrations. `db:seed` adds two accounts, both with the password `password`:

| Panel | URL | Account |
|-------|-----|---------|
| Admin | `/admin` | `admin@example.test` |
| SuperAdmin | `/super-admin` | `super-admin@example.test` |

## What you get

| Piece | Where | What it does |
|-------|-------|--------------|
| Module system | `app/Modules/Core` | Discovers each module's service provider, registers modules in `ModuleRegistry`, and turns them on or off at runtime (`config/modules.php`, the `module.active` middleware). |
| Panels | `app/Panels` | Admin and SuperAdmin panels on a shared base: login, password reset, email verification, notifications and a profile page. Each panel signs in only its own role. |
| Users | `app/Modules/Users` | One `users` table with a role per user type, per-user timezone, and a policy. |
| Partners | `app/Partners` | Contracts for outbound integrations, with a payment gateway interface and a dummy gateway as the reference. |
| Example module | `app/Modules/Example` | A small module that follows every convention. Copy it to start a real one. |
| Guards | `tests/Unit` | Architecture and placement tests that keep the layers apart as the app grows. |
| Agents | `AGENTS.md`, `.claude/`, `.opencode/` | A guide for coding agents and read-only pre-PR reviewer agents. |

## Requirements

- PHP 8.4 and Composer
- Node.js 22 (see `.nvmrc`) and npm
- No database server: development and tests run on sqlite. To use Postgres, see [docs/postgres.md](docs/postgres.md).

Redis is optional. Queues, sessions and cache run on the database. When you enable Redis, `predis` is the default client; install `ext-redis` and set `REDIS_CLIENT=phpredis` for the native one.

## Build your first module

1. Copy `app/Modules/Example/` to `app/Modules/<YourModule>/` and rename the classes.
2. Register the module in its service provider, the way `ExampleServiceProvider` does. The Core module discovers the provider; do not add it to `bootstrap/providers.php`.
3. Add an on/off flag for it to `config/modules.php`; the module's applicability closure reads it.
4. Put UI pieces under the module's `Filament/` directory and mount them from a panel.

[app/README.md](app/README.md) is the full guide: layers, the module system, where each Filament class goes, and how to add a payment gateway.

To make modules depend on a tenant or organization, pass your own context to `ModuleScope::forContext()` and read it in each module's `ModuleDefinition::$applicabilityCheck`.

## Panels and roles

Roles live in `App\Modules\Users\Enums\UserType` and map one-to-one to panels through `App\Panels\Commons\Enums\PanelID`. The starter ships `Admin` and `SuperAdmin`, each with its own panel. The `type` column has no default, so every user gets an explicit role. The Users module README explains how to add one.

To add a panel, copy one under `app/Panels/`, add a case to `PanelID`, and list the provider in `bootstrap/providers.php`. Details are in [app/Modules/Users/README.md](app/Modules/Users/README.md).

## Commands

| Command | What it does |
|---------|--------------|
| `composer dev` | Runs the server, a queue listener, log tailing and Vite together. |
| `composer test` | Runs the Feature and Unit suites in parallel. |
| `composer test:browser` | Runs the browser suite (see below). |
| `composer lint` | Formats every file with Pint, then runs PHPStan. |

## Testing

Tests use Pest on in-memory sqlite, so `composer test` works on a fresh clone with no `.env` and no database server.

The browser suite in `tests/Browser` drives the panels in a headless Chromium. It is not part of `composer test`. Set it up once, then run it:

```bash
npm ci && npm run build
npx playwright install chromium
composer test:browser
```

Shared browser steps, such as signing in through a panel, live in `tests/Helpers/browser.php`.

## Localization

Write strings in English in the code. The app renders English by default. Modules and panels ship an `es.json` with Spanish translations as a working example: set `APP_LOCALE=es` to use them, or add a catalog for your own language the same way.

## Deployment

The `Dockerfile` builds one production image. Inside it, supervisor runs three processes: Laravel Octane on FrankenPHP for HTTP, a queue worker and the scheduler. PHP runs as `www-data`.

```bash
docker build -t my-app .
docker run -p 8000:8000 --env-file .env.production my-app
```

| Setting | Notes |
|---------|-------|
| `PORT` | The port the server listens on, `8000` by default. Behind a proxy that routes to port 80, such as Coolify, set `PORT=80`. |
| HTTPS | With `APP_ENV=production` the app generates `https://` URLs, so put TLS in front (Coolify's proxy does). To try the image over plain http locally, run it with `APP_ENV=local`. |
| `APP_KEY`, `DB_*`, … | Pass the environment at run time; the image never contains `.env`. |
| Migrations | Run `php artisan migrate --force` in the container on each deploy. |
| Database drivers | `pdo_sqlite`, `pdo_pgsql` and `pdo_mysql` are installed. |

`docker/` holds the server config: the Caddyfile (it blocks dotfiles and PHP files under `/storage`), the PHP upload limits and the supervisor programs. The image installs without dev packages, so `db:seed` is not available in production.

Octane workers are long-lived: keep per-request state out of singletons.

## AI tooling

[Laravel Boost](https://laravel.com/docs/boost) is installed for development. Its MCP server is registered in `.mcp.json` and `opencode.json`, and its skills live in `.agents/skills/`. `AGENTS.md` describes the project's rules for coding agents.

## Documentation

- [app/README.md](app/README.md): architecture, the module system, Filament and partner guides.
- `app/Modules/*/README.md`: one guide per module.
- [docs/](docs/README.md): guides that are not architecture, such as running on Postgres.

## License

MIT. See [LICENSE](LICENSE).
