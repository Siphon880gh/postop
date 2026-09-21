# Dependency graph prompt

Use this prompt at the beginning of every orchestration pass.

## Prompt

You are coordinating implementation of the Postop wound-progress gallery.
Read `PLAN.md`, `PRODUCT_PLAN.md`, `.agents/state.json`, and `loops/README.md`.
Validate the state using `loops/state.schema.json`. Reconcile state against the
working tree and test evidence; source files alone do not prove completion.

Select exactly one dependency-ready milestone from the graph below. Prefer the
lowest numbered milestone, except that applicable E7 guardrails must be built
with the feature that first needs them. If a milestone is already `in_progress`,
resume it before selecting another. Never start a node whose prerequisites have
not passed.

```mermaid
flowchart TD
  E0M1[E0.M1 Confirm product decisions] --> E0M2[E0.M2 Freeze shared contracts]
  E0M2 --> E0M3[E0.M3 Implementation readiness]
  E0M3 --> E1M1[E1.M1 Runtime and persistence foundation]
  E7M1[E7.M1 Safe mutation primitives] --> E1M1
  E1M1 --> E1M2[E1.M2 Authentication and protected routes]
  E1M2 --> E1M3[E1.M3 Patient entry and shell]
  E1M3 --> E2M1[E2.M1 Library model and revision]
  E2M1 --> E2M2[E2.M2 Library CRUD]
  E2M2 --> E2M3[E2.M3 Timeline navigation]
  E2M3 --> E3M1[E3.M1 Wound lifecycle]
  E3M1 --> E3M2[E3.M2 Daily update lifecycle]
  E3M2 --> E3M3[E3.M3 Empty and partial day states]
  E3M3 --> E4M1[E4.M1 Scoped note model and CRUD]
  E4M1 --> E4M2[E4.M2 Note indicators and exact-scope UI]
  E4M2 --> E5M1[E5.M1 Authenticated media and validation]
  E5M1 --> E5M2[E5.M2 Uploads and placeholders]
  E5M2 --> E5M3[E5.M3 Photo metadata, order, and deletion]
  E5M3 --> E6M1[E6.M1 Installable PWA shell]
  E6M1 --> E6M2[E6.M2 Full patient sync lifecycle]
  E6M2 --> E6M3[E6.M3 Offline reader]
  E6M3 --> E6M4[E6.M4 Outbox and pending overlays]
  E6M4 --> E6M5[E6.M5 Replay and conflict recovery]
  E7M1 --> E7M2[E7.M2 Security and integrity audit]
  E1M3 --> E7M2
  E5M3 --> E7M2
  E6M5 --> E7M3[E7.M3 Accessibility and responsive audit]
  E7M2 --> E7M3
  E7M3 --> E7M4[E7.M4 End-to-end acceptance]
```

E7.M1 is initiated with E1 and remains a cross-cutting prerequisite. Re-run
E7 checks in every later milestone rather than postponing safety.

For the selected milestone, load its `loops/epics/E*.md` prompt. Change its
state to `in_progress`, set `current_epic_id` and `current_milestone_id`, and
write `last_updated_iso`. Decompose only the selected milestone into tasks that
each have a concrete deliverable and an automated verification command. Execute
each task through `loops/task-loop.md`. Then use `loops/milestone-loop.md`.

If no node is ready, explain the exact unmet dependency in state. Pause only if
human verification is required or a feature has accumulated five failed
attempts. Otherwise select the next ready node and continue without asking for
permission.

