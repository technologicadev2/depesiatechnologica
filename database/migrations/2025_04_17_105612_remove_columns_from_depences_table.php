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
        Schema::table('depences', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère avant de supprimer la colonne
            $table->dropForeign(['user_id']);
            // Supprimer les colonnes user_id et user
            $table->dropColumn(['user_id', 'user']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('depences', function (Blueprint $table) {
            // Restaurer les colonnes en cas de rollback
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('user')->nullable();
        });
    }
};