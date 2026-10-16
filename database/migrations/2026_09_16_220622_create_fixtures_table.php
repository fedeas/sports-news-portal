<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fixtures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->restrictOnDelete();
            // No explicit ON DELETE action: MySQL forbids referential actions on columns used in a
            // CHECK constraint. The default already prevents deleting a team that has fixtures.
            $table->foreignId('home_team_id')->constrained('teams');
            $table->foreignId('away_team_id')->constrained('teams');
            $table->dateTime('kickoff_at');
            $table->string('status', 20)->default('scheduled');
            $table->unsignedTinyInteger('score_home')->nullable();
            $table->unsignedTinyInteger('score_away')->nullable();
            $table->unsignedSmallInteger('minute')->nullable();
            $table->string('round', 30)->nullable();
            $table->string('venue', 150)->nullable();
            $table->string('external_source', 30)->nullable();
            $table->string('external_id', 60)->nullable();
            $table->timestamps();

            $table->index(['status', 'kickoff_at']);
            $table->index(['season_id', 'kickoff_at']);
            $table->unique(['external_source', 'external_id']);
        });

        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE fixtures ADD CONSTRAINT fixtures_teams_differ CHECK (home_team_id <> away_team_id)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixtures');
    }
};
