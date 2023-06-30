<?php

use App\PedidoMaiorEstoque;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdicionarCamposTabelaPedidoMaiorEstoque extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        PedidoMaiorEstoque::truncate();
        Schema::table('pedido_maior_estoques', function (Blueprint $table) {
            $table->float('necessidade_compras');
            $table->float('compras_em_transito');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pedido_maior_estoques', function (Blueprint $table) {
            $table->dropColumn('necessidade_compras');
            $table->dropColumn('compras_em_transito');
        });
    }
}
