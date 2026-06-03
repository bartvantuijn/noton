# Contributing to Noton

Thanks for your interest in contributing. Noton is a small Laravel + Filament
application and most contributions are small bug fixes, documentation
improvements, or Filament/Livewire polish.

## Development setup

You need PHP 8.2+ (CI uses 8.4), Composer, Node 24, and a database
(SQLite in-memory is enough for tests; PostgreSQL or MySQL for local
development). Clone the repo and run:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

For day-to-day work, `composer dev` runs the PHP server, queue worker,
log stream (`pail`), and Vite together via `concurrently`.

## Tests

```bash
composer test         # config:clear + php artisan test
php artisan test --filter=ChatModalTest
```

Tests use SQLite `:memory:` and a sync queue, so they are hermetic.
AI services (`OllamaService`, `OpenClawService`) are **always mocked** in
tests — see `tests/Feature/ChatModalTest.php` for the pattern.

## Code style

We run Rector and Pint in CI:

```bash
composer rector       # Rector (config: rector.php)
composer pint         # Pint (Laravel preset + project rules)
composer cs           # rector then pint, in that order
```

PRs that touch PHP should leave `composer cs` clean.

## Pull requests

- One focused change per PR. Large refactors should be discussed in an
  issue first.
- The PR template asks for a one-line summary, a "How tested" section,
  and screenshots for UI changes — please fill all of them in.
- If your change touches migrations, include the up **and** down paths
  and a note on backwards compatibility.
- If your change touches AI behaviour (`OllamaService`, `OpenClawService`,
  `ChatModal`), keep the system prompt template under
  `App\Livewire\ChatModal` and update the related feature test.

## AI-assisted contributions

If you use an AI assistant to help draft the patch, please read
`AGENTS.md` first. It documents the project structure, the conventions
the maintainer cares about, and the test/lint commands an AI should
run before opening a PR.

## Reporting issues

Use the bug and feature request templates under
`.github/ISSUE_TEMPLATE/`. They ask for the information the maintainer
needs to triage on the first pass; please fill them in.

## License

By contributing, you agree that your contributions are licensed under
the project's [FSL-1.1](LICENSE) license.
