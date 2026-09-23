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
        Schema::create('corbeille_salaries', function (Blueprint $table) {
            $table->id();
            // Copie de tous les champs de la table salaries
            $table->string('nom');
            $table->string('prenom')->nullable();
            $table->string('cin')->nullable();
            $table->string('phone')->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('adresse')->nullable();
            $table->string('situation_familiale')->nullable();
            $table->integer('nombre_enfant')->default(0)->nullable();
            $table->string('n_matricule_cnss')->nullable();
            $table->string('n_matricule_entreprise')->nullable();
            $table->unsignedBigInteger('fonction_id')->nullable();
            $table->unsignedBigInteger('reglement_id')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            // Dans la migration pour corbeille_salaries
$table->unsignedBigInteger('created_by')->nullable();
$table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('corbeille_salaries');
    }
};
