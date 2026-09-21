# E4 graph prompt — Scoped Notes and Context

**Outcome:** timestamped notes can be managed and recognized at exactly their
library, day, or wound scope.

## Milestone graph

`E3.M3` → `E4.M1 Scoped note model and CRUD` →
`E4.M2 Note indicators and exact-scope UI`

### E4.M1 — Scoped note model and CRUD

Implement multiple-note storage and POST-backed add/edit/delete for library,
day, and wound scopes with generated note IDs and timestamps.

**Acceptance:** all nine CRUD/scope combinations persist; ownership, IDs, date,
CSRF, and empty content are validated; a mutation cannot move or expose a note
across scope; revisions update consistently.

### E4.M2 — Note indicators and exact-scope UI

Expose note controls and exact lists on selectors, date chips, library summary,
and wound cards using accent, icon/text, and numeric accessible badges.

**Acceptance:** counts update after every mutation and refresh; `Notes 2` has an
accessible `2 notes` equivalent; day notes do not falsely mark wound activity;
presence and scope remain understandable without color.

