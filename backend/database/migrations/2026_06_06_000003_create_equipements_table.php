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
        Schema::create('equipements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categorie_id')->constrained('categories')->onDelete('cascade');
            $table->string('reference', 100)->unique();
            $table->string('numero_serie', 100)->unique();
            $table->string('imei', 20)->nullable()->unique();
            $table->string('code_inventaire', 100)->unique();
            $table->string('marque', 100);
            $table->string('modele', 150);
            $table->string('fournisseur', 150)->nullable();
            $table->date('date_acquisition')->nullable();
            $table->decimal('prix_achat', 10, 2)->nullable();
            $table->date('garantie_fin')->nullable();
            $table->enum('etat', ['neuf', 'en_service', 'en_panne', 'en_maintenance', 'en_attente_sinistre', 'reforme', 'perdu'])->default('neuf');
            $table->string('localisation', 200)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipements');
    }
};
