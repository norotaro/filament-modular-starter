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

These bind what you write. They extend `app/README.md`, `### Modules` and `## Filament Integration Guide`; where those sections state a rule, they are the authority.

- **A Filament atom names the permission; it never computes it.** Enforce with `->authorize('<ability>')`, which already hides an unauthorized atom, so `->hidden()` is for a reason that is not the gate. The verdict itself lives in `Policies/` or a service method.
- **An HTTP controller stays flat**: resolve the input, call one seam, format the response. A permission verdict, a business effect (an audit row written inline), or a derived response value (filename, MIME type, slug) belongs to the seam. Turning a seam's typed refusal into 404 or 403 belongs to the controller.
- **A class asks for its collaborators.** Use constructor parameters where the container builds the object, a resolved closure parameter where it does not (`Action::__construct()` is final, Livewire instantiates components with a bare `new`), and a hand-resolved `app()` only where neither shape exists, once per class.
- **Presentation mapping is a module seam**: `{Module}/Mappers/` for the shape a view reads, `{Module}/DTOs/` when a mapped value crosses a boundary, never an inline body in a controller.
- **A model holds state, not policy.** Read predicates over its own loaded state stay on the model; a verdict, or an existence read across other rows, goes to `Policies/` or a service. Framework-required methods such as `FilamentUser::canAccessPanel()` are exempt by name.
- Existing code that predates a rule stands; there is no scrub pass. Cleaning up around a change you are already making is welcome.
- One mechanical scan, so writer and reviewer run the identical command, `scripts/review-scans.sh placement [base-ref]` (base defaults to `main`):

  `Gate::` and `::forUser(` are hunted in module `Filament/`, `Http/Controllers/` and `Models/`, because a verdict is never the body of an atom, a controller, or a record class. `app(` is hunted only in `Http/Controllers/`: the container always builds a controller, while the same token is unavoidable in a Filament atom or a Livewire page. Comment-only lines are skipped; a token in a trailing comment on a code line is still reported and needs a stated dismissal. The path tests use `index()` because a `/` inside a bracket class does not parse under every awk.

  What the scan cannot see stays reviewer judgment: whether a condition decides a business outcome or only a UI affordance, a controller body with no token at all (an inline audit row, a derived filename), and a presentation shape built in a controller.

## Comment rules

- Comments explain **why**, never **what**: invariants, contracts, failure modes, and why a registration or placement is load-bearing. The test for writing one: could someone make a wrong change later without it? If not, leave it out.
- Match length to the trap: a subtle constraint gets 2–3 lines, not a paragraph. A long comment is fine when every line carries weight. Link to the source of a rule (`{@see Class::method()}`, a test name, a README section) instead of restating it.
- **Link only to what a clone of this repo contains.** In code, tests, and living docs (`README.md` files), a pointer must resolve inside the repository: a class, a test, a README section. Never cite an id from a document that lives elsewhere (a spec requirement number, a decision code, a tracker id); state the rule in plain words instead.
- No process or history: no pull request numbers, task ids, session ids, or review-finding ids used as history, nothing that reads as "fixed in this batch" or "verified green". Git holds that. The one exception is a status marker for intentionally incomplete work, `TODO(task N.N):` or `UNMOUNTED:`, which states current state; an id may appear only inside such a marker.
- Don't add docblocks or type annotations to code you did not change. Remove dead code rather than commenting it out.
- Living docs never cite code by line number. Cite by symbol name.
- The rule binds added and rewritten lines, not the whole repo. A copied sentence that carries a provenance id counts as new text.
- One mechanical scan for the provenance class, `scripts/review-scans.sh comments [base-ref]`:

  It reports added non-Markdown lines only and passes the two marker shapes. It cannot see provenance with no id shape ("as reviewed", "verified green"), an external spec id, or anything in Markdown; those stay reviewer judgment.
- Delegated writers receive this section in their prompt, run the scan on their own diff before returning, and check by hand for the smells no grep sees: a comment that restates the code, a paragraph that should be a `{@see}` pointer, and a citation of an id from outside the repo.

## Testing and verification

- Test-first: write a failing test (RED) before the fix or feature, then make it pass. Report the real pass/fail counts you observed.
- `vendor/bin/pest`: the suite. `composer test` runs it in parallel through `php artisan test --parallel --compact`.
- `vendor/bin/phpstan analyse`: static analysis (Larastan, configured in `phpstan.neon`).
- `vendor/bin/pint`: formatter. `vendor/bin/pint --test` checks without writing; `composer lint` runs `pint` plus PHPStan.
- Tests assert observable behavior through the public interface, not string matches over source files.

## Pre-PR review

Before a push or pull request, a read-only reviewer pass runs against the exact commits about to be published and returns `VERDICT: approve` or `VERDICT: block`. The reviewers are project subagents in `.claude/agents/` (Claude Code) and `.opencode/agents/` (OpenCode).

- Standard changes: dispatch `pre-pr-review` with the base ref.
- Lenses are chosen by what the change can break, never by size alone. Pick every row the change matches and run the union of their lenses in place of the plain pass:

  | The change touches | Review |
  |---|---|
  | No semantic risk below | one `pre-pr-review` pass |
  | Security and permission boundaries: auth, guards, credentials, secrets, policies | `review-risk` + `review-reliability` |
  | Stored data: migrations, persisted-format changes, edits to existing rows | `review-risk` + `review-resilience` |
  | Concurrency (locks, races, async ordering), queues, retries | `review-resilience` + `review-reliability` |
  | Contracts others consume: public API, CLI flags, config formats, published exports | `review-risk` + `review-readability` |
  | Agent instructions: `AGENTS.md`, `.claude/agents/`, `.opencode/agents/` | one `pre-pr-review` pass |
  | Delivery and environment: CI, deploy, installers, runtime dependency manifests | `review-risk` + `review-resilience` |

  A change over 800 **authored** lines earns all four lenses whatever it touches.
- Authored lines are additions plus deletions of bytes someone wrote. Exclude lockfiles (`composer.lock`, `package-lock.json`), `vendor/`, `public/build`, generated agent-skill copies (`.agents/skills/`), and anything an installer or generator emits.
- Mechanical evidence is gathered once, by the caller: run the suite, `composer lint`, and the comment and placement scans above on the exact HEAD under review, and paste their output into every reviewer prompt. A reviewer that receives that output uses it instead of re-running it.
- Each reviewer runs on the model its agent file pins: `review-risk` on `opus`, because a missed permission or data leak is the costliest miss; `pre-pr-review` and the other lenses on `sonnet`. The OpenCode agents pin no model and use the session's.
- Reviewers are read-only. In Claude Code, `.claude/hooks/pre-pr-review-bash.sh` allows only `git status`/`diff`/`log`/`show`/`grep`/`ls-files`, `composer test`, `php artisan test` and `vendor/bin/pest` with test-selection options, `vendor/bin/phpstan analyse`, `vendor/bin/pint --test`, and `scripts/review-scans.sh`. Commands may not contain quotes, pipes, redirection or substitution, and git, pest, phpstan and pint options that write files or run code are refused. The OpenCode agents carry the same commands as permission globs plus deny globs for those options; globs cannot catch abbreviated git options such as `--ou=`, so the Claude Code hook is the stricter of the two. Keep the two lists in sync.
- Documentation-only change: one `pre-pr-review` pass that checks the claims against the code.
- A rebase or amend that changes the reviewed bytes requires a new review; an approve does not carry over. Agent-instruction files are never exempt, because their prose is runtime behavior.
- BLOCKER or CRITICAL findings introduced by the change stop the push. WARNING and SUGGESTION findings become follow-ups. A correction commit that only answers findings gets one `pre-pr-review` pass over that delta.

## Working agreements

- Conventional commits. Keep code, tests, and docs for one piece of behavior in the same commit.
- Laravel Boost is a dev-only dependency. Its MCP server runs as `php artisan boost:mcp`, and its agent skills live in `.agents/skills/`, the tracked cross-editor copy. The per-editor mirrors (`.claude/skills/`, `.cursor/`, `.junie/`) are gitignored; `php artisan boost:install --skills` recreates them.
- Boost skills are framework guidance, not this project's policy. When a generated skill disagrees with this file, this file wins.
- Never deploy with dev dependencies installed or with `APP_DEBUG=true`.
