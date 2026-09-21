# E3 graph prompt — Longitudinal Wounds and Daily Recording

**Outcome:** users can maintain sites and distinguish recorded updates from
missing observations on every selected day.

## Milestone graph

`E2.M3` → `E3.M1 Wound lifecycle` → `E3.M2 Daily update lifecycle` →
`E3.M3 Empty and partial day states`

### E3.M1 — Wound lifecycle

Implement wound create/read/edit, active state, and confirmed safe cascade
delete according to the confirmed history policy.

**Acceptance:** mutations persist and bump revision; deactivation/history follows
the recorded decision; delete removes only owned updates/media and leaves no
metadata orphan; invalid ownership/CSRF is rejected.

### E3.M2 — Daily update lifecycle

Implement selected-day notes shell and explicit per-wound update create/edit/
delete, including note-only and zero-photo updates.

**Acceptance:** explicit updates persist only for valid library dates and owned
wounds; note-only counts as updated and says `No photos added`; deleting an
update safely handles its media and does not create blank-day files merely for
viewing.

### E3.M3 — Empty and partial day states

Seed and render a blank date, a partial date, and complete activity. Show every
applicable active wound and accurate update status.

**Acceptance:** blank day says `No wound updates recorded`; an unupdated wound is
visible and says `Not updated on this date`; state remains correct after refresh,
deactivation, update creation, and update deletion.

