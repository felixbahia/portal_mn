<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTitulosAbertosNasajonViradasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('titulos_abertos_nasajon_viradas', function (Blueprint $table) {
            $table->string('codigo')->nullable();
            $table->string('cod_cliente')->nullable();
            $table->string('nome_cliente')->nullable();
            $table->string('numero')->nullable();
            $table->integer('parcela')->nullable();
            $table->date('vencimento')->nullable();
            $table->float('valor')->nullable();
            $table->string('conta_agencia')->nullable();
            $table->string('conta_agencia_digito')->nullable();
            $table->string('conta_numero')->nullable();
            $table->string('conta_digito')->nullable();
            $table->uuid('id_estabelecimento')->nullable();
            $table->date('titulo_emissao')->nullable();
            $table->string('nota_numero')->nullable();
            $table->uuid('nota_id')->nullable();
            $table->date('nota_emissao')->nullable();
            $table->string('banco_codigo')->nullable();
            $table->string('banco_nome')->nullable();
            $table->string('cnpj')->nullable();
            $table->uuid('id_cliente')->nullable();
            $table->boolean('titulo_de_terceiro')->nullable();
            $table->string('documento_terceiro')->nullable();
            $table->string('nome_terceiro')->nullable();
            $table->float('saldotitulo')->nullable();
            $table->string('nossonumero')->nullable();
            $table->string('identificadorbancario')->nullable();
            $table->date('vencimento_original')->nullable();
            $table->boolean('tem_prorrogacao')->nullable();
            $table->uuid('titulo_id')->nullable();
            $table->float('multa')->nullable();
            $table->float('desconto')->nullable();
            $table->string('observacao', 1000)->nullable();
            $table->boolean('enviado_para_banco')->nullable();
            $table->float('juros')->nullable();
            $table->date('datainiciomulta')->nullable();
            $table->string('vendedor_codigo')->nullable();
            $table->boolean('enviado_para_cartorio')->nullable();
            $table->string('enviado_para_cartorio_data')->nullable();
            $table->integer('origem')->nullable();
            $table->string('origem_texto')->nullable();
            $table->float('percentualjurosdiario')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('titulos_abertos_nasajon_viradas');
    }
}
