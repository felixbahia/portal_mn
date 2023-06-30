<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableImportacao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('importacaos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('estabelecimento_codigo', 2);
            $table->string('fornecedor_cpf_cnpj');
            $table->string('numero_proforma');
            $table->string('pedido_compras');
            $table->string('referencia')->nullable();
            $table->date('data_proforma');
            $table->date('data_previsao_carta_programa')->nullable();
            $table->date('data_previsao_recebimento');
            $table->string('respresentante_cpf_cnpj')->nullable();
            $table->date('data_carga_pronta_previsao')->nullable();
            $table->date('data_carga_pronta_realizado')->nullable();
            $table->date('data_embarque_previsao')->nullable();
            $table->date('data_embarque_realizado')->nullable();
            $table->date('data_chegada_porto_previsao')->nullable();
            $table->date('data_chegada_porto_realizado')->nullable();
            $table->date('data_di_previsao')->nullable();
            $table->date('data_di_realizado')->nullable();
            $table->date('data_devolucao_cntr_previsao')->nullable();
            $table->date('data_devolucao_cntr_realizado')->nullable();
            $table->date('data_quality_sample_enviado')->nullable();
            $table->date('data_quality_sample_recebido')->nullable();
            $table->date('data_handlooms_enviado')->nullable();
            $table->date('data_handlooms_recebido')->nullable();
            $table->date('data_strike_off_enviado')->nullable();
            $table->date('data_strike_off_recebido')->nullable();
            $table->date('data_amostra_embarque_enviado')->nullable();
            $table->date('data_amostra_embarque_recebido')->nullable();
            $table->boolean('aprovacao_amostra_embarque')->nullable();
            $table->string('aprovado_embarque_produto_codigo')->nullable();
            $table->integer('tempo_producao')->nullable();
            $table->string('arquivo_carta_programada')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('deleted_by')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('importacaos');
    }
}
