<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Tag;

class CategoryObserver
{
    /**
     * Handle the Category "saved" event.
     */
    public function saved(Category $category): void
    {
        Tag::all()->each(function (Tag $tag): void {
            if (! $tag->posts()->exists() && ! $tag->categories()->exists()) {
                $tag->delete();
            }
        });
    }

    /**
     * Handle the Category "created" event.
     */
    public function created(Category $category): void
    {
        //
    }

    /**
     * Handle the Category "updated" event.
     */
    public function updated(Category $category): void
    {
        //
    }

    /**
     * Handle the Category "deleted" event.
     */
    public function deleted(Category $category): void
    {
        //
    }

    /**
     * Handle the Category "restored" event.
     */
    public function restored(Category $category): void
    {
        //
    }

    /**
     * Handle the Category "force deleted" event.
     */
    public function forceDeleted(Category $category): void
    {
        //
    }
}
