<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InventarioProdutosEstoquesNasajon extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventario_produtos_estoques_nasajon', function (Blueprint $table) {
            $table->string('estabelecimento');
            $table->string('codigo_produto');
            $table->float('saldo', 8, 3);
            $table->dateTime('data_atulalizacao');
            $table->primary(['estabelecimento', 'codigo_produto']);
            $table->index(['estabelecimento', 'codigo_produto']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventario_produtos_estoques_nasajon');
    }
}
