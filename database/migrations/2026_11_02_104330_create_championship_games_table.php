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
        Schema::create('championship_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matchday_id')->nullable()
                ->constrained('championship_gamedays')
                ->nullOnDelete();
            $table->foreignId('home_team_id')->constrained('championship_teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('championship_teams')->cascadeOnDelete();
            $table->dateTime('scheduled_at')->nullable();
            $table->unsignedSmallInteger('home_score')->nullable();
            $table->unsignedSmallInteger('away_score')->nullable();
            $table->string('status')->default('scheduled');
            $table->string('location')->nullable();
            $table->string('source')->default('manual');
            $table->string('external_id')->nullable();
            $table->timestamps();

            $table->index(['matchday_id', 'scheduled_at']);
            $table->unique(['source', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('championship_games');
    }
};
