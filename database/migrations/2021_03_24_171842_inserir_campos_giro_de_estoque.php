<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InserirCamposGiroDeEstoque extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('giro_de_estoques', function (Blueprint $table) {
            $table->float('preco_venda')->nullable();
            $table->float('custo_gerencial')->nullable();
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
            $table->dropColumn('preco_venda');
            $table->dropColumn('custo_gerencial');
        });
    }
}
