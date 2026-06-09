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
        Schema::table('maintenances', function (Blueprint $table) {
            $table->json('photos_retour')->nullable()->after('date_fin');
        });

        Schema::table('pertes_casses', function (Blueprint $table) {
            $table->json('photos')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropColumn('photos_retour');
        });

        Schema::table('pertes_casses', function (Blueprint $table) {
            $table->dropColumn('photos');
        });
    }
};
