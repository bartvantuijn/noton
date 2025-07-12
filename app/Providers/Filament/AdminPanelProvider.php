<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Settings;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\PostResource;
use App\Filament\Resources\UserResource;
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
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
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
            ->when(App::hasUsers(), fn ($panel) => $panel->login(Login::class))
            ->when(!App::hasUsers(), fn ($panel) => $panel->registration(Register::class))
            //->passwordReset()
            ->emailVerification(isRequired: false)
            ->profile(isSimple: false)
            ->font('Montserrat')
            ->colors([
                'primary' => Color::hex(app('colors.primary')),
            ])
            ->viteTheme('resources/css/app.css')
            ->brandLogo(fn () => Setting::singleton()->getFirstMediaUrl('logo') ?: asset('images/logo.svg'))
            ->brandLogoHeight('2rem')
            ->homeUrl('/')
            ->favicon(fn () => Setting::singleton()->getFirstMediaUrl('logo') ?: asset('images/favicon-96x96.png'))
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                //Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                //Widgets\AccountWidget::class,
                //Widgets\FilamentInfoWidget::class,
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
            ])
            ->authMiddleware([
                //Authenticate::class,
            ])
            ->navigation(fn(NavigationBuilder $builder): NavigationBuilder => self::getNavigation($builder))
            ->userMenuItems([
                MenuItem::make()
                    ->label(__('Settings'))
                    ->url(fn (): string => Settings::getUrl())
                    ->icon('heroicon-o-cog')
                    ->visible(fn () => Settings::canAccess())
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
            ->groups(
                Category::with(['posts' => fn ($posts) => $posts->orderBy('sort')])
                    ->orderBy('sort')
                    ->get()
                    ->map(function ($category) {
                        return NavigationGroup::make($category->name)
                            ->icon('heroicon-o-folder')
                            ->items(
                                collect(
                                    $category->posts->map(function ($post) {
                                        return NavigationItem::make($post->title)
                                            ->url(route('filament.admin.resources.posts.view', ['record' => $post]))
                                            ->isActiveWhen(fn () => (request()->routeIs('filament.admin.resources.posts.view') || request()->routeIs('filament.admin.resources.posts.edit')) && request()->route('record') == $post->id);
                                    })
                                )
                                ->push(
                                    NavigationItem::make(__('Create post'))
                                        ->badge('+')
                                        ->url(route('filament.admin.resources.posts.create', ['category_id' => $category->id]))
                                        ->visible(Gate::allows('create', Category::class))
                                )
                                ->toArray()
                            );
                    })->toArray()
            );
    }
}
