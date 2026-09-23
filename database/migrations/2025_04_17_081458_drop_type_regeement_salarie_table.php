<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('type_reglement_salarie');
        Schema::enableForeignKeyConstraints();
    }

    public function down()
    {
        // Optionnel : recréer la table si nécessaire
    }
};