<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdicionarIdPerguntaScoreFornecedor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('public.score_fornecedores_formulario_respondidos', function (Blueprint $table) {
            $table->integer('score_fornecedor_formulario_id')->nullable();
            
            $table->foreign('score_fornecedor_formulario_id')
            ->references('id')
            ->on('public.score_fornecedor_formularios')
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
        Schema::table('public.score_fornecedores_formulario_respondidos', function (Blueprint $table) {
            $table->dropForeign('public_score_fornecedores_formulario_respondidos_score_forneced');
            $table->dropColumn('score_fornecedor_formulario_id');
        });
    }
}
