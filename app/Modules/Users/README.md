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
| Domain relations per role | The modules that own them. The role models are deliberately bare, so a module extends a role through its own classes, not by editing this module. |

### The inheritance chain — direction matters

```
Illuminate\Foundation\Auth\User   (Authenticatable)
  └── Identity            <- the base Authenticatable; owns the `users` table
        └── User          <- adds Parental\HasChildren, the casting decorator
              ├── Admin       HasParent only
              └── SuperAdmin  HasParent only
```

**The base `Authenticatable` is `Identity`, not `User`.** `User` is a *subclass* that adds polymorphic casting. Inverting this inverts the whole design: `Identity` is the raw row, `User` is the row that auto-casts to a role.

This is `tightenco/parental` **single-table inheritance on a `type` column — not polymorphic relationships.** `User::$childTypes` maps each `UserType->value` string to its class, and every role subclass uses `HasParent`, so the whole chain shares the one `users` table. `type` is a string with **no DB default**, so a row created without a role fails instead of landing in one. It is cast to `UserType` and it is **never in `$fillable`**: the role column is written only by trusted flows — seeders, factories, and code that force-fills it explicitly.

### When to type-hint `Identity` instead of `User`

The model's own docblock: *"Base identity model representing the raw user record in the database. Use this model when you need to avoid the automatic polymorphic casting provided by the User model's HasChildren trait."*

`UserPolicy` accepts `Identity` on both the actor and the model, and it is registered as `Gate::policy(Identity::class, UserPolicy::class)`. Registering on the base class is what makes authorization apply to resources that render raw `Identity` rows: `Identity` is the *parent* of `User`, and a policy registered only for `User` never resolves for `Identity`. Everything else — relations, factory states, panel checks — type-hints `User`.

## Roles and panel matrix

`PanelID::isAccessibleBy()` is **strict one role per panel, one panel per role**: no role reaches two panels and no panel admits two roles.

| | **SuperAdmin** | **Admin** |
|---|---|---|
| value | `super_admin` | `admin` |
| panel + path | `super-admin` | `admin` |
| `UserPolicy` | `before()` returns **true for every ability** | every ability, except updating, deleting or retyping a SuperAdmin row and deleting itself |

`UserPolicy::before()` returns **`null`, not `false`**, for non-SuperAdmins, so every specific ability method still runs.

Panel access is enforced by `Identity::canAccessPanel()`: `PanelID::tryFrom($panel->getId())`, then `$panelId !== null && $panelId->isAccessibleBy($this->type)`. An **unknown panel id returns false** — it fails closed. The login attempt itself is filtered earlier by the `eloquent-panel-user` auth driver (`Panels\Commons\Auth\PanelUserProvider`), which restricts the user query per panel and finds nobody by credentials outside a known panel, so an Admin typing a SuperAdmin email gets a credential error rather than a post-login rejection.

`canImpersonate()` / `canBeImpersonated()` remain as the documented rule — only Super Admins may impersonate, and a Super Admin may never be impersonated. The starter ships no impersonation package; the methods are the hook one reads when you add it.

## Lifecycle

### Creating a user

`Identity` has no `creating` hook. If your app adds tenancy, enforce membership in a `static::creating` closure (for example, non-SuperAdmin rows inherit `organization_id` from the authenticated user and creation fails when it stays null); factories and seeders are written so nothing else needs to change.

`users.id` is `bigIncrements`; email is globally unique. Making it unique per tenant takes a composite index that belongs with the tenant model, not with the users table.

### The timezone resolver — load-bearing

`UserServiceProvider::boot()` calls `FilamentTimezone::set()` with a lazy resolver returning `Auth::user()?->timezone ?? config('app.timezone')`. One registration at application boot serves every panel; it is what makes per-user display time work without a per-panel plugin. The `timezone` column is nullable and edited through `EditProfile` (required, searchable select of `timezone_identifiers_list()`). Keep it registered **once in Users, never in panels**.

## Traps and intentional oddities

- **`UserServiceProvider` binds `Illuminate\Contracts\Auth\Authenticatable` to `User::class`** "for Filament's Import feature", overriding a framework contract binding globally.
- **`EditProfile` composes the parent's form components and appends the timezone select**; it does not override the email field. If you scope email uniqueness per tenant, also *fail closed* (a deterministic validation error) when the tenant cannot be resolved; a `where()` alone is not enough.
- **`Login` exists to hide the registration affordance** and the subheading when multi-factor is mid-flow; with no registration pages in the skeleton, panels call `->login(Login::class)` only.

## Extension points

**To add a role**, add its case to `UserType` (label, color, description), a bare model extending `User` with `HasParent`, and its entry in `User::$childTypes`. The role signs in only once a panel claims it in `PanelID::isAccessibleBy()`, and `UserPolicy` denies it every ability until you grant one there.

**To add self-registration for a role** (for example, letting a new role sign up on a public panel), create `{Module}/Filament/Concerns/HasRegisterForm.php`-style concerns in the **panel-facing module or panel** that owns the flow, force the `type` there, and register `->registration(...)` on the panel. Role-forcing must bypass `$fillable` (force-fill or `forceCreate`) — that is why `type` is not fillable.

## Layout

Standard module convention: [`../../README.md`](../../README.md#modules). Auth UI lives in `Filament/Pages/`.
