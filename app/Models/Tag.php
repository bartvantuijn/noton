<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;
use Spatie\Tags\Tag as BaseTag;

class Tag extends BaseTag
{
    #[Scope]
    protected function mostUsed(Builder $query, int $limit = 1): void
    {
        $query->orderByDesc(
            DB::table('taggables')
                ->selectRaw('count(*)')
                ->whereColumn('taggables.tag_id', 'tags.id')
        )->take($limit);
    }

    public function categories(): MorphToMany
    {
        return $this->morphedByMany(Category::class, 'taggable');
    }

    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }
}
