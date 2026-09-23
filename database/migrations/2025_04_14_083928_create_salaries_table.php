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
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom')->nullable();
            $table->string('matricule')->nullable()->unique();
            $table->string('cin')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('n_matricule_cnss')->nullable()->unique();
            $table->string('n_matricule_entreprise')->nullable()->unique();
            $table->string('adresse')->nullable();
            $table->string('situation_familiale')->nullable();
            $table->integer('nombre_enfant')->nullable();
            $table->foreignId('fonction_id')->nullable()->constrained('fonctions'); 
            $table->string('fonction');
            $table->foreignId('reglement_salarie_id')->nullable()->constrained('type_reglement_salarie'); 
            $table->string('reglement_salarie');
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
