<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaScoreFornecedorNotas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('public.score_fornecedores_notas_lancamentos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('pergunta');
            $table->string('resposta')->nullable();
            $table->string('grupo_pergunta');
            $table->integer('notas_importadas_compra_id')->nullable();
            $table->integer('score_fornecedor_formulario_id')->nullable();
            $table->integer('score_fornecedor_tipo_respostas_nota_id')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('score_fornecedor_formulario_id')
                ->references('id')
                ->on('public.score_fornecedor_formularios')
                ->onDelete('NO ACTION');
            $table->foreign('score_fornecedor_tipo_respostas_nota_id')
                ->references('id')
                ->on('public.score_fornecedor_formulario_tipo_respostas')
                ->onDelete('NO ACTION');
            $table->foreign('notas_importadas_compra_id')
                ->references('id')
                ->on('public.notas_importadas_compras')
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
        Schema::dropIfExists('score_fornecedores_notas_lancamentos');
    }
}
