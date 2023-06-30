<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NotasImportadasEntradas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notas_importadas_entradas', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('nota_id')->nullable();
            $table->string('documento_chave')->nullable();
            $table->string('documento_numero',30)->nullable();
            $table->string('documento_serie',3)->nullable();
            $table->string('documento_cfop',30)->nullable();
            $table->string('fornecedor_id')->nullable();
            $table->string('fornecedor_cnpj')->nullable();
            $table->string('fornecedor_nome')->nullable();
            $table->string('tipo')->nullable();
            $table->uuid('cliente_id',30)->nullable();
            $table->string('cliente_cpf_cnpj',30)->nullable();
            $table->string('cliente_nome')->nullable();
            $table->dateTime('data_emissao');
            $table->float('peso_bruto')->nullable();
            $table->float('peso_base_calculo')->nullable();
            $table->float('valor_total_servico')->nullable();
            $table->float('valor_a_receber')->nullable();
            $table->float('valor_frete')->nullable();
            $table->float('valor_despacho')->nullable();
            $table->float('valor_pedagio')->nullable();
            $table->float('valor_gris')->nullable();
            $table->float('valor_tas')->nullable();
            $table->float('valor_total_carga')->nullable();
            $table->float('icms_cst')->nullable();
            $table->float('icms_base_calculo')->nullable();
            $table->float('icms_aliquota')->nullable();
            $table->float('icms_valor')->nullable();
            $table->float('quantidade')->nullable();
            $table->string('estabelecimento',03)->nullable();
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
        Schema::dropIfExists('notas_importadas_entradas');
    }
}
