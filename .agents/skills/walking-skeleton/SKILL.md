---
name: walking-skeleton
description: Generate a dependency-ordered, phase-based task list from a set of ADRs
license: MIT
compatibility: No external workflow CLI required.
metadata:
author: Aircury
version: "1.0"
---

Generate an actionable, dependency-ordered task list from the existing ADRs.

**Steps**

1. **Load artifacts**

Required: `specs/decisions/<ADR-XXXX-name>.md`.

2. **Generate `specs/changes/walking-skeleton/skeleton.md`**

For this skill execution do NOT follow any execution mode and do NOT follow TDD.

Start `caveman full` mode.

Write the list of tasks required to complete everything described in the accepted ADRs in dependency order, then add
tasks for the follow-ups of all the ADRs that are relevant for the previous list of tasks.

Structure tasks like:

```md
# Walking Skeleton tasks:

- [ ] [T01] [ADR-0001] Description
```

Task format: `- [ ] [TaskID] [ADR-XXXX] Description

 - Each task must be specific and immediately executable.
 - Order: structure → containers → makefile → setup → frameworks → dependencies → linting → features -> tests -> cleanup.

3. **Validate**

Before finishing:
 - Perform a consistency analysis across all available tasks and the existing ADRs.
 - Ensure no task is vague and that they are clear.

After writing skeleton.md output task count.

4. **Execute tasks**

For each pending task, in dependency order:

 - Announce the task being worked on.
 - Review its linked ADR to ensure the outcome is clear.
 - Load relevant skills.
 - Perform the task.
 - Mark complete in skeleton.md: `- [ ]` → `- [x]`.

**Pause if:**
 - A task is unclear — ask for clarification before implementing.
 - Implementation reveals an issue — suggest updating skeleton.md.
 - User interrupts.

Before finishing:
 - Delete all installed dependencies.
 - Destroy all containers.
 - Regenerate all containers.
 - Install all the dependencies
 - Execute the full test suite.
 - Delete superfluous or dead code in generated files.
 - Execute the full test suite again.
 - Deprecate the walking skeleton ADR without superseeding.

**Guardrails**
 - Halt on task failures — do not skip ahead.
 - Update task checkbox immediately after completion.
