<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class GiroDeEstoque extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('giro_de_estoques', function (Blueprint $table) {
            $table->increments('id');
            $table->string('codigo_produto');
            $table->string('descricao');
            $table->string('grupo');
            $table->string('linha');
            $table->string('marca');
            $table->string('origem');
            $table->float('estoque');
            $table->date('ultima_compra')->nullable();
            $table->integer('compras_aberto');
            $table->date('previsao_chegada')->nullable();
            $table->float('consumo_01_mes');
            $table->float('consumo_02_mes');
            $table->float('consumo_03_mes');
            $table->float('consumo_04_mes');
            $table->float('consumo_05_mes');
            $table->float('consumo_06_mes');
            $table->float('consumo_07_mes');
            $table->float('consumo_08_mes');
            $table->float('consumo_09_mes');
            $table->float('consumo_10_mes');
            $table->float('consumo_11_mes');
            $table->float('consumo_12_mes');
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
        Schema::dropIfExists('giro_de_estoques');
    }
}
