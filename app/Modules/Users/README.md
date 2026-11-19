# Users Module

Owns the `users` table, the four role types the panels are built around, and the per-user timezone that every Filament panel reads. Roles are not separate tables — they are one table read through an inheritance chain.

**This file is the single source of truth for the role and panel rule set in the skeleton.**

## What this module owns

Here: the `users` schema (one consolidated base migration, born with `timezone` and soft deletes), the role models, `UserType`, `UserPolicy`, the `EditProfile` and `Login` Filament pages, and `UserServiceProvider`.

Deliberately **not** here:

| Concern | Where it lives instead |
|---|---|
| The `PanelID` enum itself | `app/Panels/Commons/Enums/PanelID.php`. Panels owns the enum; this module owns the rule that consumes it — `Identity::canAccessPanel()`. |
| Panel providers, user resources | `app/Panels/*`. Users supplies models and auth pages; Panels wires them into a panel. |
| Domain relations per role | The modules that own them. The skeleton's `Participant` and `Reviewer` are deliberately bare — the role models exist so a module can extend them through their own classes, not by editing this module. |

### The inheritance chain — direction matters

```
Illuminate\Foundation\Auth\User   (Authenticatable)
  └── Identity            <- the base Authenticatable; owns the `users` table
        └── User          <- adds Parental\HasChildren, the casting decorator
              ├── Admin       HasParent only
              ├── Participant HasParent only
              ├── Reviewer    HasParent only
              └── SuperAdmin  HasParent only
```

**The base `Authenticatable` is `Identity`, not `User`.** `User` is a *subclass* that adds polymorphic casting. Inverting this inverts the whole design: `Identity` is the raw row, `User` is the row that auto-casts to a role.

This is `tightenco/parental` **single-table inheritance on a `type` column — not polymorphic relationships.** `User::$childTypes` maps each `UserType->value` string to its class, and every role subclass uses `HasParent`, so the whole chain shares the one `users` table. `type` is a string with DB default `participant`, cast to `UserType`, and it is **never in `$fillable`**: the role column is written only by trusted flows — seeders, factories, and code that force-fills it explicitly.

### When to type-hint `Identity` instead of `User`

The model's own docblock: *"Base identity model representing the raw user record in the database. Use this model when you need to avoid the automatic polymorphic casting provided by the User model's HasChildren trait."*

`UserPolicy` accepts `Identity` on both the actor and the model, and it is registered as `Gate::policy(Identity::class, UserPolicy::class)`. Registering on the base class is what makes authorization apply to resources that render raw `Identity` rows: `Identity` is the *parent* of `User`, and a policy registered only for `User` never resolves for `Identity`. Everything else — relations, factory states, panel checks — type-hints `User`.

## Roles and panel matrix

`PanelID::isAccessibleBy()` is **strict one role per panel, one panel per role**: no role reaches two panels and no panel admits two roles. In the skeleton two panels are wired; the other two roles keep their enum cases and models so the first event-style panel added has its audience ready — until then they simply cannot sign in.

| | **SuperAdmin** | **Admin** | **Reviewer** | **Participant** |
|---|---|---|---|---|
| value | `super_admin` | `admin` | `reviewer` | `participant` (**DB default**) |
| panel + path | `super-admin` | `admin` | — | — |
| login possible | yes | yes | no panel claims it | no panel claims it |
| `UserPolicy` | `before()` returns **true for every ability** | `viewAny`/`create` yes; `view`/`update` yes (never SuperAdmin rows for update; never self for delete) | `$user->id === $model->id` for `view`/`update` | same as Reviewer |

`UserPolicy::before()` returns **`null`, not `false`**, for non-SuperAdmins, so every specific ability method still runs.

Panel access is enforced by `Identity::canAccessPanel()`: `PanelID::tryFrom($panel->getId())`, then `$panelId !== null && $panelId->isAccessibleBy($this->type)`. An **unknown panel id returns false** — it fails closed. The login attempt itself is filtered earlier by the `eloquent-panel-user` auth driver (`Panels\Commons\Auth\PanelUserProvider`), which restricts the user query per panel and finds nobody by credentials outside a known panel, so an Admin typing a SuperAdmin email gets a credential error rather than a post-login rejection.

`canImpersonate()` / `canBeImpersonated()` remain as the documented rule — only Super Admins may impersonate, and a Super Admin may never be impersonated — even though the impersonation package was pruned with the rest of the domain; the methods are what a re-added package reads.

## Lifecycle

### Creating a user

`Identity` has no `creating` hook in the skeleton: the original enforced tenant membership there (non-SuperAdmin rows inherited `organization_id` from the authenticated user and threw when it stayed null). When a tenancy layer returns, that guard belongs in a `static::creating` closure again — factories and seeders are written so nothing else needs to know.

`users.id` is `bigIncrements`; email is globally unique (in the multi-tenant original uniqueness was scoped per organization/conference — the composite index came with the tenancy model, not with the users table).

### The timezone resolver — load-bearing

`UserServiceProvider::boot()` calls `FilamentTimezone::set()` with a lazy resolver returning `Auth::user()?->timezone ?? config('app.timezone')`. One registration at application boot serves every panel; it is what makes per-user display time work without a per-panel plugin. The `timezone` column is nullable and edited through `EditProfile` (required, searchable select of `timezone_identifiers_list()`). The original registered this **once in Users, never in panels** — keep it that way.

## Traps and intentional oddities

- **`UserServiceProvider` binds `Illuminate\Contracts\Auth\Authenticatable` to `User::class`** "for Filament's Import feature", overriding a framework contract binding globally.
- **`EditProfile` composes the parent's form components and appends the timezone select**; it no longer overrides the email field. The original scoped email uniqueness per tenant and *failed closed* (deterministic validation error) when the tenant could not be resolved — when you re-add tenancy, restore both halves of that, not just the `where()`.
- **`Login` exists to hide the registration affordance** and the subheading when multi-factor is mid-flow; with no registration pages in the skeleton, panels call `->login(Login::class)` only.

## Extension points

**To add self-registration for a role** (the original let Participants sign up openly on a public panel), create `{Module}/Filament/Concerns/HasRegisterForm.php`-style concerns in the **panel-facing module or panel** that owns the flow, force the `type` there, and register `->registration(...)` on the panel. Role-forcing must bypass `$fillable` (force-fill or `forceCreate`) — that is why `type` is not fillable.

## Layout

Standard module convention: [`../../README.md`](../../README.md#modules). Auth UI lives in `Filament/Pages/`; there is no `Traits/` directory.
