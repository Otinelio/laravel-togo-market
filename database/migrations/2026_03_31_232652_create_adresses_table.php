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
        Schema::create('villes', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->timestamps();
        });

        Schema::create('quartiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ville_id')->constrained('villes')->cascadeOnDelete();
            $table->string('nom');
            $table->timestamps();
        });

        Schema::create('adresses', function (Blueprint $table) {
            $table->id();
            $table->morphs('addressable');
            $table->foreignId('quartier_id')->nullable()->constrained('quartiers')->cascadeOnDelete();
            $table->text('details')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adresses');
        Schema::dropIfExists('quartiers');
        Schema::dropIfExists('villes');
    }
};
