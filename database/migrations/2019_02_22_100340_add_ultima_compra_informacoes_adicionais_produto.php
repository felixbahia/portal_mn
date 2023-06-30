<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUltimaCompraInformacoesAdicionaisProduto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('informacao_adicional_produtos', function ($table) {
            $table->float('valor_ultima_compra')->default(0)->nullable();
            $table->dateTime('data_ultima_compra')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('informacao_adicional_produtos', function ($table) {
            $table->dropColumn('valor_ultima_compra');
            $table->dropColumn('data_ultima_compra');
        });
    }
}
