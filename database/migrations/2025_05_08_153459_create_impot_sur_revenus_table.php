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
        Schema::create('impot_sur_revenus', function (Blueprint $table) {
            $table->id();
            $table->decimal('revenu_min', 10, 2)->nullable();
            $table->decimal('revenu_max', 10, 2)->nullable(); 
            $table->decimal('taux', 5, 2)->nullable();
            $table->decimal('somme_a_deduire', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('impot_sur_revenus');
    }
};
