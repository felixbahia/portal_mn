<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableAddCamposVerificacaoRenegociacao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renegociacao_titulos', function (Blueprint $table) {
            $table->integer('maior_atraso')->nullable();     
        });

        Schema::table('aprovacao_renegociacaos', function (Blueprint $table) {
            $table->boolean('motivo_parcelas_maior_permitido')->nullable();     
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('renegociacao_titulos', function (Blueprint $table) {
            $table->dropColumn('maior_atraso');
        });

        Schema::table('aprovacao_renegociacaos', function (Blueprint $table) {
            $table->dropColumn('motivo_parcelas_maior_permitido');
        });
    }
}
