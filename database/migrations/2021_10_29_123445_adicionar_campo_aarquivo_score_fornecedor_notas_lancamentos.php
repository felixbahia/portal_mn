<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdicionarCampoAarquivoScoreFornecedorNotasLancamentos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('score_fornecedores_notas_lancamentos_documentos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('descricao',60);
            $table->string('caminho')->nullable();
            $table->integer('score_fornecedores_notas_lancamentos_id');
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('score_fornecedores_notas_lancamentos_id')
            ->references('id')
            ->on('score_fornecedores_notas_lancamentos')
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
        Schema::dropIfExists('score_fornecedores_notas_lancamentos_documentos');
    }
}
