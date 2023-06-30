<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenomearCampoTabelaHistoricoCompras extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('historico_compras', function (Blueprint $table) {
            $table->renameColumn('descricao','produto_descricao');
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
            $table->renameColumn('produto_descricao','descricao');
        });
    }
}