# Application Architecture

`app/` has three top-level directories: `Panels/`, `Modules/`, and `Partners/`. Panels present, Modules decide, Partners talk to the outside world. On top of that layout, `Core` provides a runtime module system that switches optional features on and off; the skeleton decides per config flag, not per tenant.

## Documentation conventions

This file is the single source of truth for four things the module READMEs link to instead of restating: the [module system](#module-system), the [module structure convention](#modules), the [Filament integration guide](#filament-integration-guide), and the [partner gateway guide](#partner-gateway-development-guide). What follows is knowledge, not a folder inventory — `ls` for the current instances. These rules govern this file and every README under `app/`, and they are the convention future contributors and AI tools are pointed to:

- Cite by symbol name (`ModuleScope::resolve()`), never by line number. Line references rot on the next commit; symbol names survive refactors.
- Document what is stable: boundaries, invariants, state transitions, intentional design, and negative facts about deliberate choices. For anything volatile, link out instead of quoting.
- Code defects go to the issue tracker with `severity:`, `area:`, and `source:` labels — not into a README. A README that reads like a bug report rots before the fix lands.

Translation convention (skeleton rule, restated in the root README): source strings are English, every module and panel ships one `es.json`, `APP_LOCALE=es` + `APP_FALLBACK_LOCALE=en` makes the catalog the only place Spanish lives.

## Layers

```
Panels → Modules → Partners
   ↓        ↓         ↓
 [UI]   [Business]  [External]
        [Logic]     [Services]
```

The arrows are the *intended* direction: a panel composes module atoms and calls module services; a module reaches an external system only through a partner contract. Keeping them that way is a discipline, not a mechanism — grep the `use` statements when reviewing.

### Panels

`app/Panels/` is the Filament presentation layer. Each panel serves exactly one audience: `PanelID::isAccessibleBy()` is a strict 1:1 mapping, so no role reaches two panels and no panel admits two roles.

All panel providers extend the shared base `App\Modules\Core\Filament\PanelProvider`, which centralizes login, password reset, email verification, database notifications, the profile page (`Users\Filament\Pages\EditProfile`), and the `EnvironmentIndicatorPlugin` every panel gets. The per-user display timezone is registered once by `Users\UserServiceProvider`, not per panel.

**Registration is a hand edit.** Nothing auto-discovers panel providers: the globs in [Provider auto-discovery](#provider-auto-discovery) walk `app/Modules` and `app/Partners`, never `app/Panels`. A new panel provider stays dead code until it is listed in `bootstrap/providers.php` beside `AdminPanelProvider` and `SuperAdminPanelProvider`. Forgetting that line fails silently: Filament never boots the panel, so it gets no routes and no `PanelID` mount. `tests/Unit/ArchitectureTest.php` fails when a panel provider is missing from that file, and when a module or partner provider is listed there by hand.

Panels own no `Models/` directory and no migrations: they present state that modules own.

Panel URLs, from each provider's `->path()`:

| Panel | URL path | Declared in |
|-------|----------|-------------|
| Admin | `admin` | `AdminPanelProvider` |
| SuperAdmin | `super-admin` | `SuperAdminPanelProvider` |

`Panels/Commons/` is this layer's shared drawer: the `PanelID` enum, the `eloquent-panel-user` auth driver (`Auth\PanelUserProvider`) that restricts every user lookup to the current panel's role, read per query, and finds nobody by credentials when no known panel is current, Filament color registration, and a small `routes/web.php` loaded by `CommonServiceProvider`. Panels served on their own domain (`->domain()`) or keyed by a route parameter are tenant mechanics, and the starter deliberately does not carry them.

### Modules

`app/Modules/` holds the business logic, organized as vertical slices: one directory per business capability, containing everything that capability needs.

What follows is the **convention**, not an inventory — modules contain only what they use, so the directories present differ from module to module. `ls` the module you care about for its current shape.

- `Models/` — Eloquent models.
- `Services/` + `Contracts/` — business logic behind interfaces. Consumers depend on the contract, never the concrete class.
- `Database/` — `Migrations/`, `Factories/`, `Seeders/`. Migrations live inside the module and are loaded by its provider's `loadMigrationsFrom()`.
- `Filament/` — reusable UI atoms (`Actions/`, `Schemas/`, `Schemas/Entries/`, `Tables/Columns/`) plus the module's panel plugins. See the [Filament Integration Guide](#filament-integration-guide) for what belongs here versus in a panel.
- `Support/` — mechanism, not policy. Capabilities business logic *calls*: URL signing and routing (`Core\Support\RedirectUrlSigner`, `Route`), result envelopes (`Result`), the module registry (`Core\Support\Modules\ModuleRegistry`). The dependency arrow is one-way: Services may call Support; Support never calls a Service or a Policy. The moment a class decides a business outcome (who, when, whether) it is policy and belongs in `Services/` or `Policies/`. The one exception is a **pure rule**: a function of plain values that reads no database, no container, no actor and no clock, and takes no value derived from them either, so a caller cannot smuggle the actor in as an argument. It lives here so it is unit-tested without a database, and the service that owns the verdict asks it; the actor, the row lock and the side effects stay in the service.
- `Enums/`, `Policies/`, `Notifications/`, `Events/`, `Listeners/`, `Jobs/`, `Observers/`, `Rules/`, `DTOs/`, `Http/`, `Console/`, `Casts/`, `Mappers/` — as needed.
- `routes/`, `lang/`, `resources/views/` — module-owned routes and translations, loaded by the provider via `loadRoutesFrom()`, `loadJsonTranslationsFrom()`, `loadViewsFrom()`. The root `routes/web.php` stays empty; every HTTP route comes from a module, a panel, or Filament.
- **Only modules own models.** Every Eloquent model lives in a module's `Models/` namespace, and a `Models/` namespace holds nothing else. `tests/Unit/ArchitectureTest.php` enforces this, the Support rule above, class-name casing, and provider file names.
- **Factories resolve inside the module.** `Core\Concerns\HasModuleFactory` splits the model FQCN on `Models\` and looks for `{Module}\Database\Factories\{Model}Factory`. A model whose FQCN has no `Models\` segment yields a bogus path and a fatal — keep models under `Models/`.
- **A model holds state and answers about its own.** Columns, relations, casts, scopes, and read predicates over its own loaded state belong on the model: `Identity::isSuperAdmin()` compares its own `type` column, and that is the whole job. A model never decides who may do what — a method that consults the actor, another aggregate, or a workflow outcome is policy and lives in `Policies/` or a service can-method. An existence read across *other* rows is not a question about the self either: the read goes to a service seam, and the verdict to the policy that calls it. The exemption is a list, not a wildcard: `FilamentUser::canAccessPanel()` stays on the model because the framework interface requires it there and Filament's login and panel middleware are its callers. `Identity::canImpersonate()` reads the actor and stays only as the documented hook an optional impersonation package expects; it is a deviation, not a precedent.
- **A class asks for its collaborators; it never grabs them.** Where the container builds the object — HTTP controllers, services, jobs, listeners, policies — the dependency is a constructor parameter. `app(X::class)` inside such a class hides a real dependency behind a locator and contradicts the class's own signature. Where the framework builds the object the rule does not pretend otherwise: `Filament\Actions\Action::__construct()` is final and atoms come from `make()`, and Livewire instantiates components with a bare `new`, so neither an atom nor a panel page can constructor-inject. Those take the collaborator as a typed closure parameter, which Filament resolves from the container and which keeps the dependency in the signature instead of the body. A hand-resolved `app()` survives only where no parameter shape exists, once per class. The second carve-out is about lifetime, not directory, because production runs on Octane with long-lived FrankenPHP workers (`Dockerfile`, `config/octane.php`). `config/octane.php` registers `FlushTemporaryContainerInstances` on `OperationTerminated`, which discards every `scoped()` binding when a request ends, so a container-cached `ModuleScope` never reaches the next request. What no listener can undo is a reference already handed to an object that outlives the request: a `singleton()` or a static built during the first request keeps the `ModuleScope` it was given, for every later request. So the question is the enclosing class's lifetime. A per-request class — `bind()`, or the auto-resolved default, which is what a module service is — may constructor-inject a scoped binding, and should. A `singleton()` resolves it late, at the point of use or through a resolver closure. Adding a scoped binding to `octane.flush` changes nothing: `scoped()` is already discarded every request, and a flush cannot reach a reference an object already holds.
- **Presentation mapping is a module seam, not a controller body.** The shape a view reads — a localized title, a generated route, an ordering, the keys a template declares — is built by the module's mapping layer and handed to the view already built. A controller passes what the seam returned; it does not compose it. Mappers live in `{Module}/Mappers/`, one class per view surface, and a view documents the keys it consumes so the mapper and the template cannot drift apart. Where a mapped value crosses a boundary — a service returning it to a panel, one module handing it to another — it is a typed object in `{Module}/DTOs/`, never an ad-hoc array two callers read differently. This is a review rule: a `->map()` over identifiers is legitimate controller work, and a text scan cannot tell a presentation shape from a query shape.
- **HTTP entrypoints stay flat.** A controller resolves input, calls one seam, and formats what comes back. It never forks on the caller's identity to decide permission — the guest and the signed-in arm of one download are a single ability the policy answers. It never performs a business effect inline: an audit row the controller writes is guaranteed for that controller only, while the seam that does the work logs every caller. It never derives domain output — a filename, a MIME mapping, or a slug is knowledge, not formatting. What it may do is dress a seam's typed refusal as 404 or 403: that verdict was already made elsewhere.

### Partners

`app/Partners/` is the outbound integration layer. It declares no `Models/` directory and no migrations: the outbound layer owns communication, not persisted state. Shared contracts live in `Interfaces/`, the identifying enum in `Enums/PartnerId.php`, and the domain-agnostic DTOs gateways are typed against in `Support/`. Each integration gets its own directory.

The skeleton ships the payment-gateway contract family (`PaymentPartnerInterface`, `PaymentInitResultInterface`, `PaymentWebhookInterface`) and one reference implementation, `DummyPayments/`. Gateways are typed against `Partners\Support\PaymentIntent`, **not** a domain payment model — a payments module maps its record to an intent at the call site. Adding a gateway is a documented procedure: [Partner Gateway Development Guide](#partner-gateway-development-guide).

## Module System

`Core` decides which optional features are active for the current request. `Core/README.md` links here rather than restating any of it.

### Provider auto-discovery

`App\Modules\Core\ModuleManagerServiceProvider` is listed **first** in `bootstrap/providers.php` and does all its work in `register()` — it has no `boot()`. It registers `CoreServiceProvider` unconditionally first, then auto-discovers by glob:

- one directory under `app/Modules`, skipping `Core`
- one directory under `app/Partners`

For each match it builds the FQCN as `App\{Modules|Partners}\{dirname}\{basename}` and registers it only `if (class_exists(...))`.

> **A new module or partner must NOT be added to `bootstrap/providers.php`.** The glob already registers it. Listing it too double-registers the provider.

Three consequences of the exact glob shape:

- **Both globs are exactly one directory level deep.** A provider nested deeper than `{Module}/{Name}ServiceProvider.php` is not matched and must be registered by hand from its module's provider.
- **The filename must equal the class name**, and the class must sit at the module's root namespace. Otherwise `class_exists()` fails and the provider is **silently skipped** — no warning, no exception, no log entry.
- Registration order is `glob()` order — alphabetical by full path.

### Registry, definitions, and scope

All three classes live in `app/Modules/Core/Support/Modules/`.

| Class | Binding | Responsibility |
|-------|---------|----------------|
| `ModuleRegistry` | `singleton`, bound in `CoreServiceProvider::register()` | The catalog. `define()` accepts each identifier exactly once and throws `RuntimeException` on a duplicate. `lock()` is **irreversible — there is no `unlock()`**; in tests, build a fresh registry. `get()` returns `null` for an unknown identifier. |
| `ModuleDefinition` | `final readonly` value object | Four public promoted properties: `identifier`, `applicabilityCheck` (a `Closure` taking a **nullable** context object), and `panelPages` / `panelSubNavigation`, both defaulting to `[]`. `isApplicableTo()` just calls the closure. |
| `ModuleScope` | `scoped`, bound in `CoreServiceProvider::register()` | Per-request resolver. `isActive()` memoizes into `$cache` keyed by identifier value, testing membership with `array_key_exists` so a cached `false` is not recomputed. `withBypass()` builds a scope with `bypass: true`; `isActive()` returns `true` immediately when bypassing, before touching the cache. |

`CoreServiceProvider::boot()` calls `lock()`. Laravel boots providers in registration order and Core is registered first, so **any module provider that calls `define()` from its own `boot()` will throw**. Define in `register()`.

Modules declare themselves in their own providers, in `register()`. For a hypothetical `Orders` module:

```php
// app/Modules/Orders/OrdersServiceProvider.php
$registry = $this->app->make(ModuleRegistry::class);
$registry->define(new ModuleDefinition(
    identifier: ModuleIdentifier::Orders,
    applicabilityCheck: fn (?object $context): bool => (bool) config('modules.orders', true),
));
```

**`ModuleScope::resolve()` is the null-fallback contract.** It returns `null` — not `false` — when it cannot decide: the identifier is not in the registry, or the bound scope has neither bypass nor a context. Callers fall through with `??` to whatever persistence check the application has (the starter has none, so `resolve()` matters once you add a context).

### Scoping modules per tenant

The default `ModuleScope` carries `context: null, bypass: false`, and each module's applicability closure is the whole decision — `Example` reads a config flag; you can gate on anything. To enable modules per tenant or organization:

1. define a context type (a tenant model, or an interface over one),
2. pass it through `ModuleScope::forContext()` from a per-request middleware that re-binds the scope,
3. let closures read enablement off the context.

`ModuleDefinition::isApplicableTo()` and `ModuleScope`'s constructor accept `?object` precisely so steps two and three are additive changes, not signature surgery.

### Identifiers

`App\Modules\Core\Enums\ModuleIdentifier` is a plain `enum: string`. It is the application's code vocabulary: providers register modules under its cases and code asks `ModuleScope` with them. Core itself accepts any string-backed enum, and the `module.active` middleware resolves its parameter through the registry, so Core does not depend on the cases an application ships. If you add a persistence layer for enablement (a stored module key), keep it a **separate** enum: letting the code vocabulary and the stored vocabulary evolve independently is why nothing round-trips between them, and the coupling stays a manual invariant — a name in one without the other fails silently (`ModuleRegistry::has()` false, `resolve()` null, every `??` fallback firing).

### Middleware

**`EnsureModuleActive`** (`app/Modules/Core/Middleware/EnsureModuleActive.php`), aliased `module.active` in `CoreServiceProvider::boot()`. Usage: `Route::middleware('module.active:example')`. **Fail-closed**, with two distinct 403 messages so you can tell which branch fired: an identifier no provider registered *or* no `ModuleScope` bound at all gives `'Module not active.'`; a scope that resolves inactive gives `'Module not active for this context.'`.

### Signed guest redirects

`App\Modules\Core\Support\RedirectUrlSigner` signs a panel URL so a guest can be sent back to the right place after logging in — with no allow-list and no database lookup.

- **Token format**: `base64url(url) . '.' . hex(hmac_sha256(url, app.key))`. Base64url is unpadded (`+`→`-`, `/`→`_`, trailing `=` stripped). The HMAC is computed over the **raw** URL, not the encoded form.
- **Key**: `Config::get('app.key')`, injectable through the constructor for tests. The query parameter name defaults to `r`.
- **`verify()`** is strict and timing-safe: null or empty returns `null`; the token must split into exactly two parts on `.`; `base64_decode(..., true)` runs in strict mode; the result is compared with `hash_equals()` against a freshly computed HMAC. Any mismatch returns `null` and never throws.
- **`append(string $url, string $panelUrl)`** signs `$panelUrl` and appends the token to `$url`. The two arguments are **not interchangeable**.

**There is no TTL.** No expiry, no timestamp, no nonce — the token carries only the URL and its signature. A signed link is **valid forever for a given `APP_KEY`**. Rotating `APP_KEY` invalidates every previously issued link, and the `redirectGuestsTo` closure in `bootstrap/app.php` then silently degrades to `config('app.url')` rather than erroring.

Do not confuse this with `Route::toPanelSignedRoute()` (`app/Modules/Core/Support/Route.php`), which uses Laravel's native `URL::temporarySignedRoute($name, now()->addDays(2), $params)`. That **2-day** signature is a completely separate mechanism for handing out temporary access to a specific panel route.

The `redirectGuestsTo` closure in `bootstrap/app.php` verifies `?r=`, stores `url.intended`, and redirects to the signed panel URL; otherwise it falls back to `config('app.url')`.

## Filament Integration Guide

This application uses a **hybrid pattern** that splits Filament responsibilities between `app/Modules/` and `app/Panels/`. This section is the single source of truth for where a Filament class belongs; module READMEs link here.

### Where Things Go

The example paths use a hypothetical `Orders` module and its `Order` model, to show where each class would land; none of them ship with the starter.

| What | Where | Example |
|------|-------|---------|
| **Resource classes** (CRUD pages, navigation) | `app/Panels/{Panel}/Resources/` | `app/Panels/Admin/Resources/Orders/OrderResource.php` |
| **Table compositions** (columns, filters) | `app/Panels/{Panel}/Resources/{Resource}/Tables/` | `.../Tables/OrdersTable.php` |
| **Infolist compositions** (detail views) | `app/Panels/{Panel}/Resources/{Resource}/Infolists/` | `.../Infolists/OrderInfolist.php` |
| **Form schemas** (form layouts, fields) | Shared: `app/Modules/{Module}/Filament/Schemas/`. Not shared: a dedicated file beside the resource it serves — `app/Panels/{Panel}/Resources/{Resource}/Schemas/{Resource}Form.php`, with the `Tables/` sibling per the Table compositions row. Never embedded in the resource class | `app/Modules/Orders/Filament/Schemas/OrderForm.php` |
| **Action atoms** (buttons, operations) | `app/Modules/{Module}/Filament/Actions/` | `app/Modules/Orders/Filament/Actions/CancelOrderAction.php` |
| **Column atoms** | `app/Modules/{Module}/Filament/Tables/Columns/` | `app/Modules/Orders/Filament/Tables/Columns/OrderStatusColumn.php` |
| **Entry atoms** | `app/Modules/{Module}/Filament/Schemas/Entries/` | `app/Modules/Orders/Filament/Schemas/Entries/OrderTotalEntry.php` |
| **Plugins** | `app/Modules/{Module}/Filament/` | `app/Modules/Orders/Filament/OrdersPlugin.php` |

### Rationale

- **Resource classes in Panels**: resources are a presentation-layer concern — they define navigation, routing, and page composition. Different panels expose different resources over the same module.
- **Table and infolist compositions in Panels**: these wire atoms together for one panel's context.
- **Atoms in Modules**: actions, form schemas, columns, and entries are reusable building blocks that carry *shape* knowledge — which fields, which validations, which columns — and get composed into different resources and widgets. Shape is not a verdict: an atom never decides who may act, whether a workflow stage permits it, or what a missing related record means.
- **Atoms compose UI, not reads**: no `::query()` and no relationship traversal inside an atom — a data read that feeds a decision goes through a module service seam; an inline `Order::query()` inside a column or action closure is not one. `tests/Unit/PlacementGuardTest.php` pins the static-facade half over the atom directories and the whole panel layer; relationship traversal stays a review rule, because a text scan cannot know the receiver's type.
- **Atoms name the permission, they do not compute it**: `->authorize('update')` hands the whole who/whether question to a policy arm — `UserPolicy::update()` is that shape for an `Identity` record. `authorize()` only stores the ability; Filament prepends the record (`parseAuthorizationArguments()`) and resolves the actor downstream in `resolveIsAuthorized()` through `Gate::check()`, so the atom never touches either. A private `canView()` that reads `auth()->user()`, ANDs a record state with a `Gate::forUser()` call, or answers the missing-related-record case is policy written in the UI layer: the arm that owns it belongs in `Policies/`, or in a service can-method. A permission leg needs no second visibility leg: `CanBeHidden::resolveIsHiddenInGroup()` folds the verdict in, so `->authorize('<arm>')` on its own keeps an unauthorized atom out of the layout, and `->hidden()` is reserved for a reason that is *not* the gate — a record state, a feature flag, a mount that does not apply here. Asking the gate again inside `->hidden()` is the second copy of the rule this bullet forbids, and it is worse than redundant: `resolveIsHiddenInGroup()` evaluates the `->hidden()` closure first and returns hidden from there, so the authorization branch below it is never reached.
- **An authorization message needs a message to show**: when a person should see why an affordance is unavailable, that is `authorizationTooltip()` or `authorizationNotification()`, which render the atom with a message instead of hiding it (`CanBeAuthorized::resolveIsAuthorizedOrNotHiddenWhenUnauthorized()`). Both only work when the denial carries a message. A policy arm returning plain `false` — every arm of `UserPolicy` today — has nothing to show, so the atom stays hidden even with those calls set, unless the atom supplies the fallback with `authorizationMessage()` (`CanBeAuthorized::authorizationMessage()`; documented in `vendor/filament/actions/docs/01-overview.md`, the installed package's own copy). A `->hidden()` gate read-back on the same atom makes that pairing inert, for the reason in the bullet above.

A resource therefore stays thin and delegates: a resource's `form()` and `table()` are one-liners handing off to `OrderForm::configure()` and `OrdersTable::configure()`. When a generator emits an embedded schema or table (`--simple`, `--embed-schemas`, `--embed-table`), the embedded content moves into its own file before the work is committed.

### Plugin Registration

Modules that need per-panel state register a Filament plugin class in `Filament/` and panels instantiate it (`OrdersPlugin::make()`), exposing a static `current()` accessor as a typed wrapper around the container binding when other code needs to read it back. Feature flags per panel are fixed at registration time, not per request.

## Partner Gateway Development Guide

How to implement a new payment gateway in `app/Partners/`.

### Contracts

Payment partners are contract-based. `app/Partners/Interfaces/` holds `PaymentPartnerInterface` (the core contract every gateway implements), `PaymentInitResultInterface` (the DTO returned when a payment is initialized), and `PaymentWebhookInterface` (inbound webhook handling — optional; only gateways that receive callbacks implement it). Gateways are identified by the `PartnerId` enum in `app/Partners/Enums/PartnerId.php` and receive `App\Partners\Support\PaymentIntent` as their domain-agnostic input.

### Step-by-step: adding a new gateway

**1. Add a case to the `PartnerId` enum:**

```php
// app/Partners/Enums/PartnerId.php
case Stripe = 'stripe';
```

**2. Create the partner directory:**

```
app/Partners/Stripe/
├── StripePaymentPartner.php     # implements PaymentPartnerInterface
├── StripeApiClient.php          # HTTP client for the gateway API
├── StripeServiceProvider.php    # binds the API client with config, loads views
├── DTOs/
│   └── StripeInitResultDTO.php  # implements PaymentInitResultInterface
└── views/
    └── payment-form.blade.php   # optional custom payment form
```

**3. Implement `PaymentPartnerInterface`** — four methods:

```php
class StripePaymentPartner implements PaymentPartnerInterface
{
    public function getPartnerId(): PartnerId;
    public function initializePayment(PaymentIntent $intent): Result;  // Result<PaymentInitResultInterface>
    public function revertPayment(PaymentIntent $intent): Result;       // Result<array<string, mixed>>
    public function getDisplayPayload(PaymentIntent $intent): ?array;   // array<string, string>|null
}
```

`Result` is `App\Modules\Core\Support\Result` — a covariant either/or with a **private constructor**. Build it with `Result::success($value)` or `Result::failure($message)`; calling `value()` on a failure throws `InvalidArgumentException`.

**4. Implement `PaymentInitResultInterface`** — three accessors:

```php
class StripeInitResultDTO implements PaymentInitResultInterface
{
    public function getPartnerReference(): string;  // stored as partner_reference
    public function getRedirectUrl(): string;        // URL to redirect the user to
    public function getPayload(): Collection;        // raw payload, stored as JSON
}
```

A gateway with no external redirect returns an empty string — see `DummyPayments\DTOs\PaymentResultDTO::getRedirectUrl()` for the shape.

**5. Name the ServiceProvider for auto-discovery.** `ModuleManagerServiceProvider` globs partner providers and registers every match. Placing `StripeServiceProvider.php` at the partner root is enough — do **not** add it to `bootstrap/providers.php`. The filename must equal the class name or `class_exists()` silently skips it. A partner needing no bindings and no views needs no provider at all.

**6. Add the partner to your resolver.** A real app dispatches on the stored partner id (`match ($partnerId) { PartnerId::Stripe => app(StripePaymentPartner::class), default => null }`). The skeleton has no payments module, so nothing resolves yet — the Dummy gateway exists to show the contract, not to be dispatched.

**7. Add configuration** in `config/services.php`:

```php
'stripe' => [
    'api_key' => env('STRIPE_API_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
],
```

**8. (Optional) Add webhook support.** Implement `PaymentWebhookInterface` — `validateRequest(Request): Result<Collection>` then `handle(Collection): Result<string>` — and mount a route for it. A webhook endpoint should respond **HTTP 200** on both validation and handling failure, signalling the result only inside the JSON body: every delivery is acknowledged so the gateway never retries, so a partner webhook service must report failures through the `Result` envelope rather than by throwing its way to a non-200 response.

## Runtime Prerequisites

- **Redis**: optional and unused by shipped code. `predis/predis` is the default client; `ext-redis` is only a `suggest` entry.
- **Queue worker**: `QUEUE_CONNECTION=database`, so jobs sit in the database until a worker picks them up. Locally `composer dev` starts one via `php artisan queue:listen --tries=1`.
- **Sessions/cache**: `SESSION_DRIVER=database`, `CACHE_STORE=database` — the tables come from Core's framework migrations.
