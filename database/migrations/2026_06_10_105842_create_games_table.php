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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('team_home')->nullable();
            $table->string('team_away')->nullable();
            $table->dateTime('date');
            $table->integer('numero')->nullable();
            $table->string("rendezvous")->nullable();
            $table->integer("score_home")->nullable();
            $table->integer("score_away")->nullable();
            $table->string("commentaire")->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
