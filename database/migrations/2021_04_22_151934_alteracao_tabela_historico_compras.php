<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlteracaoTabelaHistoricoCompras extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('historico_compras', function (Blueprint $table) {
            $table->renameColumn('codigo_produto','produto_codigo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('historico_compras', function (Blueprint $table) {
            $table->renameColumn('produto_codigo', 'codigo_produto');
        });
    }
}
