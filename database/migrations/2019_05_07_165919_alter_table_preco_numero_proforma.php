<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePrecoNumeroProforma extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('precos', function ($table) {
            $table->dropColumn('ultima_compra_dolar');

            $table->string('numero_proforma')->nullable();
            $table->date('previsao_entrega')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('precos', function ($table) {
            $table->float('ultima_compra_dolar')->nullable();

            $table->dropColumn('numero_proforma');
            $table->dropColumn('previsao_entrega');
        });
    }
}
