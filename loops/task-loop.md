# Reusable task implementation loop

Substitute `{epic}`, `{milestone}`, `{task}`, and `{acceptance}` before running.

## Prompt

Implement `{task}` for `{epic}` / `{milestone}`. Its acceptance contract is
`{acceptance}`. Preserve every constraint in `PLAN.md` and all decisions in
`.agents/state.json`.

1. **Inspect:** read relevant code and tests; state the smallest intended change.
2. **Record:** create/update the task entry in state with `status: in_progress`,
   `attempts`, and timestamps. An attempt begins when implementation changes are
   made for this task.
3. **Implement:** make the smallest complete change. Do not add dependencies,
   split the self-contained `index.php`, use forbidden browser storage, or fake
   a control that the plan requires to work.
4. **Verify immediately:** run the narrowest deterministic check for the task,
   followed by relevant regression checks. Record each exact command, exit code,
   and a concise result in `verification_history`.
5. **Use failure:** on failure, diagnose from actual output, record
   `failure_summary` and `next_attempt`, then make a targeted correction. Do not
   repeat an unchanged attempt. Run the failing check first on the next attempt.
6. **Finish:** only set the task to `complete` when all its checks pass. Record
   `completed_at` and the evidence. Immediately proceed to the next task.

After five failed implementation attempts for this feature, stop changing that
feature, set it to `blocked_failure`, preserve the last useful diagnostics, and
pause with a concise request for human direction. Environmental inability to run
a check is not a pass: record `verification_blocked`; continue with another
automatable check if one can establish the same behavior, otherwise treat it as
human verification required.

Never delete or weaken a failing test merely to complete the loop. Never mark a
manual visual observation as automated verification.

