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
        Schema::create('championship_gamedays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('championship_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('number');
            $table->string('label')->nullable();
            $table->date('date')->nullable();
            $table->string('source')->default('manual');
            $table->string('external_id')->nullable();
            $table->timestamps();

            $table->unique(['championship_id', 'number']);
            $table->unique(['championship_id', 'source', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('championship_gamedays');
    }
};
