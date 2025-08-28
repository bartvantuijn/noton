<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Foundation\Inspiring;

class NotonWidget extends Widget
{
    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.noton-widget';

    public function getViewData(): array
    {
        return [
            'quote' => Inspiring::quote(),
        ];
    }
}
