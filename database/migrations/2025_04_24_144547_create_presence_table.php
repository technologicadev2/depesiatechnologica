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
        Schema::create('presence', function (Blueprint $table) {
            $table->id(); 
            $table->date('date'); 
            $table->string('jour')->nullable(); 
            $table->string('heure')->nullable(); 
            $table->string('mois')->nullable(); 
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
        Schema::dropIfExists('presence');
    }
};
