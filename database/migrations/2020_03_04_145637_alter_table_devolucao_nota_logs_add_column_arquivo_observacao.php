<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableDevolucaoNotaLogsAddColumnArquivoObservacao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('devolucao_nota_logs', function ($table) {
            $table->boolean('arquivo')->nullable();
            $table->string('observacao_antigo')->nullable();
            $table->string('observacao_novo')->nullable();
            $table->string('mensagem')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('devolucao_nota_logs', function ($table) {
            $table->dropColumn('arquivo');
            $table->dropColumn('observacao_antigo');
            $table->dropColumn('observacao_novo');
            $table->string('mensagem')->nullable(false)->change();
        });
    }
}
