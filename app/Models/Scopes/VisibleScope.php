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

        $hiddenCategoryIds = $this->getHiddenCategoryIds();

        if ($model instanceof Category) {
            if ($hiddenCategoryIds) {
                $builder->whereNotIn('id', $hiddenCategoryIds);
            }

            return;
        }

        if ($model instanceof Post) {
            $builder->where('visibility', Visibility::Public);

            if ($hiddenCategoryIds) {
                $builder->whereNotIn('category_id', $hiddenCategoryIds);
            }
        }
    }

    protected function getHiddenCategoryIds(): array
    {
        $categories = Category::withoutGlobalScopes()
            ->get(['id', 'parent_id', 'visibility'])
            ->groupBy('parent_id');

        // Start with categories that are explicitly private.
        $hiddenIds = Category::withoutGlobalScopes()
            ->where('visibility', Visibility::Private)
            ->pluck('id')
            ->all();

        $queue = $hiddenIds;

        // Every child of a private category should be hidden for guests too.
        while ($parentId = array_shift($queue)) {
            foreach ($categories->get($parentId, []) as $category) {
                if (in_array($category->id, $hiddenIds, true)) {
                    continue;
                }

                $hiddenIds[] = $category->id;
                $queue[] = $category->id;
            }
        }

        return $hiddenIds;
    }
}
