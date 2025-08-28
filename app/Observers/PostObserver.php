<?php

namespace App\Observers;

use App\Models\Post;
use App\Models\Tag;

class PostObserver
{
    /**
     * Handle the Post "saved" event.
     */
    public function saved(Post $post): void
    {
        Tag::all()->each(function (Tag $tag) {
            if (! $tag->posts()->exists()) {
                $tag->delete();
            }
        });
    }

    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post): void
    {
        //
    }

    /**
     * Handle the Post "updated" event.
     */
    public function updated(Post $post): void
    {
        //
    }

    /**
     * Handle the Post "deleted" event.
     */
    public function deleted(Post $post): void
    {
        //
    }

    /**
     * Handle the Post "restored" event.
     */
    public function restored(Post $post): void
    {
        //
    }

    /**
     * Handle the Post "force deleted" event.
     */
    public function forceDeleted(Post $post): void
    {
        //
    }
}
