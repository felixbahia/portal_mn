<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TabelaEstoquePoderTerceiros extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('estoque_poder_terceiros', function (Blueprint $table) {
            $table->increments('id');
            $table->string('estabelecimento_codigo');
            $table->string('produto_codigo');
            $table->string('produto_nome')->nullable();
            $table->string('fornecedor_codigo');
            $table->string('fornecedor_nome')->nullable();
            $table->float('saldo_em_terceiro')->nullable()->default(0);
            $table->float('valor_custo_contabil_nasajon')->nullable()->default(0);
            $table->float('valor_custo_contabil_portal')->nullable()->default(0);
            $table->float('valor_custo_gerencial')->nullable()->default(0);
            $table->float('valor_custo_gerencial_portal')->nullable()->default(0);
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
        Schema::dropIfExists('estoque_poder_terceiros');
    }
}
