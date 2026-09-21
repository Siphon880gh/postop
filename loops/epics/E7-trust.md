# E7 graph prompt — Record Trust, Safety and Inclusive Use

**Outcome:** every feature is resilient, scoped, perceivable, operable, and
honest about its limitations. Apply this prompt continuously.

## Milestone graph

`E7.M1 Safe mutation primitives` begins with E1; `E1.M3 + E5.M3` →
`E7.M2 Security and integrity audit`; `E6.M5 + E7.M2` →
`E7.M3 Accessibility and responsive audit` → `E7.M4 End-to-end acceptance`

### E7.M1 — Safe mutation primitives

Build atomic locked JSON writes, safe IDs/path containment, exact recursive
deletion, CSRF, validation, redirect-after-POST, flash, and storage errors before
feature code depends on them.

**Acceptance:** concurrency and injected-write-failure tests keep valid JSON;
traversal/symlink/foreign-ID tests cannot escape ownership; destructive requests
are POST-only and CSRF protected.

### E7.M2 — Security and integrity audit

Audit every route and filesystem operation, upload/media response, revision, and
failure boundary. Check for databases, remote services, forbidden storage APIs,
embedded image data, secrets, and orphan files.

**Acceptance:** automated adversarial and filesystem consistency checks pass;
all findings are repair tasks in their owning milestone, not waived findings.

### E7.M3 — Accessibility and responsive audit

Audit semantics, labels, alt text, focus, keyboard behavior, contrast, non-color
status, confirmations/errors, and desktop/tablet/mobile layouts.

**Acceptance:** automated accessibility/browser checks pass at representative
viewports; all controls are reachable and named; automated screenshots have no
clipping/overlap. Pause for human verification only for remaining inherently
subjective usability review.

### E7.M4 — End-to-end acceptance

Run every numbered `PLAN.md` acceptance criterion from clean seed through
refresh, restart, online mutation, full sync, offline use, conflict, clearing,
and logout. Verify the disclaimer and non-diagnostic positioning.

**Acceptance:** all criteria have passing evidence; every epic is complete;
state is internally consistent and set to `complete`; no unresolved failure or
human-required item is concealed.

