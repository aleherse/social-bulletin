# Social Bulletin

A monorepo for the Social Bulletin application:
a React frontend, a Symfony API, and a framework-free PHP core package,
developed locally through Docker Compose and Makefile entrypoints.

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) with the Docker Compose plugin
- `make`, `bash` and `git`

Nothing else is installed on the host:
PHP, Composer, Node, npm and the browsers run inside containers.

## HTTPS certificates

The nginx container generates a local certificate authority on first
start and writes the root certificate to `docker/certs/rootCA.pem`.
Trust it so your browser accepts the development host names.

**Ubuntu**

```sh
sudo cp docker/certs/rootCA.pem /usr/local/share/ca-certificates/social-bulletin-rootCA.crt
sudo update-ca-certificates
```

**Windows (WSL)**

From an elevated PowerShell or Command Prompt:

```powershell
certutil -addstore -f "ROOT" \\wsl.localhost\<distro>\path\to\social-bulletin\docker\certs\rootCA.pem
```

## Setup

Builds the images, installs the dependencies, builds the frontend,
starts the stack and prepares the database:

```sh
make init
```

`make init` is idempotent: run it again after time away to update everything.
It installs the Lefthook git hooks only when npm is available on the host,
and says so when it is not.

After setup (`make urls` reprints these):

- Frontend (compiled build via nginx): <https://dev.app.social.aleherse.com>
- Frontend (Vite dev server): <https://dev.app.social.aleherse.com:3000>
- API: <https://dev.api.social.aleherse.com>

The host ports come from `.env` (copied from `.env.dist`),
so several checkouts can run side by side.
No example dataset is needed: register from the home page.

## Daily use

| Command                | What it does                                              |
|------------------------|-----------------------------------------------------------|
| `make`                 | List every target with a short description                |
| `make up`              | Start the stack, wait until healthy, print the addresses |
| `make urls`            | Reprint the addresses                                     |
| `make down`            | Stop the stack                                            |
| `make logs service=php`| Follow one service's logs (all without `service`)         |
| `make console cmd=...` | Run a Symfony console command                             |
| `make db`              | Recreate the database and its DSLR `fixtures` snapshot    |
| `make destroy`         | Remove every container, volume and generated artefact     |

## Verifying a change

**Focussed checks** are what work in progress owes before each commit:
the linters over the changed files, and the test files covering the touched
behaviour.
They run in the same containers, with the same configuration, as the gate.

```sh
make lint FILES="apps/web/src/pages/home/ui/home-page.tsx packages/core/src/UserService.php"
make php-unit PATHS=packages/core/spec/UserServiceSpec.php
make api-tests PATHS=apps/api/features/session.feature
make web-unit PATHS=apps/web/src/pages/home
make web-e2e PATHS=apps/web/e2e/session.spec.ts
```

Each linter also runs on its own (`make php-stan FILES=...`, `make web-eslint FILES=...`).
Deptrac, `tsc -b`, knip and the infrastructure typecheck accept no file
argument and run whole.
The API and browser suites need the database snapshot: run `make db` once
after `make init` and after any migration
(`make tests` runs all four suites against the development stack).

**The full gate** is the only verdict, and it is what CI runs on every pull
request and push to `main`:

```sh
make ci
```

It needs no preparation and no running stack:
it builds and installs what it needs in its own Compose project,
publishes no host ports,
runs the cheap checks together (reporting every failure) before anything that
starts a service,
then the unit suites, frontend build and database snapshot in parallel,
then Behat and Playwright,
and removes its containers and volumes when it finishes.
Locally it applies ECS and Prettier fixes and lists the files it changed;
CI checks them strictly.
Every CI check runs locally; nothing is excluded.

`make ci-stages` lists the stages,
and `make ci-stage STAGE=<stage>` re-runs one in the gate's own project to
diagnose it — a diagnostic, never a verdict.
Judge a gate run by `make`'s own exit status, not by a piped output.

## Worktrunk

[Worktrunk](https://worktrunk.dev) manages git worktrees for parallel branch
development, giving each branch its own Compose project and ports
through `.config/wt.toml`.

```sh
curl https://sh.rustup.rs -sSf | sh
cargo install worktrunk && wt config shell install
```

## Git hooks

Lefthook (installed by `make init`) runs fast format, lint and type checks
over the staged files on commit,
validates Conventional Commit messages,
and runs unit tests plus codebase scanners on push (ADR-0013).

## AI delivery board

Work on this project is queued and delivered on one Boards board,
worked by the Overboards pipeline.
There is one target, so there is nothing to choose between.

|                    | Production                                                                                                                                                            |
|--------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Board              | `https://boards.aircury.net/b/social-bulletin`                                                                                                                        |
| API base           | `https://api.boards.aircury.net`                                                                                                                                      |
| Token              | The secret `SOCIAL_BOARDS_TOKEN` in the Overboards supervisor's configuration and secrets store, never in this repository, a shell environment or an environment file |
| Integration branch | `ready`                                                                                                                                                               |

The token is board-scoped and issued from the board's **Manage tokens**
with the **Unattended contributor** shortcut.

The **Backlog** column is the project's canonical backlog;
file new findings with the `overboards-add-card` skill,
which checks the board for a covering card first.

Completed cards land on `ready`.
A stage that needs the branch and finds it absent creates it once from `main`;
the release pull request from `ready` into `main` is where a person reviews.
`main` is never pushed to directly.
