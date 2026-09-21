# E6 graph prompt — User-Controlled Offline Continuity

**Outcome:** users deliberately create a complete device copy, use it offline,
stage visible changes, and reconcile without silent overwrite.

## Milestone graph

`E5.M3` → `E6.M1 Installable PWA shell` → `E6.M2 Full patient sync lifecycle`
→ `E6.M3 Offline reader` → `E6.M4 Outbox and pending overlays` →
`E6.M5 Replay and conflict recovery`

### E6.M1 — Installable PWA shell

Serve the manifest, icons, service worker, and offline fallback from authenticated
`index.php` actions with correct types, headers, scope, and no external assets.

**Acceptance:** manifest/service-worker validation passes; shell reopens offline;
the worker cannot turn arbitrary paths into public media; no clinical copy is
created before explicit opt-in.

### E6.M2 — Full patient sync lifecycle

Implement authenticated sync manifest/snapshot, estimate and storage check,
privacy confirmation, persistence request, progress/cancel/resume, versioned full
media caching, update diff/removal, incomplete state, and clear/logout behavior.

**Acceptance:** all libraries/data/original photos are enumerated and verified;
cancel stays incomplete; resume preserves valid downloads; update fetches changed
items and evicts deletions; clear is patient/app scoped; logout clears by default
and warns for pending edits; no encryption guarantee is claimed.

### E6.M3 — Offline reader

Render the completed snapshot immediately and support full library/date/note/
gallery browsing with cache-first synced media and a truthful timestamp/status.

**Acceptance:** after sync and server shutdown, reopen and navigate every seeded
state/photo without network; incomplete copy is not called available; cached UI
does not wait on a doomed network request; no `localStorage`/`sessionStorage`.

### E6.M4 — Outbox and pending overlays

Use IndexedDB for ordered envelopes and selected blobs covering every mutation
class required by `PLAN.md`, with pending overlays and explicit review/sync.

**Acceptance:** staged records survive reopen, are distinguishable from server
records, never claim canonical success, enforce quota errors without damaging
the completed copy, and preserve dependencies/order for upload and metadata.

### E6.M5 — Replay and conflict recovery

Implement user-triggered safe replay, server-side final validation/writes,
expected revisions, stop-on-conflict, retry/reload choices, and authoritative
placeholder replacement.

**Acceptance:** successful replay is idempotent and clears only accepted items;
mixed operations preserve dependency order; rejected/conflicted work remains;
concurrent server change is never overwritten silently; reconnection never
auto-syncs merely because Background Sync exists.

