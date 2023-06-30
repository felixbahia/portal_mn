<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class HistoricoFinanceiroCliente extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historico_financeiro_clientes', function (Blueprint $table) {
            $table->increments('id');

            $table->string('cliente_raiz_cnpj');
            
            $table->string('contato');
            $table->integer('retorno_possivel_id');
            $table->integer('titulos_em_aberto_quantidade');
            $table->float('titulos_em_aberto_valor');
            $table->date('titulos_em_aberto_maior_atraso');

            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('updated_by')
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
        Schema::dropIfExists('historico_financeiro_clientes');
    }
}
