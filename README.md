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

Builds and starts the containers, generates artifacts, install dependencies and creates the database:

```sh
make init
```

After setup:

- Frontend (compiled build via nginx): <https://dev.app.social.aleherse.com>
- Frontend (Vite dev server): <https://dev.app.social.aleherse.com:3000>
- API: <https://dev.api.social.aleherse.com>

## Worktrunk

[Worktrunk](https://worktrunk.dev) manages git worktrees for parallel branch
development, giving each branch its own Compose project and ports
through `.config/wt.toml`.

```sh
curl https://sh.rustup.rs -sSf | sh
cargo install worktrunk && wt config shell install
```

## Quality gates

Lefthook installs git hooks via `make init`:
fast format/lint/type checks on commit,
Conventional Commit message validation,
and unit tests plus codebase scanners on push.
Heavier CI jobs (Behat, Playwright) are requested per pull request
through the checkboxes in the PR template.

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
