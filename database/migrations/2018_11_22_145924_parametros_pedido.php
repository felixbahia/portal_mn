<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ParametrosPedido extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parametros_pedido', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('estabelecimento')->unique();
            $table->float('valor_minimo_porcentagem');
            $table->float('valor_maximo_porcentagem');
            $table->integer("created_by");
            $table->integer("modified_by")->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');

            $table->foreign('modified_by')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('parametros_pedido');
    }
}
