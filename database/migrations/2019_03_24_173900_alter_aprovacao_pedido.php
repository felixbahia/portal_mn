<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAprovacaoPedido2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aprovacao_de_pedidos', function ($table) {
            $table->boolean('credito_sem_limite')->nullable(true);
            $table->boolean('credito_data_limite')->nullable(true);
            $table->boolean('preco_limite')->nullable(true);
            $table->boolean('preco_desconto')->nullable(true);
            $table->tinyInteger('nivel_aprovacao_credito')->nullable(true);
            $table->timestamp('data_aprovacao_credito')->nullable(true);
            $table->tinyInteger('nivel_aprovacao_preco')->nullable(true);
            $table->timestamp('data_aprovacao_preco')->nullable(true);
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
            $table->dropColumn('credito_sem_limite')->nullable();
            $table->dropColumn('credito_data_limite')->nullable();
            $table->dropColumn('preco_limite')->nullable();
            $table->dropColumn('preco_desconto')->nullable();
            $table->dropColumn('nivel_aprovacao_credito')->nullable();
            $table->dropColumn('data_aprovacao_credito')->nullable();
            $table->dropColumn('nivel_aprovacao_preco')->nullable();
            $table->dropColumn('data_aprovacao_preco')->nullable();
        });
    }
}
