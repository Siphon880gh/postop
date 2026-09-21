# Reusable milestone closure loop

## Prompt

Close `{milestone}` only after every child task is complete.

1. Map every milestone acceptance statement in its epic prompt to an automated
   command or test assertion.
2. Run syntax/static checks, the focused milestone suite, and all existing
   regression tests. For web behavior, start the PHP server on localhost with an
   isolated temporary storage directory and exercise real HTTP requests,
   redirects, sessions, CSRF, filesystem effects, and response headers.
3. Inspect produced JSON/media paths and assert that no unrelated path changed.
   For UI/PWA work, add browser automation when available; request human review
   only for a property that cannot be established programmatically.
4. Record exact commands and results in the milestone's
   `verification_history`. Feed every failure back through `task-loop.md` as a
   repair task, counting attempts against the affected feature.
5. Set the milestone to `complete` only when every required check passes. Set
   `completed_at`, clear its transient failure fields, and immediately return to
   `graph.md` for the next dependency-ready node.

Do not use a successful narrow test to conceal a failing regression suite.

