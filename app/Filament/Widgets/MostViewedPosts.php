<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use Filament\Widgets\Widget;

class MostViewedPosts extends Widget
{
    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.most-viewed-posts';

    public function getViewData(): array
    {
        return [
            'posts' => Post::with('tags')->mostViewed(8)->get(),
        ];
    }
}
