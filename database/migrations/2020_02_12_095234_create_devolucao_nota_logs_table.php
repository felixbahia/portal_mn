<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDevolucaoNotaLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devolucao_nota_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('devolucao_nota_id');
            $table->string('acao');
            $table->string('mensagem');
            $table->integer('usuario');
            $table->uuid('nota_id_antigo')->nullable();
            $table->string('nome_contato_antigo')->nullable();
            $table->string('telefone_contato_antigo')->nullable();
            $table->string('email_contato_antigo')->nullable();
            $table->integer('motivo_antigo')->nullable();
            $table->string('status_antigo')->nullable();
            $table->float('valor_antigo')->nullable();
            $table->boolean('valor_parcial_antigo')->nullable();
            $table->uuid('nota_id_novo')->nullable();
            $table->string('nome_contato_novo')->nullable();
            $table->string('telefone_contato_novo')->nullable();
            $table->string('email_contato_novo')->nullable();
            $table->integer('motivo_novo')->nullable();
            $table->string('status_novo')->nullable();
            $table->float('valor_novo')->nullable();
            $table->boolean('valor_parcial_novo')->nullable();
            $table->string('responsabilidade_frete')->nullable();      
            $table->string('laudo_imagem')->nullable();      
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('devolucao_nota_id')
                ->references('id')
                ->on('devolucoes_notas')
                ->onDelete('NO ACTION');

            $table->foreign('usuario')
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
        Schema::dropIfExists('devolucao_nota_logs');
    }
}
