<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Settings;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Users\UserResource;
use App\Http\Middleware\RedirectToLogin;
use App\Http\Middleware\RedirectToRegistration;
use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->login(Login::class)
            ->registration(Register::class)
            // ->passwordReset()
            ->emailVerification(isRequired: false)
            ->profile(isSimple: false)
            ->font('Exo')
            ->colors([
                'primary' => Color::hex(app('colors.primary')),
            ])
            ->viteTheme('resources/css/app.css')
            ->brandLogo(fn () => Setting::singleton()->getFirstMediaUrl('logo') ?: asset('images/logo.svg'))
            ->brandLogoHeight('2.5rem')
            ->homeUrl('/')
            ->favicon(fn () => Setting::singleton()->getFirstMediaUrl('favicon') ?: asset('images/favicon.svg'))
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                // Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // AccountWidget::class,
                // FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                RedirectToRegistration::class,
                RedirectToLogin::class,
            ])
            ->authMiddleware([
                // Authenticate::class,
            ])
            ->navigation(fn (NavigationBuilder $builder): NavigationBuilder => self::getNavigation($builder))
            ->userMenuItems([
                MenuItem::make()
                    ->label(__('Settings'))
                    ->url(fn (): string => Settings::getUrl())
                    ->icon(Heroicon::OutlinedCog)
                    ->visible(fn () => Settings::canAccess()),
            ])
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchFieldKeyBindingSuffix();
    }

    public function getNavigation(NavigationBuilder $builder): NavigationBuilder
    {
        return $builder
            ->items(Dashboard::getNavigationItems())
            ->when(Gate::allows('viewAny', Category::class), fn ($builder) => $builder->items(CategoryResource::getNavigationItems()))
            ->when(Gate::allows('viewAny', Post::class), fn ($builder) => $builder->items(PostResource::getNavigationItems()))
            ->when(Gate::allows('viewAny', User::class), fn ($builder) => $builder->items(UserResource::getNavigationItems()))
            ->when(Gate::allows('viewAny', Setting::class), fn ($builder) => $builder->items(Settings::getNavigationItems()))
            ->groups($this->getCategoryNavigationGroups());
    }

    protected function getCategoryNavigationGroups(): array
    {
        $categories = Category::with(['posts' => fn ($posts) => $posts->orderBy('sort')])
            ->orderBy('sort')
            ->get();

        $children = $categories->groupBy('parent_id');
        $activeCategoryId = $this->getActiveCategoryId();

        // Collect the active category and its ancestors so we can keep the open branch expanded.
        $activeIds = collect();
        $current = $categories->firstWhere('id', $activeCategoryId);

        while ($current) {
            $activeIds->push($current->id);
            $current = $categories->firstWhere('id', $current->parent_id);
        }

        return $categories->whereNull('parent_id')
            ->map(fn (Category $category) => NavigationGroup::make($category->name)
                ->icon(Heroicon::OutlinedFolder)
                ->items($this->getCategoryNavigationItems($category, $children, $activeIds, $activeCategoryId)))
            ->all();
    }

    protected function getCategoryNavigationItems(Category $category, Collection $children, Collection $activeIds, ?int $activeCategoryId, int $depth = 0): array
    {
        $items = collect();

        // Show nested categories on the root level and inside the open branch.
        if ($depth === 0 || $activeIds->contains($category->id)) {
            foreach ($children->get($category->id, collect()) as $child) {
                $onBranch = $activeIds->contains($child->id);

                $items->push(
                    NavigationItem::make($child->name)
                        ->badge($onBranch ? '▾' : '▸', $onBranch ? 'primary' : 'gray')
                        ->icon(Heroicon::OutlinedFolder)
                        ->url(CategoryResource::getUrl('view', ['record' => $child]))
                        ->isActiveWhen(fn () => $child->id === $activeCategoryId)
                );

                $items = $items->merge($this->getCategoryNavigationItems($child, $children, $activeIds, $activeCategoryId, $depth + 1));
            }
        }

        // Show posts on the root level and inside the active category.
        if ($depth === 0 || $activeIds->contains($category->id)) {
            $items = $items->merge($category->posts->map(fn (Post $post) => NavigationItem::make($post->title)
                ->url(PostResource::getUrl('view', ['record' => $post]))
                ->isActiveWhen(fn () => $this->isPostActive($post))));

            // Keep the quick create action on root categories.
            if ($depth === 0 && Gate::allows('create', Post::class)) {
                $items->push(
                    NavigationItem::make(__('Create post'))
                        ->badge('+')
                        ->url(route('filament.admin.resources.posts.create', ['category_id' => $category->id]))
                );
            }
        }

        return $items->all();
    }

    protected function getActiveCategoryId(): ?int
    {
        return match (true) {
            request()->routeIs('filament.admin.resources.categories.view') => Category::findBySlugPath((string) request()->route('record'))?->id,
            request()->routeIs('filament.admin.resources.categories.edit') => (int) request()->route('record') ?: null,
            request()->routeIs('filament.admin.resources.posts.view') => Category::findBySlugPath((string) request()->route('category'))?->id,
            request()->routeIs('filament.admin.resources.posts.edit') => Post::find(request()->route('record'))?->category_id,
            request()->routeIs('filament.admin.resources.posts.create') => request()->integer('category_id') ?: null,
            default => null,
        };
    }

    protected function isPostActive(Post $post): bool
    {
        return match (true) {
            request()->routeIs('filament.admin.resources.posts.view') => request()->route('record') === $post->getSlug() && request()->route('category') === $post->category->getSlugPath(),
            request()->routeIs('filament.admin.resources.posts.edit') => (int) request()->route('record') === $post->getKey(),
            default => false,
        };
    }
}
