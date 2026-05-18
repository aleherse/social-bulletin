# Skeleton Plan

Interactive workflow for choosing bundled ADRs, validating the selection, writing ordered decisions to `specs/decisions/`, and producing `ADR-0000` with project-specific constant values.

Bundled resources live under this skill directory:

- `references/adr-catalog.md` — category index, summaries, maintainer ownership, implicit dependencies
- `references/adrs/` — full ADR source files (same layout as the original `ADRs/` tree)
- `references/constants-template.md` — template for `ADR-0000`

Read `references/adr-catalog.md` at the start of every invocation.

## Principles

- **Guide, don't implement.** This command plans and writes decision documents. It does not scaffold code, run `composer create-project`, or replace constants inside copied ADRs.
- **One step at a time.** Complete each step and get explicit user confirmation before advancing.
- **Preserve ADR text.** Copy ADR decision content into `specs/decisions/` with ID renumbering only: filename, title line, and in-body references to other selected ADRs (source ID → output ID). Constants stay as placeholders (e.g. `` `DEV_API_URL` ``) in those files.
- **Folder order is dependency order.** Category prefixes (`01-Scaffolding` … `09-Testing`) define sequencing. Within a category, sort by ADR numeric ID.

## Step 1 — Select ADRs

Present the catalog from `references/adr-catalog.md` grouped by category. For each ADR show ID, title, and a one-line summary.

Help the user build their selection:

- They may pick by ID (`ADR-0101`, `0101`), category (`all scaffolding`), or criteria (`I need auth and testing`).
- Suggest sensible defaults for a full-stack project (typically scaffolding + backend + frontend + database at minimum).
- When they ask for recommendations, explain trade-offs briefly — e.g. skipping ADR-0801 for a non-AWS deploy, or ADR-0601 if auth is deferred.

Loop until the user confirms the selection list. Track selected IDs in a running list you can refer to in later steps.

## Step 2 — Validate references, dependencies, and conflicts

### Missing implicit dependencies

Cross-check the selected set against the **Known implicit dependencies** table in `references/adr-catalog.md`. For each selected ADR whose assumed companions are not selected, flag it and ask whether to:

- **Add** the missing ADR(s)
- **Keep anyway** (user accepts the gap)
- **Remove** the dependent ADR

Read the full text of flagged ADRs when explaining why a companion matters.

### Redundancy and conflicts

Scan selected ADRs for:

- **Overlap** — two ADRs governing the same concern (uncommon in this catalog, but flag if found)
- **Contradiction** — incompatible decisions (e.g. host-native tooling vs Docker-only development)
- **Ordering tension** — a higher-category ADR selected without its scaffolding prerequisites

Propose concrete resolutions. Do not proceed until every flagged issue is resolved or explicitly accepted by the user.

If nothing is flagged, say so briefly and ask to continue.

## Step 3 — Order and write to `specs/decisions/`

### Determine order

1. Sort selected ADRs by category folder prefix (ascending): `01` before `02` before … `09`.
2. Within the same category, sort by ADR numeric ID (ascending).
3. **`ADR-0000` is not copied in this step** — it is created in Step 4 and must appear first in the final ordered list.
4. **Assign sequential document numbers without gaps.** After sorting, renumber the selected ADRs starting at `ADR-0001` in order: first selected ADR becomes `ADR-0001`, second becomes `ADR-0002`, and so on. Do not preserve the bundled catalog IDs (`ADR-0101`, `ADR-0201`, …) in the output — a subset must not leave holes in the sequence. The final set is always `ADR-0000` (constants, Step 4) followed by `ADR-0001` … `ADR-NNNN` with no missing numbers in between.

Present the ordered list as a numbered preview showing both the new sequential ID and the source ADR:

```
1. ADR-0001 — Adopt Monorepo Structure          (from ADR-0101)
2. ADR-0002 — Adopt Docker-Based Development    (from ADR-0102)
…
```

Ask the user to confirm or reorder. If they reorder, respect their choice but warn when an ADR appears before its scaffolding prerequisites, then re-run the sequential renumbering so the final IDs remain gap-free.

### Write files

Once confirmed, in the **current working directory** (the target project root):

1. Create `specs/decisions/` if it does not exist.
2. Build a **source → output ID map** from the ordered list (e.g. `ADR-0101` → `ADR-0001`).
3. Copy each selected ADR from `references/adrs/<category>/<filename>` to `specs/decisions/` using the **new sequential filename** (e.g. `ADR-0001-adopt-monorepo-structure.md`). When writing each file:
   - Update the document title line (`# ADR-NNNN: …`) to the output ID.
   - Replace every in-body reference to a **selected** source ADR ID with its mapped output ID (e.g. `ADR-0201` → `ADR-0005`). Match common forms: `ADR-0101`, `0101`, and filename-style slugs if present.
   - Leave references to **unselected** bundled ADRs unchanged, and flag them to the user if any remain.
   - Do not substitute project constants — only ADR ID references.
4. Keep the source → output map available in case the user asks.

Show the final file paths written and ask to proceed to constants.

## Step 4 — Compile constants into ADR-0000

### Collect constants

1. Read `references/constants-template.md` for the canonical constant set and meanings.
2. Scan each **selected** ADR (from `references/adrs/`) for backtick-wrapped identifiers in ALL_CAPS that represent project placeholders — at minimum: `PROJECT_NAME`, `PROJECT_SLUG`, `PROJECT_NAMESPACE`, `DATABASE_SCHEMA`, `DEV_API_URL`, `DEV_FRONT_URL`, `DEV_TLS_HOSTNAME`, `LIVE_API_URL`, `LIVE_FRONT_URL`.
3. Build a unified table. If the same constant appears in multiple ADRs, list it once with a combined "Used in" note.

### Gather values from the user

Present every constant with its meaning (from the template). Ask the user for concrete values. Offer to infer related values when helpful:

- `PROJECT_SLUG` is usually a lowercase hyphen-free form of the project name
- `PROJECT_NAMESPACE` often matches PascalCase project name
- `DEV_*` and `LIVE_*` URLs should be consistent with the project's domain pattern

Do **not** substitute values into the ADRs already written under `specs/decisions/`.

### Write ADR-0000

Using `references/constants-template.md` as the structure, write `specs/decisions/ADR-0000-project-documentation-constants.md` with:

- Status: Accepted
- Date: today's date
- Context and Decision sections from the template
- The constants table filled with the user's values
- Consequences section from the template

Present the final constants table to the user. When they confirm, suggest `/walking-skeleton spec` as the next step.

## Completion checklist

Before finishing, verify:

- [ ] `specs/decisions/` contains `ADR-0000` plus `ADR-0001` … `ADR-NNNN` with no gaps in numbering
- [ ] ADR files (except ADR-0000) still contain placeholder constants, not substituted values
- [ ] In-body references to selected ADRs use output IDs, not bundled source IDs
- [ ] ADR-0000 holds all project-specific values in one place
- [ ] User confirmed the final constants table

## Communication style

Keep prompts scannable — use tables and numbered lists when presenting choices. After each step, state clearly what happens next. If the user jumps ahead (e.g. gives constant values during selection), acknowledge it, finish the current step's confirmation, then use the information later.
