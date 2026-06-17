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
        Schema::create('affectations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipement_id')->constrained('equipements')->onDelete('cascade');
            $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
            $table->foreignId('affecte_par')->constrained('users')->onDelete('cascade');
            $table->date('date_affectation');
            $table->string('photo_remise', 500);
            $table->date('date_retour')->nullable();
            $table->string('etat_retour', 100)->nullable();
            $table->string('photo_retour', 500)->nullable();
            $table->text('observations')->nullable();
            $table->enum('statut', ['en_cours', 'confirmee', 'retour_en_attente', 'retourne', 'renouvele'])->default('en_cours');
            $table->text('motif_rejet')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affectations');
    }
};
