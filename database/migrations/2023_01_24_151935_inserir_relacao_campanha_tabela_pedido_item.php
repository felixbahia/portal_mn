<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InserirRelacaoCampanhaTabelaPedidoItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedido_item', function (Blueprint $table){
            $table->integer('campanha_id')->nullable();

            $table->foreign('campanha_id')
            ->references('id')
            ->on('campanhas')
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
            $table->dropColumn('campanha_id');
        });
    }
}
