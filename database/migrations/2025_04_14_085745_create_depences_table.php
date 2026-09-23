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
        Schema::create('depences', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->float('montant')->nullable();
            $table->text('description')->nullable();
            $table->date('date')->nullable();
            $table->text('epreuve')->nullable(); 
            $table->string('image')->nullable();
            $table->foreignId('reglement_depense_id')->nullable()->constrained('type_reglement_depence')->onDelete('restrict');
            $table->string('reglement_depense')->nullable();
            $table->foreignId('nature_id')->nullable()->constrained('nature_depences');
            $table->string('nature_depense')->nullable();
            $table->foreignId('salarie_id')->nullable()->constrained('salaries');
            $table->string('salarie')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('user')->nullable();
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
        Schema::dropIfExists('depences');
    }
};
