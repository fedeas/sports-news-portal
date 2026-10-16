<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('slug_history', function (Blueprint $table) {
            $table->id();
            $table->string('sluggable_type', 60);
            $table->unsignedBigInteger('sluggable_id');
            $table->string('slug', 220);
            $table->timestamp('created_at')->nullable();

            $table->unique(['sluggable_type', 'slug']);
            $table->index(['sluggable_type', 'sluggable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slug_history');
    }
};
