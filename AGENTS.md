# AGENTS.md

Guidance for AI assistants working in this repository. `AGENTS.md` is the source of truth; tool-specific files such as `CLAUDE.md` should only point back here.

## Project overview

**Noton** is a free, self-hosted documentation platform built on **Laravel 12** and **Filament v4**, with private AI features powered by **Ollama** or **OpenClaw**. It is licensed under FSL-1.1 and distributed as a Docker image (`ghcr.io/bartvantuijn/noton`).

The product surface is basically a Filament admin panel mounted at the site root — there is no separate public frontend. Posts (Markdown documents) live inside nested Categories. Guests see public content; admins manage everything and can chat with an AI that is grounded in the documentation they have indexed.

## Tech stack

- **PHP** 8.2+ (CI and Docker build use 8.4)
- **Laravel** ^12.0
- **Filament** ^4.0 (panel builder + Livewire under the hood)
- **Livewire** (bundled with Filament)
- **Spatie Media Library** plugin (logos/favicons on `Setting`)
- **Spatie Tags** plugin (`Post` tags)
- **Phiki** for Markdown syntax highlighting
- **Flowframe Laravel Trend** for dashboard stats
- **Node** 24 + **Vite** 7 + **Tailwind CSS** 4 (`@tailwindcss/vite`)
- **PHPUnit** 11 for tests, **Mockery** for mocks
- **Pint** (Laravel preset + custom rules) and **Rector** for code style
- **SQLite** in-memory for tests; **PostgreSQL** or **MySQL** in production (configured via `DB_CONNECTION`)

## Repository layout

```
app/
├── Enums/                  # PHP 8.1 backed enums (e.g. Visibility)
├── Filament/
│   ├── Pages/              # Dashboard, Settings, Auth (Login, Register)
│   ├── Resources/          # CRUD resources per model (Categories, Posts, Users)
│   │   └── <Model>/{Pages,Schemas,Tables}
│   └── Widgets/            # Dashboard widgets (StatsOverview, MostViewedPosts…)
├── Helpers/                # Thin static helpers (App::hasUsers, etc.)
├── Http/Middleware/        # RedirectToLogin, RedirectToRegistration
├── Livewire/               # ChatModal (AI assistant)
├── Models/
│   └── Scopes/             # Global scopes (VisibleScope)
├── Observers/              # PostObserver
├── Policies/               # CategoryPolicy, PostPolicy, UserPolicy, SettingPolicy
├── Providers/              # AppServiceProvider, Filament/AdminPanelProvider
└── Services/               # OllamaService, OpenClawService
bootstrap/                  # app.php, providers.php
config/                     # Standard Laravel config (+ services.php for AI)
database/
├── factories/
├── migrations/
└── seeders/DatabaseSeeder.php
docker/                     # entrypoint.sh, supervisor/caddy configs
docs/setup.md               # User-facing setup guide
lang/                       # en/ + nl.json translations
resources/
├── css/app.css             # Tailwind entry
├── js/{app,bootstrap,main,jquery}.js
└── views/                  # Blade: filament/, livewire/, components/
routes/                     # web.php (empty — Filament handles routing), console.php
tests/
├── Feature/                # AuthRedirectTest, ChatModalTest, NestedCategoriesTest, VisibilityTest
├── Unit/                   # VisibilityTest
└── TestCase.php
```

## Architecture highlights

- **Filament is the whole app.** `AdminPanelProvider` mounts the panel at `path('')` (site root) and custom-builds the sidebar in `getNavigation()`: Dashboard + resource items, followed by dynamic navigation groups built from root `Category` records with their nested children and posts. There are no routes in `routes/web.php` — Filament discovers resources/pages/widgets under `app/Filament`.
- **Initial-user flow.** `RedirectToRegistration` forces the first visit to the registration page when `users` is empty, then hides registration once a user exists. The first registered user is expected to become the admin (`role = 'admin'`).
- **Guest vs. admin visibility.** `VisibleScope` is applied as a global scope to `Post` and `Category`. When there is no authenticated user, it hides private posts and all posts under any category that is private (directly or via ancestors). `RedirectToLogin` additionally guards edit/create/settings routes and redirects guests away from effectively-private view pages.
- **Nested categories.** `Category` has self-referencing `parent` / `children`. A `saving` hook (`validateParent`) prevents self-parenting and cycles. `getAncestors()` walks up without global scopes so the private-ancestor check works even when the scope would hide a parent.
- **Post content is Markdown.** Rendered with `Str::markdown()` and the Phiki CommonMark extension (`github-light-default` / `github-dark-default`) for code highlighting. `Post::summary()` strips tags and supports query highlighting for search results.
- **Tags** are Spatie tags on `Post`. `PostObserver::saved()` deletes any `Tag` with zero posts after a save (keeps the tag list tidy).
- **Settings** are stored in a single `settings` row (`Setting::singleton()`) with a JSON `data` column and `Arr::get` / `Arr::set` accessors. It also holds media (logo, favicon) via Spatie Media Library. The primary color comes from `appearance.color` and is exposed as the `colors.primary` container binding in `AppServiceProvider`.
- **AI chat (`App\Livewire\ChatModal`).** Injected into Filament via a render hook in `AppServiceProvider::boot()` (`GLOBAL_SEARCH_BEFORE`). It:
    1. Picks a provider from settings (`ai.provider` → `ollama` or `openclaw`, default from `services.ai.provider`).
    2. Tokenizes the user query, scores every `Post` by term frequency over `title + content`, and keeps the top 5 as context (respecting `VisibleScope` — private posts are never sent to the model).
    3. Builds a system prompt + chat history (last 10 messages) + a synthetic `system` message containing `RELEVANT DOCUMENTATION:` blocks delimited by `POST_START`/`POST_END`.
    4. Calls `OllamaService::chat()` (`POST /api/chat`) or `OpenClawService::chat()` (`POST chat/completions`, OpenAI-compatible). Both services auto-configure a Guzzle client with optional `Bearer` token.
    5. `OllamaService` additionally handles `isAvailable`, `hasModel`, and `pull` (to lazily download models with a separate `pull_timeout`).
- **Filament render hooks matter.** `AppServiceProvider::boot()` also injects the login button, global notice, quick actions, login/register `NotonWidget`, manifest tags, jQuery, `main.js`, and Dutch translations for JavaScript.
- **HTTPS forcing.** `AppServiceProvider::boot()` forces the URL scheme to HTTPS and sets `X-Forwarded-Proto` whenever `APP_ENV !== 'local'`. `bootstrap/app.php` also trusts all proxies.
- **Custom error handling.** `bootstrap/app.php` converts 403/404 responses inside the Filament admin panel (excluding auth routes) into a "This page is no longer available" notification and redirects to `/`.

## Development workflow

### Install

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate           # or: migrate:fresh --seed for demo data
npm run build                 # or npm run dev while iterating
```

Seeder creates a demo admin `test@example.com` / `password`, 3 root categories, 3 child categories per root category, 4 posts per category, and random tags/settings.

### Run everything locally

```bash
composer dev
```

This runs `php artisan serve`, `queue:listen`, `pail` (log streaming), and `vite` concurrently via `npx concurrently`.

### Tests

```bash
composer test        # runs `config:clear` + `php artisan test`
# or
php artisan test
php artisan test --filter=ChatModalTest
```

`phpunit.xml` uses SQLite `:memory:`, sync queue, and `array` drivers for cache/mail/session so tests are hermetic. Feature tests use `RefreshDatabase`. AI services are **always** mocked in tests (see `tests/Feature/ChatModalTest.php`) — never hit a real Ollama/OpenClaw instance from tests.

### Style tools

```bash
composer cs          # runs rector then pint
composer rector      # Rector (config: rector.php)
composer pint        # Pint with pint.json (Laravel preset + extra rules)
```

Pint enforces:
- `blank_line_before_statement`
- `concat_space: { spacing: one }` (use `'foo ' . $bar`)
- `single_trait_insert_per_statement`
- `types_spaces: { space: single }`

Rector runs at levels 0 for type coverage, dead code, and code quality across `app/`, `bootstrap/`, `config/`, `lang/`, `public/`, `resources/`, `routes/`, `tests/` (see `rector.php`). GitHub Actions run tests (`tests.yaml`) and style fixes (`fix-code-style.yaml`) on pushes to `main`; the style workflow auto-commits fixes, so run `composer cs` locally before pushing to avoid noisy follow-up commits.

### Docker

- `Dockerfile` is multi-stage: composer build → node build → PHP 8.4 FPM with Caddy + Supervisor. Exposes port `6686`. The container runs as the `noton` user and entrypoint is `docker/entrypoint.sh`.
- `docker-compose.example.yaml` is the user-facing template copied into `docker-compose.yaml`. The user-facing setup guide lives in `docs/setup.md`.
- Image is published to `ghcr.io/bartvantuijn/noton` by `.github/workflows/docker-publish.yaml` on `v*.*.*` tags (after tests pass).

## Code style

The core rule in this repo: **simple, readable, pragmatic code — with as little code as possible.** Bart writes in this style and has simplified AI-generated drift several times; commit `9e6a07b` is the most recent clean-up. New work matches.

- **Fewer lines, always.** Before adding code, ask whether existing code can do the work. Three similar inline lines beat a premature helper.
- **Read the file before touching it.** Match the shape, naming, and indentation of neighbouring code. Consistency beats personal preference.
- **Don't touch code you weren't asked to touch.** A bug fix fixes the bug. A feature ships the feature. No "while we're here" refactors, no surprise renames, no reformatting of unrelated code.
- **Eloquent and Collections are expressive — use them.** Prefer `whereHas`, `filter`, `pluck`, `contains`, and fluent chains over hand-rolled loops or manual ID-juggling. `VisibleScope` is the reference.
- **Models stay lean.** Add a method only when something outside the model needs it. No preemptive accessors, scopes, or "in case" helpers.
- **Helpers only when duplication is real.** Commit `f6a17dc` (`getNavigationRepeater`, three identical Repeater configs → one helper) is the bar. Two blocks that happen to look similar do not qualify.
- **No speculative robustness.** No `DB::transaction` or `try…catch` for things that can't go wrong in practice. No null-guards for values the framework guarantees. Let validation exceptions bubble — Filament handles them.
- **Fluent chains over decomposition.** If something fits in one `Model::with(...)->orderBy(...)->get()->map(...)` chain, write it that way. Intermediate variables only when they actually clarify.
- **No helper methods purely for a name.** A one-line helper called from a single site should be inlined.
- **Comments sparingly.** Only where intent isn't obvious from the code itself. No docblocks on self-explanatory methods. No `// Save the model` above `$model->save()`.
- **Benchmark: commit `2bee307`** (pre-nested categories). That's Bart's handwriting across `AdminPanelProvider`, `Category`, `VisibleScope`, and `Settings` — check your refactors against it.

## Git conventions

**Commit messages.** One line. Imperative. Sentence case. No trailing period. No type or scope prefix (no `feat:`, `fix:`, `[chore]`). No ticket numbers. No body. Aim for 3–7 words.

Good starting verbs: `Add`, `Change`, `Fix`, `Improve`, `Remove`, `Simplify`, `Show`, `Hide`, `Collapse`, `Require`, `Refactor`, `Update`.

Examples from the existing tree:

- `Add nested categories`
- `Change markdown editor styling`
- `Show category path in select labels`
- `Simplify navigation sorting and category selects`
- `Only show create post link for top-level categories`
- `Collapse settings sections by default`

Small miscellaneous cleanups that don't justify their own message are bundled under `Add general improvements`.

**Never:**

- Multi-line commit bodies.
- `Co-Authored-By` lines.
- Tool-specific generated footers or agent transcript links.
- `--amend` on published commits (unless Bart explicitly asks).
- `--no-verify` or skipping hooks.

**Branches.** When a session pins a branch, work on that branch — but don't create additional branches for unrelated work in the same session. For locally-run sessions, ask Bart before creating a branch; he cares about the shape of the tree. Never push directly to `main` unless explicitly asked.

**Pull requests.** Never open a PR unless Bart explicitly asks. Push to the designated branch and stop.

## Conventions and gotchas

- **No `.env` changes.** Configure via `docker-compose.yaml` env vars or the in-app Settings page. `.env.example` is the authoritative list of supported variables.
- **`Model::unguard()` is global** (`AppServiceProvider::boot()`). That means mass-assignment protection is disabled app-wide — never trust request input directly into `Model::create()`/`update()`; always validate with Filament schemas / form requests first.
- **Global scopes everywhere.** When writing queries that need to see private content (ancestor walks, admin queries, AI context building for logged-in users), use `withoutGlobalScopes()` deliberately. Leaking private data through the scope is a security regression — there are feature tests enforcing this for the ChatModal and visibility flows.
- **Translations.** All user-facing strings go through `__()` so they can be translated. English is in `lang/en/`, Dutch lives in `lang/nl.json` (also exposed to JS via `FilamentAsset::registerScriptData`). `APP_LOCALE` defaults to `en`; `docker-compose.example.yaml` currently also defaults to `en`.
- **Filament resource layout.** Each resource uses the v4 split: `<Model>Resource.php` + `Pages/` (ListX, CreateX, ViewX, EditX) + `Schemas/XForm.php` + `Tables/XTable.php`. Follow this exact structure when adding new resources.
- **Dashboard widgets** live in `app/Filament/Widgets/` and are wired into `Dashboard::getHeaderWidgets()`. The admin panel auto-discovers them but the dashboard explicitly lists them in order.
- **Policies.** Admins are short-circuited via `before()` checks (`$user->isAdmin()`). Non-admins currently have broad permissions on Posts/Categories but cannot delete. `viewAny` accepts a nullable user so guests can hit resource index pages (guest visibility is then further trimmed by `VisibleScope`).
- **`Setting::singleton()` is safe before migrations.** It swallows `Throwable` and returns an unpersisted instance. Keep it that way — code paths like `AppServiceProvider::register()` run before the database is usable during installation.
- **`MustVerifyEmail` but verification is disabled** in `AdminPanelProvider` (`emailVerification(isRequired: false)`). Don't rely on verification state in new logic without re-enabling it.
- **Routes.** `routes/web.php` is intentionally empty. All routing goes through Filament. `routes/console.php` is standard Laravel for scheduled commands.
- **Health check endpoint.** `/up` (configured in `bootstrap/app.php`).
- **Prefer fast, targeted reads.** Use repository search and small file reads before broad shell scans; the repo is small enough that targeted context is usually faster and clearer.

## Tests to run before shipping a change

At minimum:

```bash
composer cs          # style first — avoids the fix-code-style bot making a second commit
composer test        # full PHPUnit suite
```

Hot paths to keep in mind when changing visibility, categories, or the AI chat:

- `tests/Feature/VisibilityTest.php` — guest/admin visibility of nested categories and posts
- `tests/Feature/NestedCategoriesTest.php` — parent validation and tree building
- `tests/Feature/ChatModalTest.php` — documentation context selection and the private-post guarantee
- `tests/Feature/AuthRedirectTest.php` — `RedirectToLogin` / `RedirectToRegistration` behavior
- `tests/Unit/VisibilityTest.php` — the `Visibility` enum

## When adding a new feature resource

1. Create the Eloquent model + migration in `app/Models` and `database/migrations`. If it needs public/private, reuse the `Visibility` enum and apply `VisibleScope` via `#[ScopedBy]`.
2. Add a factory in `database/factories` and, if seeded data is useful, extend `DatabaseSeeder`.
3. Add a policy in `app/Policies` and (implicitly, via Laravel's convention) it will be auto-discovered. Mirror `PostPolicy::before()` to short-circuit admins.
4. Scaffold a Filament resource under `app/Filament/Resources/<Plural>/` with `Pages/`, `Schemas/<Singular>Form.php`, `Tables/<Plural>Table.php`. Look at `PostResource` / `CategoryResource` as templates.
5. Wire the resource into the sidebar by adding a `Gate::allows(...)` branch inside `AdminPanelProvider::getNavigation()` if it should only appear for certain users.
6. Add feature tests that cover both authenticated and guest scenarios.
7. Run `composer cs && composer test`.

## Reference: key files to read first

- `app/Providers/Filament/AdminPanelProvider.php` — panel wiring, sidebar builder, middleware order
- `app/Providers/AppServiceProvider.php` — render hooks (`ChatModal`, notices, quick actions), HTTPS forcing, asset registration
- `app/Livewire/ChatModal.php` + `app/Services/{Ollama,OpenClaw}Service.php` — AI chat pipeline
- `app/Models/Scopes/VisibleScope.php` + `app/Models/{Category,Post}.php` — visibility rules
- `app/Http/Middleware/RedirectTo{Login,Registration}.php` — first-user and auth redirect flow
- `bootstrap/app.php` — exception handling, proxies, health check
- `docs/setup.md` — the user-facing deployment guide (keep in sync when env vars change)
