<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Post;
use Filament\Widgets\Widget;

class MostViewed extends Widget
{
    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.most-viewed';

    public function getViewData(): array
    {
        return [
            'records' => Post::with('tags')->mostViewed(8)->get()
                ->concat(Category::with('tags')->mostViewed(8)->get())
                ->sortByDesc('views')
                ->take(8),
        ];
    }
}
