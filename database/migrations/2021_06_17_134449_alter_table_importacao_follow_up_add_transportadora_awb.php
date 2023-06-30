<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableImportacaoFollowUpAddTransportadoraAwb extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('importacao_follow_up', function (Blueprint $table) {
            $table->date('envio_cor_data_revisao')->nullable();
            $table->string('quality_sample_transportadora')->nullable();
            $table->string('quality_sample_awb')->nullable();
            $table->string('amostra_embarque_transportadora')->nullable();
            $table->string('amostra_embarque_awb')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('importacao_follow_up', function (Blueprint $table) {
            $table->dropColumn('envio_cor_data_revisao');
            $table->dropColumn('quality_sample_transportadora');
            $table->dropColumn('quality_sample_awb');
            $table->dropColumn('amostra_embarque_transportadora');
            $table->dropColumn('amostra_embarque_awb');
        });
    }
}
