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
        Schema::create('championship_standing_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('standing_snapshot_id')
                ->constrained('championship_standing_snapshots')
                ->cascadeOnDelete();
            $table->foreignId('championship_team_id')
                ->constrained('championship_teams')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('rank');
            $table->unsignedSmallInteger('played')->default(0);
            $table->unsignedSmallInteger('won')->default(0);
            $table->unsignedSmallInteger('lost')->default(0);
            $table->unsignedSmallInteger('points_for')->default(0);
            $table->unsignedSmallInteger('points_against')->default(0);
            $table->integer('points')->default(0);
            $table->integer('penalty_points')->default(0);
            $table->timestamps();

            $table->unique(['standing_snapshot_id', 'championship_team_id']);
            $table->unique(['standing_snapshot_id', 'rank']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('championship_standing_entries');
    }
};
