<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ImportacaoNotasVenda extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notas_vendas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('estabelecimento', 2);
            $table->string('mes_data_entrada', 2);
            $table->string('ano_data_entrada', 4);
            $table->string('codigo_produto');
            $table->float('quantidade');
            $table->float('valor');
            $table->float('valor_tabela')->nullable();
            $table->timestamps();

            $table->index(['codigo_produto', 'estabelecimento']);
            $table->index(['codigo_produto', 'estabelecimento', 'mes_data_entrada', 'ano_data_entrada']);
            $table->index(['codigo_produto', 'mes_data_entrada', 'ano_data_entrada']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notas_vendas');
    }
}
