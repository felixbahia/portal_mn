<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateParametrosAprovacaoPedidoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parametros_aprovacao_pedido', function (Blueprint $table) {
            $table->increments('id');
            $table->string('estabelecimento');
            $table->integer('maximo_tempo_inativo');
            $table->integer('maximo_atraso_medio');
            $table->integer('maximo_ultimo_astraso');
            $table->integer('maximo_maior_atraso');
            $table->integer('maximo_duplicatas_vencidas');
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('parametros_aprovacao_pedido');
    }
}
