<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('type_reglement_depence', 'type_reglement');
    }

    public function down(): void
    {
        Schema::rename('type_reglement', 'type_reglement_depence');
    }
};