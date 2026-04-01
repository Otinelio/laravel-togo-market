<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boutique_id')->constrained('boutiques')->onDelete('cascade');
            $table->foreignId('categorie_id')->constrained('categories')->onDelete('cascade');
            $table->string('titre');
            $table->text('description');
            $table->decimal('prix', 10, 2);
            $table->enum('etat', ['Neuf', 'Occasion']);
            $table->string('localisation')->nullable();
            $table->json('variations_possibles')->nullable();
            $table->enum('statut', ['actif', 'reserve', 'vendu'])->default('actif');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
