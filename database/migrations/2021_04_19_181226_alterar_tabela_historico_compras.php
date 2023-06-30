<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarTabelaHistoricoCompras extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('historico_compras', function (Blueprint $table) {
            $table->string('descricao')->nullable();
            $table->float('quantidade_restante')->nullable();
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
            $table->dropColumn('descricao');
            $table->dropColumn('quantidade_restante');
        });
    }
}
