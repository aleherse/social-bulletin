## Command surface: Makefile first

All development commands run through `make`,
which delegates to `docker compose run --rm` —
never run PHP, Composer, npm, or database tools on the host.
`make help` lists every target.

The ones agents need most:

| Command          | Purpose                                            |
|------------------|----------------------------------------------------|
| `make db`        | Rebuild the test database and DSLR snapshot        |
| `make tests`     | Full suite: PHPSpec, Behat, Vitest, Playwright     |
| `make php-unit`  | PHPSpec for `packages/core`                        |
| `make api-tests` | Behat for `apps/api` (needs a `make db` snapshot)  |
| `make web-unit`  | Vitest for `apps/web`                              |
| `make web-e2e`   | Playwright against the real API                    |
| `make lint`      | All linters and static analysis                    |
| `make console`   | Symfony console (`make console cmd="cache:clear"`) |
