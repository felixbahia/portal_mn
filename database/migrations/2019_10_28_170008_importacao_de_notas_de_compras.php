<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ImportacaoDeNotasDeCompras extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notas_entradas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('estabelecimento', 2);
            $table->date('data_entrada');
            $table->string('codigo_produto');
            $table->string('nota')->nullable();
            $table->string('nota_serie')->nullable();
            $table->string('pedido')->nullable();
            $table->string('proforma')->nullable();
            $table->string('forncedor_codigo')->nullable();
            $table->string('forncedor_cpf_cnpj')->nullable();
            $table->string('unidade')->nullable();
            $table->float('quantidade');
            $table->float('preco_real');
            $table->float('preco_dolar');
            $table->string('origem');
            $table->timestamps();

            $table->index(['codigo_produto', 'estabelecimento']);
            $table->index(['codigo_produto', 'estabelecimento', 'data_entrada', 'pedido', 'proforma', 'forncedor_cpf_cnpj']);
            $table->index(['codigo_produto','data_entrada', 'pedido', 'proforma', 'forncedor_cpf_cnpj']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notas_entradas');
    }
}
