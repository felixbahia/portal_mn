<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlteredTableOrcamentoComprasAddModoTipoFornecedor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orcamento_compras', function (Blueprint $table) {
            $table->string('modo')->default('competencia');
            $table->string('tipo')->default('nacional');
            $table->string('fornecedor_codigo')->nullable();
            $table->float('valor')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orcamento_compras', function (Blueprint $table) {
            $table->dropColumn('modo');
            $table->dropColumn('tipo');
            $table->dropColumn('fornecedor_codigo');
            $table->dropColumn('valor');
        });
    }
}
