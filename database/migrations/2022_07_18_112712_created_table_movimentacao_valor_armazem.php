<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableMovimentacaoValorArmazem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movimentacao_valor_armazem', function (Blueprint $table) {
            $table->increments('id');
            $table->date('data_movimentacao');
            $table->float('saldo_anterior')->nullable();
            $table->float('saldo_atual')->nullable();
            $table->float('entrada')->nullable();
            $table->float('saida')->nullable();
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
        Schema::dropIfExists('movimentacao_valor_armazem');
    }
}
