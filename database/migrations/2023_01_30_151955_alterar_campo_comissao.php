<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarCampoComissao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campanhas_comissao_calculo_itens', function (Blueprint $table) {
            $table->dropColumn('comissao_incentivo_vendedor_interno')->nullable();
            $table->dropColumn('comissao_incentivo_gerente')->nullable();
            $table->dropColumn('comissao_incentivo_representante')->nullable();
            $table->dropColumn('comissao_incentivo_vendedor_interno_tipo')->nullable();
            $table->dropColumn('comissao_incentivo_gerente_tipo')->nullable();
            $table->dropColumn('comissao_incentivo_representante_tipo')->nullable();
            $table->float('incetivo_percentual_comissao')->nullable();
            $table->float('total_percentual')->nullable();
            $table->integer('tipo_comissao_campanha')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campanhas_comissao_calculo_itens', function (Blueprint $table) {
            $table->float('comissao_incentivo_vendedor_interno')->nullable();
            $table->float('comissao_incentivo_gerente')->nullable();
            $table->float('comissao_incentivo_representante')->nullable();
            $table->float('comissao_incentivo_vendedor_interno_tipo')->nullable();
            $table->float('comissao_incentivo_gerente_tipo')->nullable();
            $table->float('comissao_incentivo_representante_tipo')->nullable();
            $table->dropColumn('incetivo_percentual_comissao')->nullable();
            $table->dropColumn('total_percentual')->nullable();
            $table->dropColumn('tipo_comissao_campanha')->nullable();
        });
    }
}
