<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableHistoricoCompra extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('historico_compras', function (Blueprint $table) {
            $table->string('estabelecimento');
            $table->uuid('pedido_id');
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
            $table->dropColumn('estabelecimento');
            $table->dropColumn('pedido_id');
        });
    }
}
