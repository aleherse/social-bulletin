# ADR-0009: Adopt symfony/translation for Backend Internationalisation

- Status: Accepted
- Date: 2026-05-18

## Context

The Symfony API (`apps/api`) has no internationalisation infrastructure.
Validation error messages, API error responses, and system-generated
notifications are hardcoded in English. Introducing i18n now, while the
codebase is a skeleton, avoids expensive retrofitting when user-facing
messages grow.

A translation strategy is needed that fits two constraints already in place:

- The domain and application layer lives in `packages/core`, which must not
  depend on Symfony (ADR-0004). Any translation abstraction used in core
  must remain framework-free.
- The frontend handles its own locale detection and catalogue loading via
  react-i18next (ADR-0008). The backend only needs to serve localised
  strings for API responses whose content is determined server-side:
  validation errors, constraint violation messages, and server-generated
  notifications or emails.

Three options were considered:

**symfony/translation** (native component)
- Ships with Symfony; no additional dependency when using the full framework stack.
- `TranslatorInterface` lives in `symfony/translation-contracts`, a
  near-zero-dependency package that core can depend on without pulling in Symfony.
- YAML message catalogues are readable, diff-friendly, and tool-agnostic.
- ICU message format (plurals, gender, date/number formatting) is supported
  natively via the `MessageFormatter` from `php-intl`.
- Domain-based catalogue separation maps naturally to bounded-context ownership
  (e.g. `validators`, `notifications`, `errors`).
- Locale negotiation from the `Accept-Language` request header is handled by
  Symfony's `LocaleSwitcher` and the `_locale` request attribute without
  custom code.

**php-translation/symfony-bundle**
- Adds extraction tooling and push/pull integration with professional
  translation platforms (Phrase, Crowdin, Lokalise).
- Wraps `symfony/translation` rather than replacing it, so all native
  capabilities still apply.
- Overkill at skeleton stage; adds platform coupling before any translation
  workflow exists.

**Hardcoded English only**
- Zero setup cost and no abstraction layer needed.
- Eliminates localisation flexibility permanently unless refactored later.
- Acceptable for a prototype but not for a product expecting international users.

## Decision

Adopt **`symfony/translation`** and **`symfony/translation-contracts`** as
the internationalisation stack for the backend.

Message catalogues are YAML files stored under
`apps/api/translations/<domain>+intl-icu.<locale>.yaml`. The `+intl-icu`
suffix activates ICU message format for all keys in that catalogue.

Translation domains follow bounded-context ownership:
- `validators` — constraint violation messages.
- `notifications` — server-generated user-facing notifications and emails.
- `errors` — HTTP error response messages (4xx/5xx).

`packages/core` declares a dependency on `symfony/translation-contracts` only.
Domain and application services that require message translation receive
`Symfony\Contracts\Translation\TranslatorInterface` via constructor injection.
The concrete `Symfony\Component\Translation\Translator` is wired in the API
application container, keeping core fully framework-free.

Locale negotiation uses the `Accept-Language` request header. An event listener
on `kernel.request` reads the header, resolves it against the configured
available locales, and sets the request locale via `$request->setLocale()`.
The fallback locale is `en` for all environments.

## Consequences

Positive:
- `symfony/translation-contracts` keeps `packages/core` free of framework
  coupling while still allowing domain services to emit localised messages.
- YAML catalogues are human-readable, easy to diff, and straightforward to
  hand off to translators or import into a translation management platform later.
- ICU message format provides pluralisation and number/date interpolation
  without additional packages (requires `php-intl` extension, already a
  Symfony 7 prerequisite).
- Locale negotiation from `Accept-Language` matches REST conventions and
  requires no client-side session or cookie state.
- Domain-scoped catalogues allow feature teams to own their translation keys
  without creating a shared file with unrelated entries.

Tradeoffs:
- `php-intl` must be present in the PHP container; this is already expected by
  Symfony 7 but must be confirmed in the Dockerfile.
- ICU message syntax is more verbose than simple `%placeholder%` interpolation;
  contributors must learn the format.
- Locale negotiation from `Accept-Language` can produce surprising behaviour
  when the header contains a quality-weighted list; the resolution strategy
  must be documented.
- Catalogue validation (missing keys, unused keys) requires either a Composer
  script or a CI lint step to be added separately.

Follow-ups:
- Confirm `php-intl` is installed in the API Docker image.
- Add a Symfony translation lint step (`bin/console translation:extract --force`)
  to CI to detect missing or orphaned keys early.
- Evaluate `php-translation/symfony-bundle` if a professional translation
  management platform integration becomes necessary.
- Document the `Accept-Language` locale resolution rules in the API onboarding
  guide.
