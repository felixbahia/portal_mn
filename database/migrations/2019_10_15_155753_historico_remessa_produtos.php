<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class HistoricoRemessaProdutos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historico_remessa_produtos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('lancamento_projetos_id');
            $table->string('tipo');
            $table->string('codigo_produto');
            $table->string('pedido_remessa');
            $table->float('quantidade_enviada');
            $table->date('data_envio');
            $table->integer('lancamento_projeto_tecidos_id');
            $table->integer('lancamento_projeto_insumos_id');
            $table->integer('lancamento_projeto_faccoes_id');

            $table->integer('created_by');
            $table->integer('updated_by')->nullable();

            $table->integer('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lancamento_projetos_id')
                ->references('id')
                ->on('lancamento_projetos')
                ->onDelete('NO ACTION');
            $table->foreign('lancamento_projeto_tecidos_id')
                ->references('id')
                ->on('lancamento_projeto_tecidos')
                ->onDelete('NO ACTION');
            $table->foreign('lancamento_projeto_insumos_id')
                ->references('id')
                ->on('lancamento_projeto_insumos')
                ->onDelete('NO ACTION');
            $table->foreign('lancamento_projeto_faccoes_id')
                ->references('id')
                ->on('lancamento_projeto_faccoes')
                ->onDelete('NO ACTION');
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('deleted_by')
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
        Schema::dropIfExists('historico_remessa_produtos');
    }
}
