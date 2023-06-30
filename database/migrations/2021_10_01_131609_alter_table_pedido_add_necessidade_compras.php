<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePedidoAddNecessidadeCompras extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedido_item', function (Blueprint $table) {
            $table->integer('necessidades_compras_id')->nullable();

            $table->foreign('necessidades_compras_id')
                ->references('id')
                ->on('necessidades_compras')
                ->onDelete('NO ACTION');
        });
        
        Schema::table('necessidades_compras', function (Blueprint $table) {
            $table->integer('pedido_id')->nullable();

            $table->foreign('pedido_id')
                ->references('id')
                ->on('pedido')
                ->onDelete('NO ACTION');
        });

        Schema::table('necessidades_compras_x_projetos', function (Blueprint $table) {
            $table->integer('pedido_id')->nullable();
            $table->integer('pedido_item_id')->nullable();
            $table->integer('lancamento_projetos_id')->change()->nullable(true);

            $table->foreign('pedido_id')
                ->references('id')
                ->on('pedido')
                ->onDelete('NO ACTION');
            $table->foreign('pedido_item_id')
                ->references('id')
                ->on('pedido_item')
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
        Schema::table('pedido', function (Blueprint $table) {
            $table->dropColumn('necessidades_compras_id');
        });

        Schema::table('necessidades_compras', function (Blueprint $table) {
            $table->dropColumn('pedido_id');
        });

        Schema::table('necessidades_compras_x_projetos', function (Blueprint $table) {
            $table->dropColumn('pedido_id');
            $table->dropColumn('pedido_item_id');
            $table->integer('lancamento_projetos_id')->change()->nullable(false);
        });
    }
}
