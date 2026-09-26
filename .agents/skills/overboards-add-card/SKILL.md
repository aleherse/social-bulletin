---
name: overboards-add-card
description: File work onto the project's delivery board — find the board and its token from the project's own documentation, check whether the thing is already recorded, and either add to the card that covers it or create a new one in Backlog. Use when an agent has found something worth doing and needs it recorded where people and the Overboards pipeline will see it.
license: MIT
compatibility: Requires a project whose documentation names a Boards board and a token, and a token carrying board:read, card:create, card:update and comment:create.
metadata:
  author: Aircury
  version: "1.0"
---

Put something you have found onto the board, so it survives the run
that found it.

You already know what the work is.
This file is about recording it: where the board lives, what a card has
to carry, and how not to file the same thing twice.

**This is not a pipeline stage.**
It delivers no product work and logs no time.
It may briefly claim and return an existing in-flight card under the
lifecycle rules below.
It ends with one card filed, improved, returned or represented by a
linked follow-up, and a line back to whoever called you saying which.

The board conventions this skill shares with the rest of the
Overboards family — the column model, the endpoints, the lifecycle
rules, the holding note — are embedded under **Board protocol** at the
end of this file.
Each step points at the part it uses; you do not need it to start.

**Steps**

1. **Find the board.**
   All of it comes from the project's own documentation, never from
   guesswork or from another project's settings:

   - **Target**: a project may document more than one board — a real
     queue, and one for exercising these skills against. Where it
     does, it also documents how a run chooses between them, and that
     choice is the operator's: read it from where the project says it
     lives, and never infer it from the task, the branch, or which
     board looks busier. A project documenting one board has one
     target and nothing to choose.
   - **Board**: the project's root README carries a dedicated board
     section; the first link under that heading is the board for the
     chosen target, `https://<host>/b/<slug>`. The slug is what the
     API wants.
   - **Token**: that same section documents where the target's token
     lives — usually the name of an environment variable the operator
     exports. Read it from there; never from a file you found by
     searching, and never echo it.
   - **API base**: documented alongside; failing that, the board origin
     with `api.` prefixed to the host.

   Missing documentation, a missing token, or a token the API rejects
   → stop and say exactly what is missing.
   Do not file the work somewhere else instead:
   a card in the wrong place is worse than a clear report that it could
   not be filed, because the second one still has your evidence in it.

2. **Say what you are filing, before you write it anywhere.**
   The next reader of this card is the specification stage, which takes
   the title and description as the whole feature description and
   escalates a card it cannot build a defensible specification from.
   So the card carries:

   - **The problem**, stated as what is wrong or missing rather than as
     what should be built.
   - **The evidence** — file and line, the command and its output, the
     behaviour you saw. A claim a reader cannot check is a claim they
     have to take on trust from a run that has since ended.
   - **Why it matters**: the concrete cost of leaving it, not an
     abstraction.
   - **Provenance**: what you were doing when you found this. A card
     whose reader knows it fell out of a code review of something else
     reads it differently from one somebody sat down to write.

   For a deprecated compatibility path — code kept only because
   existing callers, data, configuration or deployments may still
   depend on it, filed so its eventual removal stays visible — the
   description also carries the durable removal record:

   - **Status:** deprecated compatibility path kept for now.
   - **Kept for:** the backwards-compatibility reason.
   - **Replacement:** the current path callers should use instead.
   - **Introduced or observed:** the date or card that recorded the debt.
   - **Earliest removal:** the condition, version, date or confirming evidence
     after which removal can be considered.
   - **Owner or area:** the component responsible for deciding removal.
   - **Tracking:** the current card key or other source that found it.
   - **Removal checklist:** migrate remaining users or data, warn where
     appropriate, update docs and examples, remove the compatibility code, and
     remove or rewrite tests that exist only for the compatibility path.
   - **Evidence:** exact file paths, lines or commands proving the path exists.

   **Do not design the solution.** A remedy you are confident of goes
   in as a suggestion, marked as one and kept short. The pipeline
   specifies, plans and reviews on purpose, and a card that arrives
   with the answer already in it biases every stage that follows.

3. **Check whether it is already recorded.**
   Read every board column, not only Backlog,
   except `Merged to staging` and `Merged to main`.
   Include operator-owned `Deep backlog` only as read-only
   duplicate-prevention evidence:
   coverage can sit in any active pipeline column,
   and a decision not to do the thing sits in Won't do.

   Read it by its **content** where the board offers that.
   the board's card digest returns every card's
   description along with its key, title, column and labels, one request
   per page (see Board protocol).
   Matching cards in `Deep backlog` are read-only duplicate evidence.
   Do not comment on, move, assign, relabel, or otherwise mutate
   those cards.
   Descriptions are where the evidence lives — the shared `file:line`,
   the same seam, the stated relationship to another card — and they are
   what tells two cards describing one problem apart from two cards
   describing two.
   Where the board advertises no digest, or the digest is advertised
   but the read cannot be completed, it is not available to you:
   fall back to the titles the board the entry point links
   already returns, and open the few cards whose titles leave the
   question genuinely open.
   Say in your report which of the two you used, and why.
   Card search orders equally matching cards by most recently worked
   and uses the same ordering to settle its shortlist;
   do not compensate with board order.
   Matching `Deferred` cards in `Deep backlog` are not board drift;
   a covering `Deep backlog` card prevents a duplicate active Backlog card,
   but it remains outside this family's writes.

   Classify every covering card by the
   **Filing against a card's lifecycle** rules under Board protocol
   before writing.
   Where a non-frozen follow-up already covers the finding, use it
   instead of widening its frozen or actively owned predecessor.

   - **A card in Deep backlog covers it** →
     do not create a duplicate active Backlog card.
     Do not comment on, move, assign, relabel, edit, link, or otherwise
     mutate the Deep backlog card.
     Then stop, and report that the finding is already represented by
     that Deep backlog card.
   - **A card in Backlog, Ready to play or Other covers it** →
     do not create a second one.
     Add what you found: a comment carrying your evidence and
     provenance, and — where the card's description is plainly thinner
     than what you now know — an addition to it.
     Editing a description means re-reading it immediately beforehand
     and appending beneath what is there, never rewriting somebody
     else's words.
     Then stop, and report which card you added to.
   - **A card in Discovery covers it** →
     do not create a second one.
     Add your evidence as a comment only,
     leave the card in Discovery,
     and report which card you added to.
     The discovery stage will answer it and move it to Backlog.
   - **A card in Won't do covers it** → somebody has already decided
     this should not happen. Do not file it again.
     Comment your evidence there if it is genuinely new — that is how a
     decision gets revisited — and report that the work was previously
     declined, with the reason. Reopening it is a person's call.
   - **An unblocked in-flight card covers it** → return the card so the
     evidence becomes work rather than an unread late comment.
     Choose the receiving column by the lifecycle routing under Board
     protocol.
     Do not edit the description or any attachment.
     Re-read the card detail immediately before writing and confirm its
     column and unblocked state still match the decision.
     Claim it with the family's conflict-safe holding note
     (see Board protocol), using `expectedBlockReason: null`.
     Then comment:
     `Returned for new evidence to <column>: <problem, evidence and
     provenance>`.
     Move it to the bottom of the receiving column when that differs
     from its current column, then unblock it using the exact holding
     reason.
     A card already in the correct receiver is commented and unblocked
     without being reordered.
     Report the card and where it was returned.
   - **A blocked in-flight card covers it, or the claim loses a race** →
     leave that card wholly untouched.
     Create or enrich a linked Backlog follow-up per step 4.
   - **A card in Retrospective captured, Merged to staging or Merged to
     main covers it** → its delivery scope is frozen.
     Do not comment on, edit, block or move it.
     Create or enrich a linked Backlog follow-up per step 4.
   - **Nothing covers it** → create it, per the next step.

   Judge by what the work *is*, not by wording. Two cards can describe
   one problem in different words, and one title can cover two
   different problems.

   **The board is not the only place a decision lives.**
   Where the finding proposes work the project has already ruled out in
   its own documentation — a spec that fixes a version family, a
   decision record, an `engines` or platform declaration, an explicit
   project instruction — that settles it as much as a `Won't do` card
   does.
   Do not file it.
   Report what you found and the document that settles it, naming the
   condition that would reopen it where the document states one, so the
   reader learns the answer instead of meeting it again as an
   escalation two stages later.
   A document that simply does not mention the work decides nothing:
   this is about what the project has written down, never about what it
   has left unsaid.

4. **Create the card in Backlog.**
   the column's advertised `card.create` takes a
   **title and a position and nothing else**;
   the description arrives as a `PATCH` on the card it returns.

   - **Backlog, at the bottom.** New work joins the end of the queue;
     where it belongs is decided at later whole-Backlog grooming,
     by a person or `overboards-reprioritise-backlog`
     (see Board protocol: Order is the instruction).
   - **The title states the problem in a few words**, with no prefix
     announcing that an agent filed it — the board already records
     which credential created the card, and a prefix would only shrink
     the space the title has to say something useful.
   - **Then `PATCH` the description**, holding what step 2 gathered.
   - **For a lifecycle follow-up**, include the matching card's public
     key and URL and state whether it was frozen or actively owned.
     When card links are available,
     record that same URL as a card link on the Backlog follow-up,
     with description `Overboards follow-up card: <key>`.
     Use the board origin plus canonical `/c/<public key>` path,
     without a title slug or query string.
     First re-read the follow-up card detail and create the link only
     when that exact URL and description pair is absent.
     If the board does not expose card links,
     keep the URL in the description and report that the older path was
     used.
     This relation belongs only on the new card:
     do not add a reciprocal comment to the card that could not safely
     absorb the work.

5. **Label what you are sure of.**
   Attach the board's existing labels for the **area** the work sits in
   and the **severity** you would defend — you are the card's author,
   and that is the author's call to make.
   The family's rule that no pipeline stage revises those still holds,
   and this is its filing exception: filing a card is not revising one.
   Once the card exists, what you put on it is a proposal a person may
   change at grooming.
   No delivery stage overwrites that decision;
   a later whole-Backlog reprioritisation may reassess it explicitly.
   Supplying an absent classification is a stage's;
   revising a present one is not — so a label you attach here is left
   exactly as you attached it, and a gap you leave is filled by the
   specification stage rather than carried into planning.

   - **Read the board's label catalogue** from the payload you already
     have, and match by meaning. Use what the board defines, whatever
     it happens to call it.
   - **Deprecated compatibility paths carry `Deprecation`.**
     Treat `Deprecation` as a known work-classification label for cards
     filed as compatibility debt under step 2, and attach it
     in addition to any area or severity you can defend.
     If it is unexpectedly absent, say in the description that the card
     should carry `Deprecation` and report the missing label to the
     caller; do not create the label.
   - **A card reporting a defect carries `Bug`.**
     Treat `Bug` the same way: a known work-classification label,
     attached in addition to any area or severity, for a card whose
     subject is something behaving wrongly rather than something
     missing.
     It is what tells the pipeline this card owes an explanation of
     what happened once somebody works it out, so a defect filed
     without it gets fixed without ever being explained.
     Absent from the catalogue, say so in the description and report it
     to the caller; do not create the label.
   - **Never create a label**; that is an administrator's to do.
     Where the board has no label for the area or the severity, say so
     in the description in words and leave it unlabelled. Do not reach
     for the `Missing label` marker — that machinery exists for a stage
     recording a card's fate, not for a guess at its area.
   - **Sure of one and not the other?** Attach the one you are sure of.
     A wrong severity costs a groomer more than a missing one.
   - Attaching takes the card's advertised `card.attach-label`
     with `{"labelId": "<id>"}`, one label per request, leaving the
     card's other labels alone.

6. **Record a named hard prerequisite as a blocking relationship.**
   A card link is a readable reference; a blocking relationship is the
   durable dependency selection reads before any work starts.
   A follow-up filed without the edge gets picked up, specified,
   planned and built ahead of the work it depends on.
   You are the only party who knows, without archaeology, whether the
   new work waits on the old — so record it here, while it is cheap.

   - **Act only on a card something already named.**
     A hard prerequisite is one the caller or the artefact in hand
     names as a card that has to land first.
     A card merely mentioned in the evidence is not one, and there is
     no board-wide sweep: **nothing named means no relations read and
     no write at all**, which is what keeps ordinary filing at the
     request count it already has.
   - **Whether it is a hard prerequisite is not decided here.**
     The test is the one in `reference/card-lifecycle.md`
     § Waiting on another card, and it is cited rather than restated,
     so filing and selection cannot come to different conclusions about
     the same pair of cards.
     Read each of its conditions against the card that holds the role
     that condition is written about; a candidate failing any of them
     gets no edge.
   - **Record it in whichever direction the naming gives.**
     Where the filed card waits, the edge is `blockedBy` on the filed
     card. Where the filed card is the prerequisite and an existing
     card waits on it, the edge is `blockedBy` on that existing card,
     so the card that waits is the one that carries it.
   - **Three named cards are refused before any request is sent**, and
     the refusal costs no write: an archived card, a card on another
     board, and a prerequisite already in a column satisfying the
     dependent card's delivery target.
   - **Walk for a ring before recording and again immediately after**,
     under the visited set and the twenty-card cap that same reference
     section defines. The walk is only as fresh as the moment it ran,
     so the second walk is not optional.
   - **Recording leaves both cards' held state untouched.**
     Take no hold on a card you do not already hold, and change no
     card's `blocked` flag or block reason.

7. **A ring is reported, never acted on.**
   When the walk after recording shows this run closed a ring, withdraw
   only the edge you created — by the identifier the record returned,
   and no other edge — keep the filed card exactly where it is,
   and report the ring key by key to whoever called you.
   Change no card's column and no card's hold.
   This skill **escalates nothing**: it is not a pipeline stage, so the
   only card that could be escalated belongs to your caller, and its
   fate is the caller's to decide.

8. **Report back.**
   Tell whoever called you the card's public key and URL, and whether
   you created it, added to it, returned it, or created it as a linked
   follow-up because another card could not safely absorb the finding.
   That line is what lets the run you interrupted carry on and still
   leave a trail.

   Say which of these applied to the dependency, in the same line:

   - an edge was recorded, naming both cards and the direction;
   - the edge was already present, so nothing was written;
   - nothing named a prerequisite, so nothing was read or written;
   - an edge was not attempted because it could not exist, naming
     which of the three refusals above applied;
   - an edge could not be recorded, naming the outcome the board gave;
     or
   - the board offers no relationship write, which is said
     **once per run rather than once per card**.

   A failed, refused or conflicting record still leaves the card filed:
   the card is the product of this skill and the edge is not.
   Say that the dependency was not recorded rather than presenting the
   card as carrying one it does not carry.

**Filing discipline**

A board everybody writes to is a board nobody reads.

- **File what you would defend to a person.** If the honest summary is
  "something here could probably be better", it is not a card.
- **One card per problem.** Three symptoms of one cause are one card;
  one card listing six unrelated problems is six cards nobody can
  schedule, and the pipeline will specify it into a mess.
- **Process pain is not a card.** Flaky tooling, unclear guidance and
  awkward artefacts are frictions for the pipeline's retrospective
  stage to consolidate. Cards are for work on the product.
- **An API gap belongs to the API's project.**
  A limitation exposed through process pain is product work only when
  the current checkout contains the route or capability implementation
  that would deliver it.
  Merely consuming the API does not make its improvement suitable for
  this project's Backlog.
  Report work owned elsewhere to the caller instead, with the full
  evidence, so it can be filed in the owning project's queue.
- **Do not file the thing you were asked to do.** Work inside your
  current task belongs in that task, not on a board where it will look
  to everybody else like something nobody has started.

**Guardrails**

- **New cards start in Backlog only.**
  Returning an existing in-flight card to Ready to play,
  Clarifications provided or Re-implementation needed is
  the only exception to changing another column.
- A lifecycle return claims only an unblocked card, holds it only for
  the comment and move, and always releases its own exact holding note.
  It never takes over another run's or person's block.
- Retrospective captured, Merged to staging and Merged to main are
  immutable to this skill.
- No filing or return logs time.
- No checklists either. The family's checklists are work queues
  written by a stage that already knows the work, and a to-do list on
  a freshly filed card is the solution designed in tick-boxes —
  evidence and reproduction steps are prose in the description.
  On a card that already exists, a checklist somebody put there is
  theirs: comment your evidence and leave its items alone.
- The token is read, used and never written anywhere — not into a
  card, a comment, a log line, or a file.
- A run that cannot reach the board still reports what it found, in
  full, to its caller. The evidence is the valuable part; the card is
  only where it was put.

**Board protocol**

The shared conventions this skill relies on, embedded so it stands on
its own.

*The column model.*
A board run by the Overboards family carries these columns, in
pipeline order:
Discovery, Backlog, Ready to play, Specified, Clarifications needed,
Clarifications provided, Plan + Tasks + Analysed,
Implemented, Re-implementation needed, QA, QA passed, Code reviewed,
Code review findings fixed,
Retrospective captured, Merged to staging, Merged to main, Won't do,
Other.
**Deep backlog** is an operator-owned holding column outside the
pipeline.
For add-card filing only, it may be inspected as read-only duplicate
evidence before creating active Backlog work.
For every other family decision, treat it as absent — never inspect,
count, report, comment on or change cards there, and discard it from any
payload before deciding anything.

*Filing against a card's lifecycle.*
Finding a matching card does not always mean that card can absorb more
work. Its column decides what is safe:

- **Investigation intake** — Discovery. Only the discovery stage moves
  it; genuinely new evidence may be added without moving it.
- **Pre-flight or resting** — Backlog, Ready to play, Won't do and
  Other. New evidence may be added under this skill's own rules
  without moving the card.
- **In flight** — Specified through Code review findings fixed.
  New evidence must be returned to a stage that can incorporate it;
  a comment left at the card's current stage is not a work queue.
- **Frozen** — Retrospective captured, Merged to staging and Merged to
  main. The delivered scope does not change. Never comment on, edit,
  block or move the card; create or enrich a linked Backlog follow-up
  instead.
- **Read-only filing evidence** — Deep backlog. Inspect it only while
  checking whether a new finding is already represented.
  A covering match prevents a duplicate active Backlog card.
  Never inspect it for delivery stages, grooming, reports, counts or
  selection, and never comment on, move, assign, relabel or otherwise
  mutate the card.

An in-flight return uses the exact comment prefix in step 3 and is not
a structural bounce: it does not count towards the family's two-bounce
cap. Route it by the work needed:

- Specified, Clarifications needed or Clarifications provided returns
  to **Ready to play**, so specification runs again.
- From Plan + Tasks + Analysed onwards, a scope, requirement or
  artefact gap returns to **Clarifications provided**.
- From that same range, an implementation defect returns to
  **Re-implementation needed** only when the artefacts already require
  the right behaviour and the card carries a branch.
- A card already in its correct receiving column keeps its place; the
  receiving stage can act on the new comment without an artificial
  detour.

A linked follow-up names the matching card's public key and URL and
says whether that card was frozen or actively owned. When a non-frozen
follow-up already covers the finding, enrich it instead of creating
another.

*Do not block a card to claim it.*
A block on a card means a person must look at it; it is not a lock.
The card a run is working carries the `Overboards` assignee, placed by
the runner and withdrawn when the run ends, and this skill runs inside
such a run or on behalf of a person: it claims nothing.
Enriching a card is a comment or a description edit on a card nobody is
working, judged by its assignees: skip a card that carries `Overboards`
or a person, and one still blocked under a legacy `holding since` note
the board reports in force, and say why.
The API applies that precondition atomically and answers
`409 Conflict` when another writer got there first — on a conflict,
leave the card wholly untouched.
Unblocking is `blocked: false` with the exact currently held reason as
`expectedBlockReason`; the state change lands in the card's Activity
history, so never post a separate `Unblocked` comment.

*Order is the instruction.*
A card higher in a column is worked before a card lower down, and that
is the only priority the family can see — no urgency from labels, no
age from timestamps.
New and returned work therefore lands at the **bottom** of its column,
behind everything already waiting; somebody who wants it worked sooner
drags it up.
Nothing else is reordered: an agent that rearranges a queue is
overruling the person who arranged it.
Whole-Backlog grooming, by a person or
`overboards-reprioritise-backlog`, is the deliberate exception.

*Reaching the board.*

The board describes itself at runtime; there is no list of addresses to
memorise, and composing one is how a request ends up refused.

1. `GET {apiBase}/api` with `Authorization: Bearer <token>` and
   `Accept: application/ld+json`. A board-scoped token's entry point links
   its one `board`, and the `cardById` and `cardByToken` lookup templates.
2. Follow a link to reach a resource. The board links its `columns`,
   `labels`, `cardSearch` and `columnCardCounts`; a card links its
   `relations`, `timeEntries` and its attachments' delivery.
3. Write through an operation the resource advertises. Each entry in a
   representation's `hydra:operation` carries the action it performs
   (`@id` ending `#card.update`, say), its method, and its target. Take the
   target the board gave; do not build one.
4. An action the board does not advertise on the resource in hand is one this
   credential may not take there. Fall back as this skill documents; never
   send the request anyway to see what happens.
5. Every error below `/api` is a problem document: `type`, `title`, `status`,
   `detail`, and `code` on a business refusal. A `404` is a resource the
   credential may not see or that does not exist — never a board that lacks
   a feature.

*Operations.*

| Purpose                          | Operation                                               | Capability          |
| -------------------------------- | ------------------------------------------------------- | ------------------- |
| Resolve board and columns        | the board the entry point links                        | `board:read`        |
| Read every card's content on a board | the board's card digest            | `card:read`         |
| Read one card in full            | the card lookup the entry point advertises                         | `card:read`         |
| Create a card                    | the column's advertised `card.create`   | `card:create`       |
| Edit description, block, unblock | the card's advertised `card.update`            | `card:update`       |
| Move a card                      | the card's advertised `card.move`        | `card:update`       |
| Attach one label                 | the card's advertised `card.attach-label`      | `card:update`       |
| Comment                          | the card's advertised `comment.create`    | `comment:create`    |
| Record a card link               | the card's advertised `card.add-link`       | `card:update`       |
| Record a blocking relationship   | the card's advertised `card.add-blocking-relationship` | `card:update`       |
| Withdraw a blocking relationship | the relations entry's own advertised removal | `card:update`       |

Facts that save a hunt:

- **A blocking relationship is recorded by direction and the other
  card's internal id**, not by public key: `blockedBy` on the card that
  waits, `blocks` on the card it waits for. The record comes back
  carrying the relationship's own identifier, which is what a
  withdrawal names — not either card's id. Read the card's relations
  first and record only what is missing; an edge already present
  between the same pair in the same direction is the same edge.
- **Recording or withdrawing an edge leaves both cards' held state
  untouched.** The edge lives on the card's blocking-relationships
  collection while a hold is an update of the card itself, so no
  `blocked` flag and no block reason moves — which is what lets an edge
  be recorded on a card this run does not hold.
- **A board advertising no relationship write does not offer it.**
  Behave exactly as filing did before the feature, keep the card link
  and the description text, and say so
  **once per run rather than once per card**.

- The board payload carries every column with its cards in ranked
  order (integer `position`; topmost card first), and each card's
  internal `id`, public key, `blocked` boolean and optional parent
  summary.
  Archived cards do not appear in the active board payload at all.
- Cards have two identifiers: the internal `id` (used by write routes)
  and the **public key** shown in the card URL `/c/<key>`,
  for example `ABC-12`.
- **The digest is how a whole board's card content is read.** The
  board payload carries every title and no descriptions, so judging
  what each card *is* from per-card reads costs one request per card.
  The digest answers that in one request per page: each record carries
  the card's key, title, column (and whether that column is in the
  backlog area), labels, the description as authored, its comment
  count, and whether it is archived. It is paged by `limit` (fifty by
  default, clamped to `[1, 100]`) and an opaque `cursor` the previous
  page returned, ordered by an identity a move or a rename does not
  disturb, so a card edited between two page reads is neither served
  twice nor skipped. Archived cards need `includeArchived=true`.
- **A board that advertises no digest does not offer it.** The read
  is a deployment away on some boards and these skills are shared by
  projects whose boards run older ones, so treat its absence as
  ordinary: fall back to the titles in the board payload plus a
  per-card read of whichever cards look worth opening. Nothing here
  requires the digest.
- **An advertised digest can still be unreadable, and the failure is
  in the reading, not in the board.** A helper that pages the digest
  by passing the accumulated JSON to another program as a
  command-line argument hits the operating system's limit on the
  size of one argument — 128 KiB on Linux, which is separate from,
  and far below, the total argument-list limit — and dies with
  `Argument list too long`. At the default page size of fifty cards
  that is reached by any board whose descriptions average a couple
  of kilobytes, which is an ordinary board rather than a large one,
  and it fails on the first page, so there is no partial result and
  paging further does not help. Do not read this as the board
  lacking the digest, and do not abandon the content check: request
  the advertised `cardDigest` link yourself, following `nextCursor`
  and writing each page to a file rather than into an argument, or
  take the title fallback above. Report the helper's failure as a
  defect of the helper, on whichever board owns it.
- **Comments and checklists are read only from the card detail
  payload.** There is no list endpoint; do not hunt for one.
- **Board-facing text is composed as data, never as shell input.**
  This governs any text a stage sends to the board, and this skill
  writes under the same rule: card titles, card descriptions, comment
  bodies, block reasons, checklist item titles, time-entry descriptions
  and card-link descriptions are examples of it, not the whole of it.
  The conventions here require that text to carry fenced blocks,
  inline code spans and backticked identifiers, so a body that reaches
  a shell as source rather than as content is executed before the board
  ever sees it — the card records something mangled, and the machine
  runs whatever the text happened to contain.
  So: never an unquoted heredoc delimiter, and never the body
  interpolated into a command line.
  One technique covers every board-facing write: hand the text over as
  data and let the route decide the shape it takes.
  Where the client in use has an operation for the write, hand it the
  value and let it build the request: a multi-line body through
  `--body-file`, and a single-line value through that operation's own
  argument, which becomes part of the request payload rather than part
  of a command.
  Where it has no such operation, compose the body into a file and build
  the request body from it with `jq -n --rawfile`.
  Where no safe route is open for a body, it is not sent through a path
  that interprets it: omit the body, log a friction and report it.
- **Card links are read only from the card detail payload.**
  The `card.links` array carries each link's `id`, `url`,
  nullable `description`, display label, host, creation time and creator.
  Recording a link takes an absolute HTTP(S) URL and optional description.
  Card targets use the board origin plus canonical `/c/<public key>` path,
  without a title slug or query string.
  This skill writes non-blank Overboards descriptions and treats an existing
  identical URL plus description as already recorded.
  If a touched legacy card has the exact needed URL only in old prose,
  preserve the prose and add the matching managed link.
  A card detail without `links`, or a card advertising no link write,
  means the board predates first-class card links;
  keep the older prose URL and report the compatibility path.
- Labels are read, never listed. The board payload and every card
  detail payload carry the board's whole label catalogue — each
  label's `id`, `name` and `colourPreset` — so there is no lookup
  route to hunt for. Match by name, case-insensitively.
- **Label routes carry one label id and touch nothing else.**
  Attaching takes `{"labelId": "<id>"}` and is idempotent: attaching a
  label the card already carries succeeds and changes nothing.
  The card `PATCH` also accepts a `labelIds` array, but that one
  *replaces* the whole set — never reach for it to change one label.
  Creating a label is `label:write`, an administrator's capability
  this skill is not issued.
- Moving takes a target column id and a target index. The index is
  not a detail to pick freely: see Order is the instruction.
- Creating a card takes a title and a position and nothing else;
  its description arrives as a second `PATCH` on the new card.
- Capability names are the slugs of the Boards token capability
  register. The Boards product enforces them on every
  token-authenticated request.
