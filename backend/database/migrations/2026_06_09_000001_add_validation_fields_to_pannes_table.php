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
        Schema::table('pannes', function (Blueprint $table) {
            $table->foreignId('valide_par')->nullable()->constrained('users')->onDelete('set null')->after('declare_par');
            $table->dateTime('date_validation')->nullable()->after('date_declaration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pannes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('valide_par');
            $table->dropColumn('date_validation');
        });
    }
};
