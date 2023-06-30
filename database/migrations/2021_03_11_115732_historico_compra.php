<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class HistoricoCompra extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historico_compras', function (Blueprint $table) {
            $table->increments('id');
            $table->string('codigo_produto');
            $table->string('numero_pedido');
            $table->float('quantidade');
            $table->date('data_alteracao');
            $table->date('previsao_entrega')->nullable();
            $table->string('status');
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('historico_compras');
    }
}
