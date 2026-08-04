<?php

namespace App\Providers;

use App\Models\Setting;
use Carbon\Carbon;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $loader = AliasLoader::getInstance();

        // Register aliases
        $loader->alias('Carbon', Carbon::class);
        $loader->alias('FilamentAsset', FilamentAsset::class);

        // Register custom colors
        $this->app->singleton('colors.primary', function () {
            return Setting::singleton()->get('appearance.color') ?? '#3b82f6';
        });

        // Override HTML sanitizer
        $this->app->scoped(
            HtmlSanitizerInterface::class,
            fn (): HtmlSanitizer => new HtmlSanitizer(
                (new HtmlSanitizerConfig)
                    ->allowSafeElements()
                    ->allowRelativeLinks()
                    ->allowRelativeMedias()
                    ->allowElement('input', ['type', 'checked']) // Allow inputs
                    ->allowAttribute('class', allowedElements: '*')
                    ->allowAttribute('data-color', allowedElements: '*')
                    ->allowAttribute('data-from-breakpoint', allowedElements: '*')
                    ->allowAttribute('data-type', allowedElements: '*')
                    ->allowAttribute('style', allowedElements: '*')
                    ->allowAttribute('width', allowedElements: 'img')
                    ->allowAttribute('height', allowedElements: 'img')
                    ->withMaxInputLength(500000),
            ),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();

        // Force HTTPS scheme
        if (! app()->environment('local')) {
            URL::forceScheme('https');
            request()->headers->set('X-Forwarded-Proto', 'https');
        }

        // Register custom assets
        FilamentAsset::register([
            Js::make('jquery', Vite::asset('resources/js/jquery.js')),
            Js::make('main', Vite::asset('resources/js/main.js')),
        ]);

        // Register script data
        FilamentAsset::registerScriptData([
            'translations' => File::json(lang_path('nl.json')),
        ]);

        // Register manifest
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_START,
            fn (): string => Blade::render('
            <link rel="manifest" href="{{ asset(\'manifest.json\') }}" />
            <meta name="theme-color" media="(prefers-color-scheme: light)" content="#ffffff">
            <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#18181b">
            '),
        );

        // Register login render hook
        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_AFTER,
            fn (): string => Blade::render('
            @guest
                <x-filament::button tag="a" href="{{ route(\'filament.admin.auth.login\') }}" color="gray">
                    {{ __(\'Login\') }}
                </x-filament::button>
            @endguest
            '),
        );

        // Register notice render hook
        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_AFTER,
            fn (): string => Blade::render(view('filament.components.notice')->render()),
        );

        // Register quick actions render hook
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): string => Blade::render(view('filament.components.quick-actions')->render()),
        );

        // Register login render hook
        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
            fn (): string => Blade::render('@livewire(\App\Filament\Widgets\NotonWidget::class)'),
        );

        // Register register render hook
        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE,
            fn (): string => Blade::render('@livewire(\App\Filament\Widgets\NotonWidget::class)'),
        );

        // Register GitHub render hook
        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
            fn (): string => Blade::render('
            <x-filament::button tag="a" href="https://github.com/bartvantuijn/noton" target="_blank" color="gray" class="ms-4 md:ms-0">
                <svg class="h-5 w-5 shrink-0 overflow-visible" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M6.766 11.328c-2.063-.25-3.516-1.734-3.516-3.656 0-.781.281-1.625.75-2.188-.203-.515-.172-1.609.063-2.062.625-.078 1.468.25 1.968.703.594-.187 1.219-.281 1.985-.281.765 0 1.39.094 1.953.265.484-.437 1.344-.765 1.969-.687.218.422.25 1.515.046 2.047.5.593.766 1.39.766 2.203 0 1.922-1.453 3.375-3.547 3.64.531.344.89 1.094.89 1.954v1.625c0 .468.391.734.86.547C13.781 14.359 16 11.53 16 8.03 16 3.61 12.406 0 7.984 0 3.563 0 0 3.61 0 8.031a7.88 7.88 0 0 0 5.172 7.422c.422.156.828-.125.828-.547v-1.25c-.219.094-.5.156-.75.156-1.031 0-1.64-.562-2.078-1.609-.172-.422-.36-.672-.719-.719-.187-.015-.25-.093-.25-.187 0-.188.313-.328.625-.328.453 0 .844.281 1.25.86.313.452.64.655 1.031.655s.641-.14 1-.5c.266-.265.47-.5.657-.656" />
                </svg>
                <span data-github-stars></span>
            </x-filament::button>
            '),
        );

        // Register chat render hook
        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
            fn (): string => Blade::render('@livewire(\App\Livewire\ChatModal::class)'),
        );
    }
}
