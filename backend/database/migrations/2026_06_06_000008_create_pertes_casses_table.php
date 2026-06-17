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
        Schema::create('pertes_casses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipement_id')->constrained('equipements')->onDelete('cascade');
            $table->foreignId('declare_par')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['perte', 'vol', 'casse']);
            $table->date('date_declaration');
            $table->text('description');
            $table->json('photos')->nullable();
            $table->enum('statut', ['en_attente_validation', 'validee', 'cloturee', 'rejetee'])->default('en_attente_validation');
            $table->foreignId('valide_par')->nullable()->constrained('users')->onDelete('set null');
            $table->date('date_validation')->nullable();
            $table->text('motif_rejet')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pertes_casses');
    }
};
