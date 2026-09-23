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
        Schema::create('corbeille_depeses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->double('montant')->nullable();
            $table->text('description')->nullable();
            $table->date('date')->nullable();
            $table->text('epreuve')->nullable();
            $table->unsignedBigInteger('nature_id')->nullable();
            $table->string('nature_depense')->nullable();
            $table->unsignedBigInteger('salarie_id')->nullable();
            $table->string('salarie')->nullable();
            $table->timestamps();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->unsignedBigInteger('reglement_id')->nullable();
            $table->string('reglement_depense', 50)->nullable();

            // Index pour les colonnes de relation
            $table->index('nature_id');
            $table->index('salarie_id');
            $table->index('reglement_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corbeille_depeses');
    }
};
