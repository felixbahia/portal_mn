<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableDevolucoesNotasRenameTableRenameStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('devolucoes_notas', 'devolucao_notas');

        Schema::table('devolucao_notas', function ($table) {
            $table->renameColumn('status', 'devolucao_nota_status_id');
            $table->dropColumn('aprovador');
            $table->dropColumn('reprovador');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('devolucao_notas', 'devolucoes_notas');

        Schema::table('devolucoes_notas', function ($table) {
            $table->renameColumn('devolucao_nota_status_id', 'status');
            $table->integer('aprovador')->nullable();
            $table->integer('reprovador')->nullable();
        });
    }
}
