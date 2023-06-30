<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaNotasComprasImportadas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notas_importadas_compras', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('nota_id')->nullable();
            $table->uuid('estabelecimento_id')->nullable();
            $table->string('estabelecimento_codigo')->nullable();
            $table->string('estabelecimento_nome')->nullable();
            $table->string('documento_numero')->nullable();
            $table->date('emissao')->nullable();
            $table->uuid('fornecedor_id')->nullable();
            $table->string('fornecedor_codigo')->nullable();
            $table->string('fornecedor_nome')->nullable();
            $table->string('fornecedor_documento')->nullable();
            $table->float('valor_total')->nullable();
            $table->string('codigo_operacao')->nullable();
            $table->string('descricao_operacao')->nullable();
            $table->uuid('transportadora_id')->nullable();
            $table->string('transportadora_codigo')->nullable();
            $table->string('transportadora_nome')->nullable();
            $table->string('transportadora_documento')->nullable();
            $table->string('modalidade_frete')->nullable();
            $table->float('quantidade')->nullable();
            $table->float('peso_liquido')->nullable();
            $table->float('desconto')->nullable();
            $table->float('valor_frete')->nullable();
            $table->float('valor_seguro')->nullable();
            $table->float('valor_outras_despesas')->nullable();
            $table->float('valor_ipi')->nullable();
            $table->float('valor_icms')->nullable();
            $table->string('pedido')->nullable();
            $table->date('data_entrada')->nullable();
            $table->string('chave')->nullable();
            $table->boolean('score')->nullable();
            $table->text('informacao_complementar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notas_importadas_compras');
    }
}
