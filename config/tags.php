<?php

use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

return [

    /*
     * The given function generates a URL friendly "slug" from the tag name property before saving it.
     * Defaults to Str::slug (https://laravel.com/docs/master/helpers#method-str-slug)
     */
    'slugger' => null,

    /*
     * The fully qualified class name of the tag model.
     */
    'tag_model' => Tag::class,

    /*
     * The name of the table associated with the taggable morph relation.
     */
    'taggable' => [
        'table_name' => 'taggables',
        'morph_name' => 'taggable',

        /*
         * The fully qualified class name of the pivot model.
         */
        'class_name' => MorphPivot::class,
    ],
];
