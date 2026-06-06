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
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipement_id')->constrained('equipements')->onDelete('cascade');
            $table->foreignId('panne_id')->nullable()->constrained('pannes')->onDelete('set null');
            $table->enum('type', ['preventive', 'corrective']);
            $table->string('technicien', 150);
            $table->foreignId('responsable_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('diagnostic')->nullable();
            $table->text('actions_effectuees')->nullable();
            $table->decimal('cout', 10, 2)->nullable();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
