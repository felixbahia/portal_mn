<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableRemessaProdutosAddPedidoId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('remessa_produtos', function (Blueprint $table) {
            $table->integer('producao_pedido_id')->nullable();
            $table->integer('lancamento_projetos_id')->change()->nullable(true);

            $table->foreign('producao_pedido_id')
                ->references('id')
                ->on('pedido')
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
        Schema::table('remessa_produtos', function (Blueprint $table) {
            $table->dropColumn('producao_pedido_id');
            $table->integer('lancamento_projetos_id')->change()->nullable(false);
        });
    }
}
