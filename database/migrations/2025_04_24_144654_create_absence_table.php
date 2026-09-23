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
        Schema::create('absence', function (Blueprint $table) {
            $table->id(); 
            $table->text('description')->nullable(); 
            $table->text('piece_jointe')->nullable(); 
            $table->boolean('justification')->nullable(); 
            $table->date('date_debut'); 
            $table->date('date_fin'); 
            $table->integer('nbre_jours')->nullable(); 
            $table->foreignId('salarie_id')->constrained('salaries')->onDelete('cascade'); 
            $table->timestamps(); 
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
        Schema::dropIfExists('absence');
    }
};
