<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('boutiques', function (Blueprint $table) {
            $table->string('telephone')->nullable()->after('nom');
            $table->string('details_adresse')->nullable()->after('adresse');
        });

        // Migrate existing contacts to the new telephone column
        $boutiques = DB::table('boutiques')->get();
        foreach ($boutiques as $boutique) {
            $contacts = json_decode($boutique->contacts, true);
            $telephone = is_array($contacts) && count($contacts) > 0 ? $contacts[0] : null;
            if (!$telephone) {
                $telephone = 'tmp_' . uniqid();
            }
            DB::table('boutiques')->where('id', $boutique->id)->update(['telephone' => $telephone]);
        }

        Schema::table('boutiques', function (Blueprint $table) {
            $table->string('telephone')->nullable(false)->unique()->change();
            $table->unique('nom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boutiques', function (Blueprint $table) {
            $table->dropUnique(['nom']);
            $table->dropUnique(['telephone']);
            $table->dropColumn(['telephone', 'details_adresse']);
        });
    }
};
