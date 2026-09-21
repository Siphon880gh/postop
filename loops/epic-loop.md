# Reusable epic closure loop

## Prompt

When all milestones for `{epic}` are complete, trace each item in that epic's
`Includes` section and each applicable `PLAN.md` acceptance criterion to a
passing automated check. Run the accumulated suite from a clean seeded state and
again after a restart/refresh. Confirm that persisted state, files, and revision
values survive the restart.

Record the evidence under the epic. Any failure reopens the owning milestone and
enters `task-loop.md`; it is not patched outside the loop. Mark the epic complete
only when coverage has no unexplained gaps. Then update the top-level state and
continue through `graph.md` without waiting for approval.

For E7.M4, run the complete acceptance suite, check the repository for forbidden
dependencies/storage APIs and unexpected files, and verify that every E1–E7
epic is complete. Only then set top-level `status: complete` and
`next_action: null`. Human verification may supplement evidence but must never
replace an automated check that is feasible.

