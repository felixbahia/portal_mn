<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableTituloInformacaoGrupos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('titulo_informacao_grupos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('estabelecimento_codigo', 2);
            $table->integer('mes_emissao');
            $table->integer('ano_emissao');
            $table->integer('periodo_vencimento');
            $table->integer('quantidade');
            $table->float('valor');
            $table->float('valor_pre_pago')->nullable();
            $table->string('representante_codigo');
            $table->string('produto_grupo');
            $table->string('produto_codigo');
            $table->string('produto_descricao');
            $table->timestamps();
            $table->softDeletes();
            $table->string('equipe')->nullable();
            $table->integer('unidades_negocios_id')->nullable();

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
        Schema::dropIfExists('titulo_informacao_grupos');
    }
}
