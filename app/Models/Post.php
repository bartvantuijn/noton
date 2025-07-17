<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Spatie\Tags\HasTags;

class Post extends Model
{
    use HasFactory, HasTags;

    #[Scope]
    protected function mostViewed(Builder $query): void
    {
        $query->orderBy('views', 'desc');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function summary(int $length = 150, ?string $highlight = null): HtmlString
    {
        // Parse record content
        $content = Str::markdown($this->content);

        // Strip HTML tags
        $content = strip_tags($content);

        // Decode HTML entities
        $content = html_entity_decode($content);

        if ($highlight) {
            // Find match position
            $position = stripos($content, $highlight);

            if ($position !== false) {
                // Determine snippet start
                $start = max(0, $position - ($length / 2));

                // Extract content snippet
                $snippet = mb_substr($content, $start, $length + mb_strlen($highlight));

                // Highlight matched text
                $highlighted = preg_replace(
                    '/' . preg_quote($highlight, '/') . '/i',
                    '<mark class="bg-primary-500">$0</mark>',
                    e($snippet)
                );

                // Replace content
                $content = $highlighted;
            } else {
                // Truncate content
                $content = mb_substr($content, 0, $length);
            }
        } else {
            // Truncate content
            $content = mb_substr($content, 0, $length);
        }

        if (mb_strlen($this->content) > $length) {
            $content = $content . '…';
        }

        return new HtmlString($content);
    }
}
