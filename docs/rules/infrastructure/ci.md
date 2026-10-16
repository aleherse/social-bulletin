# Infrastructure / CI

## infrastructure-ci-0001: Narrowed checks stay inside each tool's paths

**WHEN** adding or changing a `Makefile` check that `FILES` narrows (the pre-commit hook passes it the staged files)

**THEN** pass the tool only the files inside the paths its own configuration analyses, so a narrowed run never judges
more than `make ci` does; with none left, report "no matching files" and skip.

**Example:**

| Wrong                                                 | Right                                                                          |
|-------------------------------------------------------|--------------------------------------------------------------------------------|
| `php_files = $(filter %.php,$(FILES))`                | `php_files = $(filter apps/api/src/%.php packages/core/src/%.php,$(FILES))`    |
| PHPStan handed `packages/core/spec/…Spec.php`         | the spec skipped: `phpstan.dist.neon` and `ecs.php` analyse `src` only         |
