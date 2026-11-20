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
        Schema::create('licensees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('firstname');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('licence')->nullable();
            $table->timestamps();
        });

        Schema::table('members', function (Blueprint $table) {            
            $table->foreignId('licensee_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licensees');
    }
};
