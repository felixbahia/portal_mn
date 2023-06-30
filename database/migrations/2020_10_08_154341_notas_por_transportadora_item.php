<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NotasPorTransportadoraItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notas_transportadora_itens', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nota_serie')->nullable();
            $table->string('numero_nota')->nullable();
            $table->uuid('nota_id')->nullable();
            $table->date('data_emissao')->nullable();
            $table->float('peso_nota')->nullable();
            $table->float('valor_nota')->nullable();
            $table->string('romaneio_nota')->nullable();
            $table->string('numero_sap_shipment_nota')->nullable();
            $table->string('numero_sap_account_nota')->nullable();
            $table->string('outro_numero_sap_account_nota')->nullable();
            $table->string('devolucao_nota')->nullable();
            $table->string('filial_emissora_documento')->nullable();
            $table->string('conhecimento_serie')->nullable();
            $table->string('numero_conhecimento')->nullable();
            $table->float('valor_frete')->nullable();
            $table->date('data_emissao_conhecimento')->nullable();
            $table->string('cnpj_destinatario')->nullable();
            $table->string('cnpj_filial_transportadora')->nullable();
            $table->string('uf_local_coleta')->nullable();
            $table->string('uf_unidade_emissora')->nullable();
            $table->string('uf_destinatario')->nullable();
            $table->string('conta_razao')->nullable();
            $table->string('codigo_iva')->nullable();
            $table->string('numero_romaneio_conhecimento')->nullable();
            $table->string('numero_sap_conhecimento')->nullable();
            $table->string('numero_sap_shipment_conhecimento')->nullable();
            $table->string('numero_sap_account_conhecimento')->nullable();
            $table->string('devolucao')->nullable();
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
        Schema::dropIfExists('notas_transportadora_itens');
    }
}