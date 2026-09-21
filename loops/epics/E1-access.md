# E1 graph prompt — Trusted Access and Patient Entry

**Outcome:** an authenticated demo user can safely enter the one-patient app and
sign out without protected data leaking.

## Milestone graph

`E7.M1` → `E1.M1 Runtime and persistence foundation` →
`E1.M2 Authentication and protected routes` → `E1.M3 Patient entry and shell`

### E1.M1 — Runtime and persistence foundation

Create the self-contained `index.php`, writable storage bootstrap, atomic JSON
helpers, exact seed-once behavior, and actionable failure responses.

**Acceptance:** PHP syntax passes; first run seeds exactly one `Sample Patient`;
repeat/restart is idempotent; forced unwritable storage fails clearly; no
database, remote dependency, or extra runtime source file is introduced.

### E1.M2 — Authentication and protected routes

Implement demo login/logout, session-only authentication, CSRF issuance and
validation, flash/redirect behavior, and a deny-by-default route boundary.

**Acceptance:** correct and incorrect logins behave distinctly; protected HTML,
JSON, and media reject anonymous requests; mutations reject missing/bad CSRF;
logout invalidates access and contains no clinical data in the session.

### E1.M3 — Patient entry and shell

Implement the Patients screen, selection of the sole patient, back navigation,
responsive semantic shell, network-status location, and disclaimer.

**Acceptance:** the complete login → patient → shell → logout flow survives
refresh; labels/focus/order are machine-auditable; no patient CRUD is implied.

