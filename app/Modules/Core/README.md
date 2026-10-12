# Core Module

Foundational infrastructure every other module depends on: the runtime module system, the shared Filament panel base, signed guest redirects, and a handful of cross-cutting utilities. Core owns **no domain models and no Filament resources**.

## What this module owns

The module system itself is documented once, in [`../../README.md`](../../README.md#module-system) — registry, definitions, scope, middleware and provider auto-discovery. **This file does not restate it.** What follows is the Core-specific precision that doc omits, plus the traps.

Here: `Support/` (`Result`, `RedirectUrlSigner`, `Route`, and the module-system primitives under `Support/Modules/`), `Concerns/HasModuleFactory`, `Filament/PanelProvider` (the shared base all panels extend), `Middleware/EnsureModuleActive`, `Enums/`, `Exceptions/`, the framework migrations, and the default `en`/`es` lang directories plus a `Core/lang/es.json`.

Deliberately **not** here:

| Concern | Where it lives instead |
|---|---|
| The module-system explanation | [`../../README.md`](../../README.md#module-system) — the single source of truth. |
| The panel providers | `app/Panels/*`. Core supplies only the base class they extend. |
| User/role models and auth pages | `../Users/`. Core's panel base loads `Users\Filament\Pages\EditProfile`. |
| Domain seeders | Their own modules. The root `Database\Seeders\DatabaseSeeder` is the only aggregator. |

## How it works

1. **Global middleware.** `bootstrap/app.php` configures only `trustProxies(at: '*')` and the `redirectGuestsTo` closure. It registers **no** middleware aliases, appends, groups or priority changes.
2. **`CoreServiceProvider::register()`** binds `ModuleRegistry` as a singleton and `ModuleScope` as `scoped` — the binding is a factory, so the scope auto-resolves with `context: null, bypass: false` for every request unless something re-binds it (the skeleton ships nothing that does; see [Scoping without tenancy](../../README.md#scoping-without-tenancy) for how to add a per-request binder back).
3. **`CoreServiceProvider::boot()` locks the registry**, registers the `module.active` alias, forces https in production, loads Core's migrations and lang, and defines the `Str::plain` macro.

### Registry, definition and scope — the Core-specific details

- **`ModuleRegistry::define()` is define-once, guarded in this order**: a locked registry throws `RuntimeException('ModuleRegistry is locked after boot.')`, then a duplicate identifier throws `"Module '{$key}' already registered."`. It returns `$this`, so calls chain.
- **`lock()` is irreversible — there is no `unlock()`.** The docblock is explicit: *in tests, create a fresh ModuleRegistry instance rather than trying to unlock*. `CoreServiceProvider::boot()` calls it, behind the comment *"Register modules in service provider `register()` methods, not `boot()`"*. Because Core is registered first and Laravel boots providers in registration order, `lock()` fires before any module's `boot()`.
- **There is no `ModuleRegistry::resolve()`.** The registry's null-returning accessor is `get()`; `resolve()` is a **static on `ModuleScope`**.
- **`ModuleDefinition`** is `final readonly` with four promoted public properties: `identifier`, `applicabilityCheck` (a `\Closure` taking a **nullable** `?object` context), and `panelPages` / `panelSubNavigation`, both defaulting to `[]`. `isApplicableTo()` just invokes the closure.
- **`ModuleScope` caches per request** in a private array keyed by identifier value, tested with `array_key_exists` so a cached `false` is not recomputed. `isActive()` checks `$this->bypass` **first, before touching the cache**. `withBypass()` sets bypass and **leaves context null**.
- **`resolve()` returns `null` whenever it cannot decide**: the identifier is not in the registry, or the bound scope has neither bypass nor a context. With no tenancy wired, every non-registered read goes through `??`, so keep callers tolerant of null.

### `EnsureModuleActive` — fail-closed 403

Aliased as `module.active` in `CoreServiceProvider`. `handle()` takes the identifier as a string parameter and aborts 403 on **two distinct conditions in one guard** — an unknown identifier string, or no scope bound at all — with `'Module not active.'`, then aborts 403 with the different message `'Module not active for this context.'` when the scope says inactive. The two messages tell you which branch fired. Guest-facing routes outside every panel keep the container-default scope; if a closure cannot decide for a null context, gate those routes with a policy instead — the original app learned this on its download links.

### `RedirectUrlSigner` — no TTL

Mechanics are documented once in [`../../README.md`](../../README.md#signed-guest-redirects). The Core-specific facts:

- **It has NO TTL.** A signature is valid **forever** for a given `APP_KEY`, and rotating `APP_KEY` invalidates every previously issued link — after which the guest-redirect closure silently degrades to `config('app.url')` rather than erroring. **Do not document a TTL for this class.**
- **Do not confuse it with `Route::toPanelSignedRoute()`**, which wraps `URL::temporarySignedRoute($routeName, now()->addDays(2), $params)`. That 2-day TTL is a separate Laravel-native mechanism.
- `append(string $url, string $panelUrl)` **signs `$panelUrl` but appends to `$url`** — the two arguments are not interchangeable.
- `verify()` uses **strict** `base64_decode(..., true)` and `hash_equals()`, so it is timing-safe. The key is injectable for tests and defaults to `Config::get('app.key')`.
- `Route::toPanelRoute()` / `toPanelSignedRoute()` accept an optional bare domain and pin the generated URL's origin via `URL::useOrigin`, restoring it in a `finally`. That is the hook point for a future per-tenant domain table; the skeleton passes no domain and gets `app.url`-based absolute URLs.

## Traps and intentional oddities

- **`ModuleScope`'s `scoped()` binding is default-constructible** (`?object $context = null`, `bool $bypass = false`), so any request without a binder silently gets `context = null, bypass = false` and `resolve()` returns `null` for every identifier.
- **Both provider auto-discovery globs are exactly one directory level deep** (`ModuleManagerServiceProvider`). A provider nested deeper is not matched and must be registered by hand. The **filename must equal the class name** and the class must sit at the module root namespace, or `class_exists` skips it **without any warning**. Core itself is skipped by name because it is registered first, unconditionally. `ModuleManagerServiceProvider` has **no `boot()`** — everything happens in `register()`.
- **`app/` has NO `Providers/` directory and there is no `AppServiceProvider`.** Conventional model-to-policy discovery plus the explicit `Gate::policy(Identity::class, UserPolicy::class)` in `UserServiceProvider` are the whole authorization surface.
- **`Str::plain()` is a global macro defined inside a module provider** (`CoreServiceProvider::boot()`). It strips HTML tags, decodes entities and collapses whitespace; it is invisible to IDEs and static analysis at call sites and unavailable until Core boots.
- **`Core/lang/` is loaded as both a namespaced path and a JSON path from the same directory.**
- **`Core/Database/Migrations/` holds the framework migrations** — cache, jobs, activity log and notifications. The app's `database/migrations` equivalent lives inside modules. The activity-log and notifications tables are pre-consolidated: they ship in their final shape, not as a chain of historical patches.
- **`Concerns/`, not `Traits/`.** The convention is repo-wide — Users uses `Filament/Concerns/` too if you add one.
- **`HasModuleFactory` assumes the FQCN contains `Models\`.** A model outside a `Models\` segment yields the same string from both `Str::before()` and `Str::after()`, producing a bogus factory path and a fatal. This trait is what lets each module keep factories inside its own tree instead of `database/factories`.
- **`FileType`'s backing values are MIME types, not extensions** — matching must compare MIME strings.
- **`NoResultsFoundException` is the only class in `Exceptions/`** — throw it from a bulk-download-style service, catch it in the controller, declare it via `@throws` on the contract.
- **`Result` has a private constructor** — only `success()` and `failure()` build one — and **`value()` throws `InvalidArgumentException('Cannot retrieve value from a failed result.')`** on a failure. `isSuccess()` / `isFailure()` carry `@phpstan-assert-if-true` annotations so the throw is statically unreachable after a guard.

## Layout

Standard module convention: [`../../README.md`](../../README.md#modules). Deviations: helpers live in `Support/` with the module system nested under `Support/Modules/`; traits live in `Concerns/`, not `Traits/`; and `Filament/` contains only the shared panel base class rather than any resource or action.
