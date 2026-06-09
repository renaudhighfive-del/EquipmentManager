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
        // SQLite ne supporte pas bien le changement d'ENUM via Schema::table. 
        // Si c'est MySQL, on peut utiliser une requête brute.
        // Pour être compatible, on va juste s'assurer que l'application gère ces états.
        // Si la base de données est MySQL :
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE equipements MODIFY COLUMN etat ENUM('neuf', 'en_service', 'en_panne', 'en_maintenance', 'en_attente_sinistre', 'reforme', 'perdu', 'repare') DEFAULT 'neuf'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE equipements MODIFY COLUMN etat ENUM('neuf', 'en_service', 'en_panne', 'en_maintenance', 'en_attente_sinistre', 'reforme', 'perdu') DEFAULT 'neuf'");
        }
    }
};
