---
name: create-pr
description: Create a GitHub PR. Checks branch state, handles uncommitted changes, follows project conventions for branch names, commit messages, and PR titles, and fills the repo PR template.
---

# Create PR

## Conventions

| Artifact | Format |
|---|---|
| Branch name | `<trello-id>-brief-description-of-changes` |
| Commit message | `<trello-id> \| <type>(<scope>): <message>` |
| PR title | `<trello-id> \| <brief description of changes>` |

**Commit types:** `build`, `chore`, `ci`, `docs`, `feat`, `fix`, `perf`, `refactor`, `style`, `test`
**Scopes:** use the app/package name, e.g. `api`, `api-teaching`, `core`, `student`, `teaching`, `marketing`, `cms`, `learning`

**.idea files must never be staged or committed.** Always exclude them regardless of git status.

---

## Step 1 — Assess branch state

Run these in parallel:

```bash
git branch --show-current
git status --short
git log origin/$(git branch --show-current)..HEAD --oneline 2>/dev/null   # unpushed commits
git log HEAD..origin/$(git branch --show-current) --oneline 2>/dev/null   # commits on origin not local
```

Classify the state:

| Condition | State |
|---|---|
| No uncommitted changes (excluding .idea), branch exists on origin, no unpushed commits | **CLEAN** |
| Uncommitted changes exist (excluding .idea) | **DIRTY** |
| Has unpushed commits but no uncommitted changes | **UNPUSHED** |
| Branch does not exist on origin at all | **NOT PUSHED** |

---

## Step 2 — Handle state

### CLEAN
Proceed directly to **Step 4 — Create the PR**.

### DIRTY
Ask the user (use `AskUserQuestion` tool):

> "There are uncommitted changes. How would you like to proceed?"

Options:
1. **Commit to current branch** — stage, commit, and push the changes on the current branch
2. **Create a new branch** — move the changes to a new branch, commit, and push there

If they choose **Create a new branch**, ask for:
- Trello ID (e.g. `uTez7p4H`)
- Brief description for the branch name (e.g. `fix-learnworlds-tag-sync`)

Then:
```bash
git checkout -b <trello-id>-<description>
```

### UNPUSHED
Inform the user there are local commits not yet on origin, then ask:

> "The branch has unpushed commits. Should I push now and create the PR?"

If yes, push and proceed to **Step 4**. If no, stop.

### NOT PUSHED
Inform the user the branch has never been pushed to origin, then ask:

> "The branch hasn't been pushed yet. Should I push it now and create the PR?"

If yes, push and proceed to **Step 4**. If no, stop.

---

## Step 3 — Stage, commit, and push (only if changes need committing)

1. Identify staged/unstaged changes — **skip all `.idea/` paths**:
   ```bash
   git status --short | grep -v '\.idea'
   ```

2. Ask the user for the Trello ID and a brief commit description if you don't already have them (e.g. from existing commits on the branch or from the user's request).

3. Infer the best `type` and `scope` from the changed files. Ask the user only if genuinely ambiguous.

4. Stage relevant files (never `.idea`):
   ```bash
   git add -- <specific files>   # never: git add -A or git add .
   ```

5. Commit — **do not add Co-Authored-By or any trailer lines**:
   ```bash
   git commit -m "<trello-id> | <type>(<scope>): <message>"
   ```

6. Push:
   ```bash
   git push -u origin <branch>
   ```

---

## Step 4 — Create the PR

### 4a — Gather context

The **default base branch is `preview`**. If the user specifies a different target, use that instead.

Run in parallel:
```bash
git log preview..HEAD --oneline          # commits in this branch vs base
git diff preview...HEAD --stat           # files changed
cat .github/pull_request_template.md       # repo PR template
```

### 4b — Determine Trello ID and title

- Look at commit messages on the branch for the Trello ID pattern (e.g. `uTez7p4H`).
- If none found, ask the user for the Trello ID.
- Draft a concise PR title: `<trello-id> | <brief description of changes>`

### 4c — Fill the PR template

Read `.github/pull_request_template.md` and fill every section:

**Description** — Summarise what the PR does and why. One short paragraph.

**Links** — Leave the Trello card URL as a placeholder `[Add Trello link]` unless the user provided it. If you have the trello id you can use `https://trello.com/c/<trello-id>` to link to the card.

**Deployment Instructions** — Describe any steps needed after deploy (migrations, one-off commands, feature flags). If none, write "No special steps required."

**Scope checkboxes** — Check every box that corresponds to a directory touched by the diff. Use `git diff preview...HEAD --name-only` to determine which apps/packages changed.

**Type of Change** — Check the most accurate option based on the commits.

**Checklist** — Pre-check only items that are demonstrably true:
- "My changes generate no new warnings or console errors" — check if there are no obvious warnings
- Leave testing checkboxes unchecked for the user to fill after running tests
- Leave code review checkboxes unchecked

### 4d — Create the PR

```bash
gh pr create \
  --base preview \
  --title "<trello-id> | <brief description>" \
  --body "$(cat <<'EOF'
<filled template>
EOF
)"
```

Return the PR URL to the user.

---

## Error handling

- If `gh` is not authenticated, tell the user to run `gh auth login`.
- If the branch is behind origin (diverged), do not force-push. Tell the user and stop.
- If the PR already exists for this branch, show the existing PR URL instead of creating a duplicate.
