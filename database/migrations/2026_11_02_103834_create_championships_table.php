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
        Schema::create('championships', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('season', 20);
            $table->string('category')->nullable();
            $table->string('division')->nullable();
            $table->string('pool')->nullable();
            $table->string('status')->default('active');
            $table->string('source')->default('manual');
            $table->string('external_id')->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('championships');
    }
};
