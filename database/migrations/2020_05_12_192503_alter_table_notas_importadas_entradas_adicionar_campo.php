<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableNotasImportadasEntradasAdicionarCampo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notas_importadas_entradas', function (Blueprint $table) {
            $table->float('valor_seguro')->nullable();
            $table->float('valor_desconto')->nullable();
            $table->float('valor_ipi')->nullable();
            $table->float('valor_ipi_devolucao')->nullable();
            $table->float('valor_outros')->nullable();
            $table->float('peso_liquido')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notas_importadas_entradas', function (Blueprint $table) {
            $table->dropColumn('valor_seguro');
            $table->dropColumn('valor_desconto');
            $table->dropColumn('valor_ipi');
            $table->dropColumn('valor_ipi_devolucao');
            $table->dropColumn('valor_outros');
            $table->dropColumn('peso_liquido');
        });
    }
}
