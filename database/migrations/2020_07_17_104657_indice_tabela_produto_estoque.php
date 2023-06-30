<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class IndiceTabelaProdutoEstoque extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('produtos_estoques', function ($table) {
            $table->index(array('estabelecimento', 'codigo_produto', 'compras', 'compras_aberto', 'estoque', 'custo', 'saldo_fiscal', 'saldo_em_terceiros', 'saldo_de_terceiros', 'saldo_armazem'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
