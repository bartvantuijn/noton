<?php

namespace App\Filament\Widgets;

use App\Helpers\App;
use App\Models\Tag;
use Filament\Widgets\Widget;

class MostUsedTags extends Widget
{
    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.most-used-tags';

    public static function canView(): bool
    {
        return App::hasTags();
    }

    public function getViewData(): array
    {
        return [
            'tags' => Tag::mostUsed(5)->get(),
        ];
    }
}
