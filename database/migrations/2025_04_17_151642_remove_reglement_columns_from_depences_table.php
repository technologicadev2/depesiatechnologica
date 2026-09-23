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
            $table->dropForeign(['reglement_depense_id']);
            $table->dropColumn(['reglement_depense_id', 'reglement_depense']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('depences', function (Blueprint $table) {
            // Recréer les colonnes et la clé étrangère en cas de rollback
            $table->foreignId('reglement_id')->nullable()->constrained('type_reglement')->onDelete('restrict');
            $table->string('reglement_depense', 50)->nullable();
        });
    }
};