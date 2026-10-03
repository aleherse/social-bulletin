# ADR-0015: Adopt `make ci` as the merge gate

- Status: Accepted
- Date: 2026-06-12

## Context

The project needs one merge gate that gives the same verdict on a laptop and in CI.

## Decision

`make ci` SHALL be the only merge gate,
and the same command SHALL run locally and in CI.
It SHALL run under its own Compose project built from `docker-compose.yml` alone,
so it never touches the development stack's containers, ports or database,
and two checkouts can gate at once.
It SHALL run its stages in this order, as declared in the Makefile:

- `prepare`: container images and dependencies every later stage runs on.
- `cheap`: every linter, in parallel, reporting every failure.
- `build`: unit suites, the production bundle and the database snapshot, in parallel.
- `suites`: the database-backed API and E2E suites, one after the other,
  since both restore the same snapshot into the same database.

`make ci` SHALL tear down its containers, network and database volume when it finishes,
whatever the outcome.
`make ci-stage STAGE=<stage>` MAY re-run a single stage in the gate's project
to diagnose a failure, but its result SHALL NOT be taken as the gate's verdict.

Formatters SHALL apply fixes locally and check strictly when `CI=true` is set.

A single GitHub Actions workflow, `ci.yml`, SHALL run `make ci` in one job:

```yaml
on:
  push:
    branches: [main]
  pull_request:
    types: [opened, synchronize, reopened, ready_for_review, converted_to_draft, closed]
  workflow_dispatch:
```

The gate SHALL skip draft pull requests,
and SHALL cancel any earlier run on the same branch,
so a `closed` event only cancels a closed pull request's leftover runs.

The runner SHALL be selected by the `ACTIONS_RUNNER_TARGET` repository variable,
either `github-hosted` (the default) or `self-hosted`.
The job SHALL clean its workspace before checkout,
so a persistent self-hosted runner starts from an empty tree.

Container images and installed dependencies SHALL be cached separately.
The development stack bind-mounts the repository into its containers,
so dependency trees live in the workspace rather than inside an image,
and a cached image alone would still leave the job installing them.

- Image layers SHALL be shared through the GitHub Actions cache
  by the CI-only overlay `docker-compose.ci.yml`,
  selected through `GATE_COMPOSE_FILE`
  rather than added to the development stack definition,
  so local builds keep working unchanged.
- Composer and npm dependency trees SHALL be cached with `actions/cache`,
  keyed on their lock files.

Pull request template SHALL be added at `.github/PULL_REQUEST_TEMPLATE.md`:

```markdown
<!-- Delete any heading that does not apply. -->

## What this changes

## Why

<!-- The problem, not the solution. Link the card or issue. -->

## How it was verified

<!--
Name the gate that ran and what it ran over: `make ci` over the whole
project, or the focussed checks (`make lint FILES=...`, `make <suite> PATHS=...`)
over the change. "CI is green" alone is not verification for anything
user-visible: add what a gate cannot show (a browser check, a manual flow,
a measurement).
-->

## Notes for the reviewer

## Risks and rollout notes

<!--
Include any additional information or notes that may be helpful for deployment.
-->
```

## Consequences

- Every pull request and every push to `main` runs the full gate;
  there are no optional checks to request.
- `make ci` gives the same verdict locally as in CI,
  so contributors can get it without waiting for a runner.
- The gate's isolated Compose project lets it run alongside a live development stack.
- The CI job restores cached image layers and dependencies
  rather than building them from scratch.
- A run that changes an image definition or a dependency lock file
  misses the matching cache and rebuilds that part.
- Switching to a self-hosted runner is a repository variable change, not a workflow change.
- Every pull request description states how the change was verified,
  so reviewers do not take a green CI run as proof of user-visible behaviour.
- The template copy in this ADR must change together with `.github/PULL_REQUEST_TEMPLATE.md`.
- Makefile and workflow configuration need ongoing maintenance as checks change.
