<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdicionarCamposNotaScoreFornecedorFormulariosRespondidos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('score_fornecedores_formulario_respondidos', function (Blueprint $table) {
            $table->integer('score_fornecedor_formulario_tipo_respostas_id');

            $table->foreign('score_fornecedor_formulario_tipo_respostas_id')
                ->references('id')
                ->on('score_fornecedor_formulario_tipo_respostas')
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
        Schema::table('score_fornecedores_formulario_respondidos', function (Blueprint $table) {
            $table->dropForeign('score_fornecedores_formulario_respondidos_score_fornecedor_form');
            $table->dropColumn('score_fornecedor_formulario_tipo_respostas_id');
        });
    }
}
