<?php

namespace App\Models;

use App\Enums\Visibility;
use App\Models\Scopes\VisibleScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

#[ScopedBy([VisibleScope::class])]
class Category extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            $category->validateParent();
        });
    }

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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function getSelectLabel(): string
    {
        $label = $this->getAncestors()
            ->pluck('name')
            ->push($this->name)
            ->join(' / ');

        if ($this->visibility === Visibility::Private) {
            $label .= ' (' . __('Private') . ')';
        }

        return $label;
    }

    public function getAncestors(): Collection
    {
        $ancestors = collect();
        $parent = $this->parent()->withoutGlobalScopes()->first();

        while ($parent) {
            $ancestors->prepend($parent);
            $parent = $parent->parent()->withoutGlobalScopes()->first();
        }

        return $ancestors;
    }

    public function isEffectivelyPrivate(): bool
    {
        return $this->visibility === Visibility::Private || $this->getAncestors()->contains(fn (self $category) => $category->visibility === Visibility::Private);
    }

    public function validateParent(): void
    {
        if (! $this->parent_id) {
            return;
        }

        if ($this->parent_id === $this->id) {
            throw ValidationException::withMessages([
                'parent_id' => __('A category cannot be its own parent.'),
            ]);
        }

        $parent = self::withoutGlobalScopes()->find($this->parent_id);

        while ($parent) {
            if ($parent->id === $this->id) {
                throw ValidationException::withMessages([
                    'parent_id' => __('A category cannot be nested inside its own child.'),
                ]);
            }

            $parent = $parent->parent()->withoutGlobalScopes()->first();
        }
    }
}
