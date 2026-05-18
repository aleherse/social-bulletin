# ADR-0202: Adopt symfony/translation for Backend Internationalisation

- Status: Accepted
- Date: 2026-06-12

## Context

The backend needs localised user-facing messages without coupling core logic to Symfony internals.

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

- Backend messages use Symfony translation catalogues.
- YAML ICU files are grouped by message domain and locale.
- Core depends only on translation contracts.
- The API app wires the concrete translator.
- Request locale comes from `Accept-Language` with `en` fallback.
- Translation keys, domains, and locale files must stay complete and consistent.
