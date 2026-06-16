<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('pinnable');
            $table->timestamps();

            $table->unique(['user_id', 'pinnable_type', 'pinnable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pins');
    }
};
