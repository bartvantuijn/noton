<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('categories'))->pluck('name');

        Schema::table('categories', function (Blueprint $table) use ($indexes) {
            if ($indexes->contains('categories_slug_unique')) {
                $table->dropUnique(['slug']);
            }

            if (! $indexes->contains('categories_parent_id_slug_unique')) {
                $table->unique(['parent_id', 'slug']);
            }
        });
    }

    public function down(): void
    {
        //
    }
};
