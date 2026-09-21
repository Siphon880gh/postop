# E2 graph prompt — Progress Libraries and Timeline Navigation

**Outcome:** users can manage distinct libraries and navigate every valid date.

## Milestone graph

`E1.M3` → `E2.M1 Library model and revision` → `E2.M2 Library CRUD` →
`E2.M3 Timeline navigation`

### E2.M1 — Library model and revision

Seed at least two libraries and implement canonical validation, fixed/custom
types, dates, descriptions, IDs, and monotonic content revisions.

**Acceptance:** seed covers required states; malformed records are rejected
safely; revisions change exactly on canonical mutations and persist atomically.

### E2.M2 — Library CRUD

Implement real POST-backed create/edit/delete forms, ownership checks,
confirmation, safe scoped deletion, flash, and redirects.

**Acceptance:** CRUD persists after reload; required fields and custom type are
validated server-side; CSRF/foreign IDs fail; delete cannot escape the intended
library directory or remove sibling records.

### E2.M3 — Timeline navigation

Render the library selector/summary and a scalable start-date-through-today
timeline with month paging/grouping, previous/next, and Today/latest behavior.

**Acceptance:** boundary dates work; future/pre-start dates cannot be selected;
long ranges remain bounded; activity, empty, and day-note chips expose text or
accessible names rather than color alone.

