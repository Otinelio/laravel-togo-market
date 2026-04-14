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
        // Set everything that is currently null to 0 first!
        \Illuminate\Support\Facades\DB::table('boutiques')
            ->whereNull('note_moyenne')
            ->update(['note_moyenne' => 0]);

        // Add default value to the existing column, keep it nullable to prevent strictly breaking existing schemas if anything else expects nullable
        Schema::table('boutiques', function (Blueprint $table) {
            $table->decimal('note_moyenne', 3, 2)->nullable()->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boutiques', function (Blueprint $table) {
            $table->decimal('note_moyenne', 3, 2)->nullable()->default(null)->change();
        });
    }
};
