<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Tags\HasTags;

class Post extends Model
{
    use HasFactory, HasTags;

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
