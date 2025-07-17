<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $posts = Trend::model(Post::class)
            ->between(now()->subYear(), now())
            ->perMonth()
            ->count();

        $categories = Trend::model(Category::class)
            ->between(now()->subYear(), now())
            ->perMonth()
            ->count();

        $tags = Trend::model(Tag::class)
            ->between(now()->subYear(), now())
            ->perMonth()
            ->count();

        return [
            Stat::make(__('Posts'), Post::count())
                ->color('primary')
                ->icon('heroicon-o-document-text')
                ->description(__(':count this month', ['count' => $posts->last()->aggregate]))
                ->chart($posts->map(fn (TrendValue $value) => $value->aggregate)->toArray()),
            Stat::make(__('Categories'), Category::count())
                ->color('primary')
                ->icon('heroicon-o-folder')
                ->description(__(':count this month', ['count' => $categories->last()->aggregate]))
                ->chart($categories->map(fn (TrendValue $value) => $value->aggregate)->toArray()),
            Stat::make(__('Tags'), Tag::count())
                ->color('primary')
                ->icon('heroicon-o-tag')
                ->description(__(':count this month', ['count' => $tags->last()->aggregate]))
                ->chart($tags->map(fn (TrendValue $value) => $value->aggregate)->toArray()),
        ];
    }
}
