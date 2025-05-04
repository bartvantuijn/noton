<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\NotonWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string $view = 'filament.pages.dashboard';

    public function getColumns(): int | string | array
    {
        return 2;
    }

    public function getTitle(): string
    {
        return __('Dashboard');
    }

    public static function getNavigationLabel(): string
    {
        return __('Dashboard');
    }

    public function getHeaderWidgets(): array
    {
        return [
            NotonWidget::class,
        ];
    }
}
