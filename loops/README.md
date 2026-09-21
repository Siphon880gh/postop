# Implementation loops

This directory is the execution playbook for turning `PRODUCT_PLAN.md` into the
application specified by `PLAN.md`. The files are prompts for an implementation
agent, not a claim that the application has already been implemented.

## Start here

1. Read `PLAN.md`, `PRODUCT_PLAN.md`, `.agents/state.json`, and this file.
2. Run `graph.md` to select the next dependency-ready node.
3. Use the matching epic prompt in `epics/` to refine that node into small tasks.
4. Run every task with `task-loop.md`.
5. Close a milestone with `milestone-loop.md`, then close its epic with
   `epic-loop.md`.
6. Update `.agents/state.json` after every transition, attempt, verification,
   pause, and completion. Never infer progress solely from a chat transcript.

## Source-of-truth precedence

When instructions differ, use this order:

1. The current human instruction and confirmed decisions.
2. `PLAN.md` acceptance criteria and constraints.
3. `PRODUCT_PLAN.md` epic boundaries and dependencies.
4. The prompts in this directory.
5. An implementation agent's assumptions.

Do not silently decide an item listed in `pending_confirmation`. A node that
depends on such an item must be marked `blocked_human`; independent work may
continue. Only pause the overall run when no dependency-ready node remains or
when human verification is inherently required.

## Completion contract

A task is complete only after its automated checks pass. A milestone is complete
only after all its tasks and milestone acceptance checks pass. An epic is
complete only after all its milestones pass and its requirements can be traced
to evidence. The program is complete only after every E1–E7 node is complete,
the complete `PLAN.md` acceptance suite passes, and the state has status
`complete`.

Failures are evidence, not permission to skip a check. Capture the command,
exit status, useful output, diagnosis, and next change in state. A materially
revised fix counts as the next attempt. Pause a feature after its fifth failed
implementation attempt and record it as `blocked_failure`; do not weaken tests
or acceptance criteria to obtain a pass.

