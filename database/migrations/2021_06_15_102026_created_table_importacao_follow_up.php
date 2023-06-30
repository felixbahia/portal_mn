<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableImportacaoFollowUp extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('importacao_follow_up', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('importacaos_id');

            $table->string('tipo_cor')->nullable();
            $table->date('envio_cor_data_previsao')->nullable();
            $table->date('envio_cor_data_envio')->nullable();
            $table->date('envio_cor_data_recebido')->nullable();

            $table->string('quality_sample_aprovacao')->nullable();
            $table->date('quality_sample_data_previsao_envio')->nullable();
            $table->date('quality_sample_data_envio')->nullable();
            $table->date('quality_sample_data_recebido')->nullable();
            $table->date('quality_sample_data_previsao_aprovacao')->nullable();
            $table->date('quality_sample_data_aprovacao')->nullable();

            $table->string('laboratorio_aprovacao')->nullable();
            $table->date('laboratorio_data_previsao_envio')->nullable();
            $table->date('laboratorio_data_envio')->nullable();
            $table->date('laboratorio_data_recebido')->nullable();
            $table->date('laboratorio_data_aprovacao')->nullable();

            $table->date('tempo_producao_previsao_termino')->nullable();
            $table->date('tempo_producao_termino')->nullable();

            $table->string('amostra_embarque_aprovacao')->nullable();
            $table->date('amostra_embarque_data_previsao_envio')->nullable();
            $table->date('amostra_embarque_data_envio')->nullable();
            $table->date('amostra_embarque_data_recebido')->nullable();
            $table->date('amostra_embarque_data_previsao_aprovacao')->nullable();
            $table->date('amostra_embarque_data_aprovacao')->nullable();

            $table->date('autorizacao_embarque_data_previsao_envio')->nullable();
            $table->date('autorizacao_embarque_data_envio')->nullable();

            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('importacaos_id')
                ->references('id')
                ->on('importacaos')
                ->onDelete('NO ACTION');
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
        Schema::dropIfExists('importacao_follow_up');
    }
}
