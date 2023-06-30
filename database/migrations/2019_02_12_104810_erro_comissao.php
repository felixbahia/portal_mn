<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ErroComissao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erro_comissaos', function (Blueprint $table) {
            $table->integer('id')->unique();
            $table->double('comissao_prologos');
            $table->double('comissao_portal');
            $table->string('pedido_prologos');
            $table->string('cod_representante')->nullable();
            $table->string('nfe')->nullable();
            $table->date('data')->nullable();

            $table->foreign('id')
                ->references('id')
                ->on('pedido')
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
        Schema::dropIfExists('erro_comissaos');
    }
}
