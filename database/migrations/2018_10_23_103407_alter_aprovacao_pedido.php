<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAprovacaoPedido extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aprovacao_de_pedidos', function ($table) {
            $table->integer('aprovador_id')->nullable(true)->change();
            $table->tinyInteger('nivel_aprovacao');
            $table->boolean('credito')->nullable(true);
            $table->boolean('condicao_pagamento')->nullable(true);
            $table->boolean('preco')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('aprovacao_de_pedidos', function ($table) {
            $table->dropColumn('nivel_aprovacao');
            $table->dropColumn('credito');
            $table->dropColumn('condicao_pagamento');
            $table->dropColumn('preco');
        });
    }
}
