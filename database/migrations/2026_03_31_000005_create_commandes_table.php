<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('boutique_id')->constrained('boutiques')->onDelete('cascade');
            $table->decimal('prix_total', 10, 2);
            $table->enum('methode_livraison', ['retrait_boutique', 'livraison_vendeur']);
            $table->enum('methode_paiement', ['especes', 'transfert_mobile']);
            $table->enum('statut', ['en_attente', 'confirmee_par_vendeur', 'annulee_par_client', 'refusee_par_vendeur', 'terminee'])->default('en_attente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
