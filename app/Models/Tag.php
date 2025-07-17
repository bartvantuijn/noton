<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Tags\Tag as BaseTag;

class Tag extends BaseTag
{
    #[Scope]
    protected function mostUsed(Builder $query, int $limit = 1): void
    {
        $query->withCount('posts')->orderBy('posts_count', 'desc')->take($limit);
    }

    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }
}
