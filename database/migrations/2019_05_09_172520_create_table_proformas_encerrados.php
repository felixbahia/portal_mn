<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableProformasEncerrados extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('proforma_encerrados', function (Blueprint $table) {
            $table->string('codigo_produto');
            $table->date('previsao_entrega');
            $table->string('numero_proforma');
            $table->float('valor_venda_us');
            $table->float('valor_unitario_fob');
            $table->float('valor_unitario_real');
            $table->date('data_proforma');
        });

        Schema::table('precos', function ($table) {
            $table->float('ultima_compra_dolar')->nullable();
            $table->float('valor_compra')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('proforma_encerrados');
        
        Schema::table('precos', function ($table) {        
            $table->dropColumn('ultima_compra_dolar')->nullable();
            $table->dropColumn('valor_compra')->nullable();
        });


    }
}
