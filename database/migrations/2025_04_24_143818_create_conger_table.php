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
        Schema::create('conger', function (Blueprint $table) {
            $table->id(); 
            $table->date('date_debut');
            $table->date('date_fin'); 
            $table->integer('num_j')->nullable(); 
            $table->integer('n_jours_reste')->nullable();
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
        Schema::dropIfExists('conger');
    }
};
