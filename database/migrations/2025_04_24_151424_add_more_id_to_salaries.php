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
            $table->foreignId('id_conge')->nullable()->after('date_demission')->constrained('conger')->onDelete('set null'); // References conger table
            $table->foreignId('id_absence')->nullable()->after('id_conge')->constrained('absence')->onDelete('set null'); // References absence table
            $table->foreignId('id_presence')->nullable()->after('id_absence')->constrained('presence')->onDelete('set null'); // References presence table
            $table->foreignId('id_sal_projet')->nullable()->after('id_presence')->constrained('sal_projet')->onDelete('set null'); // References sal_projet table
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->dropForeign(['id_conge']); // Drop foreign key constraint
            $table->dropForeign(['id_absence']);
            $table->dropForeign(['id_presence']);
            $table->dropForeign(['id_sal_projet']);
        });
    }
};
