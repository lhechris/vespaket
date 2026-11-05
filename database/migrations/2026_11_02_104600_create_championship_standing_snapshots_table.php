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
        Schema::create('championship_standing_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('championship_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matchday_id')->nullable()
                ->constrained('championship_gamedays')
                ->nullOnDelete();
            $table->timestamp('captured_at');
            $table->string('source')->default('manual');
            $table->string('external_id')->nullable();
            $table->timestamps();

            $table->index(['championship_id', 'captured_at']);
            $table->unique(['source', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('championship_standing_snapshots');
    }
};
