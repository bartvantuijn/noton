<?php

namespace App\Models\Scopes;

use App\Enums\Visibility;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class VisibleScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::user()) {
            return;
        }

        $hiddenCategoryIds = Category::withoutGlobalScopes()->get()
            ->filter(fn (Category $category) => $category->isEffectivelyPrivate())
            ->pluck('id');

        if ($model instanceof Category) {
            $builder->whereNotIn('id', $hiddenCategoryIds);
        }

        if ($model instanceof Post) {
            $builder->where('visibility', Visibility::Public)
                ->whereNotIn('category_id', $hiddenCategoryIds);
        }
    }
}
