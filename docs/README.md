# Docs

`docs/` holds end-user and reviewer-facing documents that are not architecture: roadmaps, workflow guides, runbooks. Architecture lives next to the code it describes — the root `README.md` for scope and setup, `app/README.md` for the architecture contract, and one `README.md` per module under `app/Modules/`.

Conventions these files follow (the same rules `app/README.md` sets for module docs):

- Cite code by symbol name, never by line number.
- Document stable knowledge — boundaries, invariants, deliberate absences — not a folder inventory.
- Code defects belong in the issue tracker, not in a README.

The test suite enforces the citation rule: see `tests/Unit/Docs/CodeReferenceGuardTest.php`.
