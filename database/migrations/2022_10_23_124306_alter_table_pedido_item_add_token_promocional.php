<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePedidoItemAddTokenPromocional extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedido_item', function (Blueprint $table) {
            $table->string('token_promocional')->nullable();
            $table->string('promocional_unidade')->nullable();
            $table->string('unidade')->nullable();
            $table->string('gml')->nullable();
            $table->float('promocional_preco_unitario')->nullable();
            $table->float('promocional_quantidade')->nullable();
            $table->float('preco_unitario_sem_desconto')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pedido_item', function (Blueprint $table) {
            $table->dropColumn('token_promocional');
            $table->dropColumn('promocional_unidade');
            $table->dropColumn('promocional_preco_unitario');
            $table->dropColumn('promocional_quantidade');
            $table->dropColumn('unidade');
            $table->dropColumn('gml');
            $table->dropColumn('preco_unitario_sem_desconto');
            $table->dropColumn('promocional_preco_unitario_sem_desconto');
        });
    }
}
