<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreteTableScoreFornecedorFormularios extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('score_fornecedor_formularios', function (Blueprint $table) {
            $table->increments('id');
            $table->string('pergunta');
            $table->integer('score_fornecedor_formulario_tipo_respostas_id');
            $table->integer('score_fornecedor_formulario_grupo_perguntas_id');
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('score_fornecedor_formulario_tipo_respostas_id')
                ->references('id')
                ->on('score_fornecedor_formulario_tipo_respostas')
                ->onDelete('NO ACTION');
            $table->foreign('score_fornecedor_formulario_grupo_perguntas_id')
                ->references('id')
                ->on('score_fornecedor_formulario_grupo_perguntas')
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
        Schema::dropIfExists('score_fornecedor_formularios');
    }
}
