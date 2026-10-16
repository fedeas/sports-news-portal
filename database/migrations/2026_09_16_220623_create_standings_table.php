<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Precomputed league tables, rebuilt by RecalculateStandings whenever results change.
     */
    public function up(): void
    {
        Schema::create('standings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('played')->default(0);
            $table->unsignedSmallInteger('won')->default(0);
            $table->unsignedSmallInteger('drawn')->default(0);
            $table->unsignedSmallInteger('lost')->default(0);
            $table->unsignedSmallInteger('goals_for')->default(0);
            $table->unsignedSmallInteger('goals_against')->default(0);
            $table->smallInteger('goal_difference')->default(0);
            $table->unsignedSmallInteger('points')->default(0);
            $table->unsignedTinyInteger('position')->nullable();
            $table->string('form', 10)->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['season_id', 'team_id']);
            $table->index(['season_id', 'points', 'goal_difference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standings');
    }
};
