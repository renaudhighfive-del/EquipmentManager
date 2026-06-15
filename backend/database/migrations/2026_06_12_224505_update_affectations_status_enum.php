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
        Schema::table('affectations', function (Blueprint $table) {
            $table->enum('statut', ['en_cours', 'confirmee', 'retour_en_attente', 'retourne', 'renouvele'])->default('en_cours')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affectations', function (Blueprint $table) {
            $table->enum('statut', ['en_cours', 'confirmee', 'valider', 'retourne', 'renouvele'])->default('en_cours')->change();
        });
    }
};
