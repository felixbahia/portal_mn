<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriacaoProdutosCusto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('produtos_custos', function (Blueprint $table) {
            $table->string('estabelecimento', 2);
            $table->string('produto_codigo');
            $table->float('custo_medio_contabil');
            $table->float('custo_medio_gerencial');
            $table->date('data_atualizacao');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('produtos_custos');
    }

}
