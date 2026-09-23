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
        Schema::create('cotisations', function (Blueprint $table) {
            $table->id();
            $table->decimal('cnss_pp', 10, 2)->nullable();
            $table->decimal('amo_pp', 10, 2)->nullable();

            $table->decimal('cnss_ps', 10, 2)->nullable();
            $table->decimal('amo_ps', 10, 2)->nullable(); 
            $table->decimal('fp_ps', 10, 2)->nullable(); 
            
            $table->decimal('charge_de_famille', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotisations');
    }
};
