<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InserirCampoOcorrenciaDeEntrega extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ocorrencias_de_entregas', function (Blueprint $table) {
            $table->string('codigo_observacao')->nullable();
            $table->string('numero_romaneio')->nullable();
            $table->string('numero_sap_shipment')->nullable();
            $table->string('numero_sap_account')->nullable();
            $table->string('outro_numero_sap_account')->nullable();
            $table->string('filial_emissora')->nullable();
            $table->string('serie_do_conhecimento')->nullable();
            $table->string('numero_do_conhecimento')->nullable();
            $table->string('indicacao_tipo_entrega')->nullable();
            $table->string('cod_emp_emissora_nf')->nullable();
            $table->string('cod_filial_emp_emissora_nf')->nullable();
            $table->dateTime('data_chegada_destino_nf')->nullable();
            $table->dateTime('data_inicio_descarregamento_destino')->nullable();
            $table->dateTime('data_termino_descarregamento_destino')->nullable();
            $table->dateTime('data_saida_destino')->nullable();
            $table->string('cnpj_emissor_nf_devolucao')->nullable();
            $table->string('serie_nf_devolucao')->nullable();
            $table->string('numero_nf_devolucao')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ocorrencias_de_entregas', function (Blueprint $table) {
            $table->dropColumn('codigo_observacao');
            $table->dropColumn('numero_romaneio');
            $table->dropColumn('numero_sap_shipment');
            $table->dropColumn('numero_sap_account');
            $table->dropColumn('outro_numero_sap_account');
            $table->dropColumn('filial_emissora');
            $table->dropColumn('serie_do_conhecimento');
            $table->dropColumn('numero_do_conhecimento');
            $table->dropColumn('indicacao_tipo_entrega');
            $table->dropColumn('cod_emp_emissora_nf');
            $table->dropColumn('cod_filial_emp_emissora_nf');
            $table->dropColumn('data_chegada_destino_nf');
            $table->dropColumn('data_inicio_descarregamento_destino');
            $table->dropColumn('data_termino_descarregamento_destino');
            $table->dropColumn('data_saida_descarregamento_destino');
            $table->dropColumn('cnpj_emissor_nf_devolucao');
            $table->dropColumn('serie_nf_devolucao');
            $table->dropColumn('numero_nf_devolucao');
        });
    }
}
