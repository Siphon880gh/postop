# E0 graph prompt — Planning and decisions

**Outcome:** resolve the approval gate and leave stable, testable contracts for
implementation. Do not implement product features in E0.

## Milestone graph

`E0.M1 Confirm product decisions` → `E0.M2 Freeze shared contracts` →
`E0.M3 Implementation readiness`

### E0.M1 — Confirm product decisions

Resolve every item in `.agents/state.json.pending_confirmation`: epic map,
working name, user/ICP assumptions, browser scope, upload limit, inactive-wound
history, and revision-conflict policy. Existing explicit requirements may be
accepted without re-asking; genuine product choices require human confirmation.

**Acceptance:** each decision is recorded with its source; no resolved item
remains pending; contradictions with `PLAN.md` are surfaced rather than guessed.

### E0.M2 — Freeze shared contracts

Specify canonical JSON entities, generated ID grammar, revision semantics,
filesystem ownership, authenticated route/action names, POST/redirect/error
shape, offline mutation envelopes, and scope relationships. Keep detail no
deeper than needed to make later milestones compatible.

**Acceptance:** every E1–E6 dependency has one unambiguous contract; examples
validate as JSON; paths can be derived only from validated/generated IDs.

### E0.M3 — Implementation readiness

Turn acceptance criteria into an executable test matrix, establish isolated test
storage/cleanup, and check that required PHP/browser capabilities are available.

**Acceptance:** every `PLAN.md` criterion has an owner and prospective automated
check; environment gaps are recorded; E1 and E7.M1 can begin without an unknown
interface.

