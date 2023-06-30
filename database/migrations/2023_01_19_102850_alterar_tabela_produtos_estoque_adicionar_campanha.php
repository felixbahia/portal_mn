<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarTabelaProdutosEstoqueAdicionarCampanha extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('produto_especificacaos', function (Blueprint $table){
            $table->dropColumn('campanha_id');
        });

        Schema::table('produtos_estoques', function (Blueprint $table){
            $table->integer('campanha_id')->nullable();

            $table->foreign('campanha_id')
            ->references('id')
            ->on('campanhas')
            ->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('produtos_estoques', function (Blueprint $table){
            $table->dropColumn('campanha_id');
        });
    }
}
