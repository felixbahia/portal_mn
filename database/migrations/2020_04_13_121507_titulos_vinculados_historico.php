<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TitulosVinculadosHistorico extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historico_financeiro_cliente_titulos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('historico_financeiro_clientes_id');
            
            $table->string('cliente');
            $table->uuid('titulo_id');
            $table->string('titulo_estabelecimento');
            $table->string('titulo_numero');
            $table->float('titulo_valor_original');
            $table->float('titulo_valor_saldo');
            $table->date('titulo_vencimento');
            $table->date('titulo_emissao');

            $table->integer('created_by');
            $table->timestamps();

            $table->foreign('historico_financeiro_clientes_id')
                ->references('id')
                ->on('historico_financeiro_clientes')
                ->onDelete('NO ACTION');

            $table->foreign('created_by')
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
        Schema::dropIfExists('historico_financeiro_cliente_titulos');
    }
}
