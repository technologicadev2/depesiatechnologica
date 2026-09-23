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
        Schema::table('salaries', function (Blueprint $table) {
            $table->string('photo')->nullable(); 
            $table->double('salaire_base')->nullable()->after('photo'); 
            $table->enum('type_travail', ['permanent', 'occasionnel'])->nullable()->after('salaire_base'); 
            $table->string('code_qr')->nullable()->after('type_travail'); 
            $table->string('rib')->nullable()->after('code_qr'); 
            $table->string('anciennete')->nullable()->after('rib'); 
            $table->boolean('quitte_post')->default(0)->after('anciennete'); 
            $table->date('date_demission')->nullable()->after('quitte_post'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {Schema::table('salaries', function (Blueprint $table) {
        $table->dropColumn([
            'photo',
            'salaire_base',
            'type_travail',
            'code_qr',
            'rib',
            'anciennete',
            'quitte_post',
            'date_demission',
        ]);
    });
    }
};
