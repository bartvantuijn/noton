<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('subtitle')->nullable()->after('name');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('subtitle')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('subtitle');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('subtitle');
        });
    }
};
