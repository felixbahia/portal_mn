<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePedidoItemAddFaccaoIdAndPedidoCompraAndRemessa extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedido_item', function (Blueprint $table) {
            $table->integer('faccaos_id')->nullable();
            $table->uuid('pedido_compras_uuid_nasajon')->nullable();
            $table->uuid('pedido_remessa_uuid_nasajon')->nullable();

            $table->foreign('faccaos_id')
                ->references('id')
                ->on('faccaos')
                ->onDelete('NO ACTION');
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
            $table->dropColumn('faccaos_id');
            $table->dropColumn('pedido_compras_uuid_nasajon');
            $table->dropColumn('pedido_remessa_uuid_nasajon');
        });
    }
}
