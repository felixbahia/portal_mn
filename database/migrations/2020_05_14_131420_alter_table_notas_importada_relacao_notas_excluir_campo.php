<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableNotasImportadaRelacaoNotasExcluirCampo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('notas_importadas_entrada_relacao_notas', 'notas_importadas_entrada_id')) {
            Schema::table('notas_importadas_entrada_relacao_notas', function (Blueprint $table) {
                $table->dropColumn(['notas_importadas_entrada_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('notas_importadas_entrada_relacao_notas', 'notas_importadas_entrada_id')) {
            Schema::table('notas_importadas_entrada_relacao_notas', function (Blueprint $table) {
                $table->string(['notas_importadas_entrada_id']);
            });
        }
    }
}
