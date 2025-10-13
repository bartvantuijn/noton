<?php

namespace App\Models;

use App\Enums\Visibility;
use App\Models\Scopes\VisibleScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy([VisibleScope::class])]
class Category extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => Visibility::class,
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
