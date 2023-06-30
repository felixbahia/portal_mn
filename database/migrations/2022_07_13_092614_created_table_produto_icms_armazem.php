<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableProdutoIcmsArmazem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movimentacao_20', function (Blueprint $table) {
            $table->increments('id');
            $table->string('estabelecimento_codigo', 2);
            $table->string('produto_codigo');
            $table->date('data_movimentacao');
            $table->float('quantidade')->nullable();
            $table->string('sinal')->nullable();
            $table->string('origem')->nullable();
            $table->uuid('documento_id')->nullable();
            $table->string('documento_numero')->nullable();
            $table->uuid('movimento_id')->nullable();
            $table->string('cliente_codigo')->nullable();
            $table->string('item_cfop')->nullable();
            $table->float('item_aliquota')->nullable();
            $table->float('item_preco_unitario')->nullable();
            $table->float('item_preco_total')->nullable();
            $table->string('item_unidade')->nullable();
            $table->float('item_frete')->nullable();
            $table->float('item_ipi')->nullable();
            $table->float('item_desconto')->nullable();
            $table->float('item_seguro')->nullable();
            $table->string('slot')->nullable();
            $table->boolean('efetivado')->nullable();
            $table->float('item_valor_icms')->nullable();
            $table->float('custo')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('movimentacao_20');
    }
}
