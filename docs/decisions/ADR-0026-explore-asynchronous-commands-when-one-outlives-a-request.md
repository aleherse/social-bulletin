# ADR-0026: Explore Asynchronous Commands When One Outlives A Request

- Status: Proposed
- Date: 2026-11-09

## Context

Commands run synchronously and answer in the HTTP response (ADR-0021),
and clients learn about other people's changes through realtime notifications (ADR-0025).
That fits commands that finish in milliseconds.

A command that is slow, depends on a slow external system, or can fail long after it was accepted
cannot answer within one request.
It needs the caller to be told later what happened,
and to be able to tell which command a result belongs to.
No such command exists yet,
and the shape of the first one decides which parts of the design are needed,
so building the machinery ahead of it would be guesswork.

## Decision

- Commands SHALL stay synchronous, and no asynchronous transport, outbox or result notification SHALL be built,
  until a command is implemented that cannot answer within a request.
- That command's change SHALL start by accepting this ADR or replacing it with one that records what the command needs.
- The design to evaluate first is acceptance followed by a result:
  - the endpoint answers `202 Accepted` with a command id, supplied by the client so that a retry is idempotent;
  - the command runs on an asynchronous transport, still through the command bus;
  - its outcome is stored with its effects and published as a notification carrying the command id,
    on a channel scoped to the user;
  - a status resource answers for a command whose notification was missed, and clients SHALL read it after a timeout or a reconnect.
- Request/response over the websocket, client publishing and a different realtime service
  SHALL only be considered if that design cannot meet the command's needs.

## Consequences

- Nothing changes today: commands, queries and the realtime contract stay as they are.
- A notification is best effort (ADR-0025),
  so an outcome that only exists as a notification can be lost.
  The first asynchronous command therefore requires a transactional outbox and a status resource,
  and ADR-0021's synchronous buses and ADR-0025's no-outbox decision are revisited by it.
- The command id, idempotency, timeouts and the failure model for work that fails late are decided with a real command,
  not guessed now.
- The OpenAPI and AsyncAPI documents (ADR-0023, ADR-0025) gain the `202` response, the status resource and the result notification.
