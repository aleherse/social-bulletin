# Social Bulletin

A monorepo for the Social Bulletin application:
a React frontend, a Symfony API, and a framework-free PHP core package,
developed locally through Docker Compose and Makefile entrypoints.

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) with the Docker Compose plugin
- `make`

Everything else runs inside containers.

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

## Bootstrap

Builds and starts the containers, generates artifacts, install dependencies and creates the database:

```sh
make init
```

After bootstrapping:

- Frontend (compiled build via nginx): <https://dev.app.social.aleherse.com>
- Frontend (Vite dev server): <https://dev.app.social.aleherse.com:3000>
- API: <https://dev.api.social.aleherse.com>

## Quality gates

Lefthook installs git hooks via `make init`:
fast format/lint/type checks on commit,
Conventional Commit message validation,
and unit tests plus codebase scanners on push.
Heavier CI jobs (Behat, Playwright) are requested per pull request
through the checkboxes in the PR template.
