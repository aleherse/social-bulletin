# ADR-0009: Adopt symfony/translation for Backend Internationalisation

- Status: Accepted
- Date: 2026-05-18

## Context

The Symfony API needs backend localisation before user-facing server messages are implemented.

The strategy must fit these constraints:

- `packages/core` must remain framework-free; any core translation abstraction cannot depend on Symfony framework code.
- The frontend translates browser-owned UI. The backend translates only server-owned content: validation errors, constraint violations, HTTP error messages, notifications, and emails.

`symfony/translation` fits because it ships with Symfony, exposes a lightweight `symfony/translation-contracts` interface for core, supports YAML catalogues, supports ICU through `php-intl`, and maps well to domain-scoped catalogues. Platform translation bundles are premature. Hardcoded English would make later localisation costly.

## Decision

Adopt **`symfony/translation`** and **`symfony/translation-contracts`** as the internationalisation stack for the backend.

Message catalogues are YAML files stored under `apps/api/translations/<domain>+intl-icu.<locale>.yaml`. The `+intl-icu` suffix activates ICU message format for all keys in that catalogue.

Translation domains follow bounded-context ownership:
- `validators` — constraint violation messages.
- `notifications` — server-generated user-facing notifications and emails.
- `errors` — HTTP error response messages (4xx/5xx).

`packages/core` declares a dependency on `symfony/translation-contracts` only. Domain and application services that require message translation receive `Symfony\Contracts\Translation\TranslatorInterface` via constructor injection. The concrete `Symfony\Component\Translation\Translator` is wired in the API application container, keeping core fully framework-free.

Locale negotiation uses the `Accept-Language` request header. An event listener on `kernel.request` reads the header, resolves it against the configured available locales, and sets the request locale via `$request->setLocale()`. The fallback locale is `en` for all environments.

## Consequences

- `symfony/translation-contracts` keeps `packages/core` free of framework coupling while still allowing domain services to emit localised messages.
- YAML catalogues are human-readable, easy to diff, and straightforward to hand off to translators or import into a translation management platform later.
- ICU message format provides pluralisation and number/date interpolation without additional packages (requires `php-intl` extension, already a Symfony 7 prerequisite).
- Locale negotiation from `Accept-Language` matches REST conventions and requires no client-side session or cookie state.
- Domain-scoped catalogues allow feature teams to own their translation keys without creating a shared file with unrelated entries.
- `php-intl` must be present in the PHP container; this is already expected by Symfony 7 but must be confirmed in the Dockerfile.
- ICU message syntax is more verbose than simple `%placeholder%` interpolation; contributors must learn the format.
- Locale negotiation from `Accept-Language` can produce surprising behaviour when the header contains a quality-weighted list; the resolution strategy must be documented.
- Catalogue validation (missing keys, unused keys) requires either a Composer script or a CI lint step to be added separately.
- Confirm `php-intl` is installed in the API Docker image.
- Add a Symfony translation lint step (`bin/console translation:extract --force`) to CI to detect missing or orphaned keys early.
- Evaluate `php-translation/symfony-bundle` if a professional translation management platform integration becomes necessary.
- Document the `Accept-Language` locale resolution rules in the API onboarding guide.
