<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTablePremiacao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('premiacaos', function (Blueprint $table) {
            $table->increments('id');
            $table->date('data_meta');
            $table->string('codigo_vendedor');
            $table->integer('users_id');
            $table->integer('unidades_negocios_id');
            $table->float('meta_valor');
            $table->float('movimentacao_valor');
            $table->float('titulo_valor');
            $table->float('comissao');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('users_id')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('unidades_negocios_id')
                ->references('id')
                ->on('unidades_negocios')
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
        Schema::dropIfExists('premiacaos');
    }
}
