<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTablePrecoAlteracaoPorDePara extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('preco_alteracao_por_de_para', function (Blueprint $table) {
            $table->increments('id');
            $table->string('produto_codigo_de');
            $table->float('preco_venda_de');
            $table->string('produto_codigo_para');
            $table->float('preco_venda_para');
            $table->float('preco_venda_novo_para');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('preco_alteracao_por_de_para');
    }
}
