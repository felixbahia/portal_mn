<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableDevolucoesNotasCampoAprovadorReprovador extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('devolucoes_notas', function ($table) {
            $table->integer('aprovador')->nullable();
            $table->integer('reprovador')->nullable();

            $table->foreign('aprovador')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('reprovador')
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
        Schema::table('devolucoes_notas', function ($table) {
            $table->dropForeign('devolucoes_notas_aprovador_foreing');
            $table->dropForeign('devolucoes_notas_reprovador_foreing');
            $table->dropColumn('aprovador');
            $table->dropColumn('reprovador');
        });
    }
}