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
        Schema::create('sal_projet', function (Blueprint $table) {
            $table->id(); 
            $table->foreignId('salarie_id')->constrained('salaries')->onDelete('cascade'); 
            $table->foreignId('projet_id')->constrained('projet')->onDelete('cascade'); 
            $table->date('date_integration')->nullable(); 
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sal_projet');
    }
};
