---
name: walking-skeleton
description: Plan, specify, and build a walking skeleton from bundled ADRs.
disable-model-invocation: true
---

# Walking Skeleton

Greenfield project workflow in three explicit commands.

## Parse

Accept `/walking-skeleton <command>`.

| Command | Purpose |
|---------|---------|
| `plan` | Choose bundled ADRs, write `specs/decisions/`, compile `ADR-0000` |
| `spec` | Define the first end-to-end walking-skeleton spec from ADRs |
| `build` | Implement the walking skeleton from ADRs and a reviewed spec |

- Empty or unknown command: show usage (`/walking-skeleton plan`, `/walking-skeleton spec`, `/walking-skeleton build`) and stop.
- Workflow order: **plan → spec → build**. Later commands gate on earlier artifacts; redirect the user when prerequisites are missing.

## Route

Read **only** the command file for the invoked command (paths relative to this skill directory):

| Command | File |
|---------|------|
| `plan` | [commands/plan.md](commands/plan.md) |
| `spec` | [commands/spec.md](commands/spec.md) |
| `build` | [commands/build.md](commands/build.md) |

Follow that file completely. Do not load other command files unless the user switches commands.
