<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaDevolucaoDocumentos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devolucao_notas_documentos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('devolucao_nota_id');
            $table->string('tipo_documento');
            $table->string('nome_arquivo');
            $table->string('caminho')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('devolucao_nota_id')
            ->references('id')
            ->on('devolucao_notas')
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
        Schema::dropIfExists('devolucao_notas_documentos');
    }
}
