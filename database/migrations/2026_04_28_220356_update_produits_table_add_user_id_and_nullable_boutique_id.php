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
        Schema::table('produits', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Migrate existing data
        \Illuminate\Support\Facades\DB::table('produits')
            ->join('boutiques', 'produits.boutique_id', '=', 'boutiques.id')
            ->update(['produits.user_id' => \Illuminate\Support\Facades\DB::raw('boutiques.user_id')]);

        Schema::table('produits', function (Blueprint $table) {
            // Make boutique_id nullable
            $table->unsignedBigInteger('boutique_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            
            $table->unsignedBigInteger('boutique_id')->nullable(false)->change();
        });
    }
};
