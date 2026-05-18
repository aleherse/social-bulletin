# Social Bulletin

A monorepo for the Social Bulletin application:
a React frontend, a Symfony API, and a framework-free PHP core package,
developed locally through Docker Compose and Makefile entrypoints.

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) with the Docker Compose plugin
- `make`

Everything else (PHP, Composer, Node.js, PostgreSQL, test tooling)
runs inside containers.

## Host names

Add the local development host names to `/etc/hosts`:

```text
127.0.0.1 dev.api.social.aleherse.com dev.app.social.aleherse.com
```

## HTTPS certificates

The nginx container generates a local certificate authority and
certificates with [mkcert](https://github.com/FiloSottile/mkcert)
on first start and copies the root certificate to
`docker/certs/rootCA.pem`.
Trust that root certificate on your machine so the browser accepts
the development host names, for example:

```sh
# Debian/Ubuntu
sudo cp docker/certs/rootCA.pem /usr/local/share/ca-certificates/social-bulletin-rootCA.crt
sudo update-ca-certificates
```

Firefox and Chrome may need the certificate imported through their
own settings (Certificates → Authorities → Import).

## Bootstrap

```sh
make init   # build containers, start the stack, JWT keys, git hooks
make build  # install Composer/npm dependencies and build the frontend
make db     # create the database, run migrations, snapshot the test fixtures
```

After bootstrapping:

- Frontend (compiled build via nginx): <https://dev.app.social.aleherse.com>
- Frontend (Vite dev server): <https://dev.app.social.aleherse.com:3000>
- API: <https://dev.api.social.aleherse.com>

## Everyday commands

`make help` lists every target.
The common ones:

| Command      | Purpose                                                     |
|--------------|-------------------------------------------------------------|
| `make up`    | Start the development stack                                 |
| `make down`  | Stop the development stack                                  |
| `make logs`  | Follow service logs (`make logs service=php`)               |
| `make shell` | Open a shell in a container (`make shell service=node`)     |
| `make db`    | Rebuild the test database and fixtures snapshot             |
| `make tests` | Run the full test suite (PHPSpec, Behat, Vitest, Playwright)|
| `make lint`  | Run all linting and static analysis checks                  |

## Repository layout

| Path              | Contents                                            |
|-------------------|-----------------------------------------------------|
| `apps/api`        | Symfony HTTP application                            |
| `apps/web`        | React + Vite frontend (Feature-Sliced Design)       |
| `packages/core`   | Framework-free PHP domain logic                     |
| `infrastructure`  | AWS CDK deployment app (`live` and `preview`)       |
| `docker`          | Container images, nginx config, generated certs     |
| `specs/decisions` | Architecture Decision Records                       |
| `specs/changes`   | Change specifications and task lists                |

## Quality gates

Lefthook installs git hooks via `make init`:
fast format/lint/type checks on commit,
Conventional Commit message validation,
and unit tests plus codebase scanners on push.
Heavier CI jobs (Behat, Playwright) are requested per pull request
through the checkboxes in the PR template.
