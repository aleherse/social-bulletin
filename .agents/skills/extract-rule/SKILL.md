---
name: extract-rule
description: Distill reusable engineering rules from code changes and record them in docs/rules/. Use whenever the user wants to extract rules, conventions, or lessons from a diff, a commit, or the current uncommitted changes — e.g. "extract the rule from these changes", "what convention does this commit establish", "record this as a rule", or after a correction during review that should become a durable guideline for future work.
license: MIT
metadata:
  author: Aircury
  version: "1.0"
---

Code changes often encode a decision that was made once but should guide every future change:
where a file belongs, how a test is written, what a layer may depend on.
This skill turns those implicit decisions into explicit rules under `docs/rules/`,
so agents and humans can follow them without rediscovering the reasoning.

The flow is interactive by design: the user confirms the distilled rules and their wording
before anything is written. Do not skip the confirmation points.

## Step 1: Establish the scope

Run `git status --porcelain`.

- If there are uncommitted changes (staged, unstaged, or untracked), those files are the scope.
- Otherwise, ask the user for a specific commit or a number of commits behind the current branch.
  Propose the latest commit (`HEAD`) as the default.

Set aside files that carry no engineering decision — agent/tooling artefacts
(e.g. `.agents/`, `skills-lock.json`), lockfiles, generated files — and say which ones you excluded.
State the chosen scope back to the user in one line before continuing.

## Step 2: Read the changes

Read the diffs for the scope (`git diff`, `git diff --cached`, or `git show`/`git diff HEAD~N` as appropriate),
plus enough of the surrounding files to understand the change in context.
The goal is to understand *why* the change was made, not just what moved —
commit messages, deleted files, renames, and related docs are all evidence.

## Step 3: Distill the rule

Extract the rule or rules that explain the changes.
A rule is the general principle you would tell an agent so it makes the same choice next time,
not a description of this particular diff.

Each rule must belong to one subcategory from this tree:

- Infrastructure
  - Deployment
  - CI
- Scaffolding
  - Docker
  - Folders
- Application
  - Framework
  - Security
- Domain
  - Common
- Test
  - E2E
  - Unit
  - Performance
  - Integration
- Database
  - Persistence
- Documentation
  - Markdown

The categories are fixed — never invent a new top-level category.
Subcategories are open: add a new one when the rule clearly deserves it
(e.g. `Test / Contract`, `Application / Validation`), and say so explicitly when you do.

If a rule could plausibly belong to more than one subcategory,
summarise the rule and ask the user which subcategory it belongs to.

When the changes yield rules across several subcategories, work one subcategory at a time:
pick one, set the other rules aside (keep an explicit list of them),
and after finishing Step 6 for the current subcategory, restart from Step 3 with the next set-aside rule.

For each rule in the current subcategory, expose the reasoning:
what in the diff suggests the rule, and why it matters beyond this change.
Then wait for the user to confirm the rule (or correct it) before moving on.

## Step 4: Contrast with existing rules

Rules live in `docs/rules/<category>/<subcategory>.md`
(lowercase kebab-case, e.g. `docs/rules/test/e2e.md`, `docs/rules/scaffolding/folders.md`).

Read the target file if it exists, then classify each distilled rule:

- **Add** — nothing similar exists; it will be appended.
- **Update** — an existing rule covers the same ground but is incomplete or outdated; it will be amended.
- **Conflict** — an existing rule says the opposite. Present both versions to the user and
  let them decide which wins; never silently overwrite an existing rule.

## Step 5: Formalise the rule

Write each rule using this exact template:

```markdown
**WHEN** <condition that triggers the rule>
**THEN** <rule that should guide the agent>

*Example:*
    <specific rule application examples>
```

Keep the WHEN concrete enough that an agent can tell whether it applies,
and the THEN actionable enough that two agents following it would make the same choice.
Ground the example in this repository (real paths, real names) — that is what makes the rule checkable.

Prefer structure over prose in the example: a short list, a before/after table, or a code/path
snippet reads faster than a sentence and is easier to scan when several rules pile up in one file.
For instance:

```markdown
**WHEN** adding domain logic to `packages/core`
**THEN** group it under `src/<Aggregate>/`, one folder per aggregate — never a flat top-level file

*Example:*
    | Wrong                                        | Right                                              |
    |-----------------------------------------------|-----------------------------------------------------|
    | `packages/core/src/MovementService.php`        | `packages/core/src/Movement/MovementService.php`   |
    | `packages/core/src/UserRepository.php`          | `packages/core/src/User/UserRepository.php`         |
```

Show the formalised rule(s) to the user and wait for feedback and confirmation.

## Step 6: Write the rule

Write the confirmed rule(s) to the target file, creating directories and files as needed.
When creating a new file, start it with a `# <Category> / <Subcategory>` heading.

If rules were set aside in Step 3, restart from Step 3 with them.
When no set-aside rules remain, summarise which files were written and you are done.
