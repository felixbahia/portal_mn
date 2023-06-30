<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InserirCamposTabelaPedidoItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedido_item', function (Blueprint $table){
            $table->integer('tipo_comissao_campanha')->nullable();
            $table->float('incentivo_campanha')->nullable();

            $table->foreign('tipo_comissao_campanha')
            ->references('id')
            ->on('campanhas_comissoes_tipos')
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
        Schema::table('pedido_item', function (Blueprint $table){
            $table->dropColumn('tipo_comissao_campanha');
            $table->dropColumn('incentivo_campanha');
        });
    }
}
