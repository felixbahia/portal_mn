<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableTempDiferencaEstoque extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('temp_produto_disponivel', function(Blueprint $table){
            $table->string("codprd", 18);
            $table->string("descricao", 40);
            $table->string("marca", 15);
            $table->string("linha", 15);
            $table->string("grupo", 15);
            $table->string("subgrupo", 15);
            $table->double("estoque_disponivel_00", 10, 2);
            $table->double("estoque_disponivel_01", 10, 2);
            $table->double("estoque_disponivel_02", 10, 2);
            $table->double("estoque_disponivel_03", 10, 2);
            $table->double("estoque_disponivel_04", 10, 2);
            $table->double("estoque_disponivel_05", 10, 2);
            $table->primary('codprd');
        });
        Schema::create('temp_produto_pecapeca', function(Blueprint $table){
            $table->string("codprd", 18);
            $table->char("empresa", 2);
            $table->double("quantidade", 10, 2);
            $table->primary(['codprd', 'empresa']);
        });
        Schema::create('temp_produto_diferenca', function(Blueprint $table){
            $table->string("codprd", 18);
            $table->char("empresa", 2);
            $table->string("descricao", 40);
            $table->string("marca", 15);
            $table->string("linha", 15);
            $table->string("grupo", 15);
            $table->string("subgrupo", 15);
            $table->double("estoque_disponivel", 10, 2);
            $table->double("estoque_pecapeca", 10, 2);
            $table->primary(['codprd', 'empresa']);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('temp_produto_disponivel');
        Schema::drop('temp_produto_pecapeca');
        Schema::drop('temp_produto_diferenca');
    }
}
