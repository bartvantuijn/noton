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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();

        // Force HTTPS scheme
        if (!app()->environment('local')) {
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

        // Register login render hook
        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_END,
            fn (): string => Blade::render('
            @guest
                <x-filament::button tag="a" href="{{ route(\'filament.admin.auth.login\') }}" color="gray">
                    {{ __(\'Login\') }}
                </x-filament::button>
            @endguest
            '),
        );
    }
}
