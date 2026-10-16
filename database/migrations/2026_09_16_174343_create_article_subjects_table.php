<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Links articles to what they are about (teams and leagues now, players later).
     */
    public function up(): void
    {
        Schema::create('article_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->string('subject_type', 60);
            $table->unsignedBigInteger('subject_id');
            $table->timestamps();

            $table->unique(['article_id', 'subject_type', 'subject_id']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_subjects');
    }
};
