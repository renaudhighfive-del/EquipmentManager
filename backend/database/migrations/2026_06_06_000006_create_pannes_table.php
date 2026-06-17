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
        Schema::create('pannes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipement_id')->constrained('equipements')->onDelete('cascade');
            $table->foreignId('declare_par')->constrained('users')->onDelete('cascade');
            $table->foreignId('valide_par')->nullable()->constrained('users')->onDelete('set null');
            $table->date('date_declaration');
            $table->dateTime('date_validation')->nullable();
            $table->text('description');
            $table->enum('gravite', ['faible', 'moyenne', 'critique']);
            $table->enum('statut', ['declaree', 'en_cours', 'en_maintenance', 'resolue', 'irrecuperable'])->default('declaree');
            $table->json('photos')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pannes');
    }
};
