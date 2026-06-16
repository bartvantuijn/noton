<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Pin;
use App\Models\Post;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

class PinnedItems extends Widget
{
    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.pinned-items';

    public static function canView(): bool
    {
        return Auth::check() && Pin::query()->whereBelongsTo(Auth::user())->exists();
    }

    public function getViewData(): array
    {
        return [
            'pins' => Pin::with(['pinnable' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                Category::class => ['tags'],
                Post::class => ['category', 'tags'],
            ])])
                ->whereBelongsTo(Auth::user())
                ->latest()
                ->get()
                ->filter(fn (Pin $pin) => $pin->pinnable)
                ->sortByDesc(fn (Pin $pin) => $pin->pinnable->views)
                ->values(),
        ];
    }
}
