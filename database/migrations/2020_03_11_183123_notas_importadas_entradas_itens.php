<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NotasImportadasEntradasItens extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notas_importadas_entradas_itens', function (Blueprint $table) {
            $table->integer('notas_importadas_entradas_id');
            $table->string('codigo_produto')->nullable();
            $table->string('codigo_ean')->nullable();
            $table->string('codigo_ncm')->nullable();
            $table->string('codigo_cest')->nullable();
            $table->string('codigo_cfop')->nullable();
            $table->string('comercial_unidade',2)->nullable();
            $table->float('comercial_quantidade')->nullable();
            $table->float('comercial_valor_unitario')->nullable();
            $table->float('valor_total')->nullable();
            $table->string('produto_nome')->nullable();
            $table->string('tributavel_codigo_ean')->nullable();
            $table->string('tributavel_unidade',2)->nullable();
            $table->float('tributavel_quantidade')->nullable();
            $table->float('icms_mercadoria_origem')->nullable();
            $table->float('icms_tributacao_cts')->nullable();
            $table->float('icms_modalidade_bc')->nullable();
            $table->float('icms_aliquota')->nullable();
            $table->float('icms_valor')->nullable();
            $table->string('pis_cts')->nullable();
            $table->float('pis_base_calculo')->nullable();
            $table->float('pis_aliquota')->nullable();
            $table->float('pis_valor')->nullable();
            $table->float('cofins_cts')->nullable();
            $table->float('cofins_base_calculo')->nullable();
            $table->float('cofins_aliquota')->nullable();
            $table->float('cofins_valor')->nullable();
            $table->text('descricao')->nullable();
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
        Schema::dropIfExists('notas_importadas_entradas_itens');
    }
}
