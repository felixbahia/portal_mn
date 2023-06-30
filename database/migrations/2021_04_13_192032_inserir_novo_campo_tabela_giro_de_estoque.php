<?php

use App\GiroDeEstoque;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InserirNovoCampoTabelaGiroDeEstoque extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        GiroDeEstoque::truncate();
        Schema::table('giro_de_estoques', function (Blueprint $table) {
            $table->float('compras_aberto_vendas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('giro_de_estoques', function (Blueprint $table) {
            $table->dropColumn('compras_aberto_vendas');
        });
    }
}
