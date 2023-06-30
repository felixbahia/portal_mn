<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableMovimentacaoRecalculoAddEquipe extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movimentacao_recalculo', function (Blueprint $table) {
            $table->integer('unidades_negocios_id')->nullable();
            $table->string('equipe')->nullable();

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
        Schema::table('movimentacao_recalculo', function (Blueprint $table) {
            $table->dropColumn('unidades_negocios_id');
            $table->dropColumn('equipe');
        });
    }
}
