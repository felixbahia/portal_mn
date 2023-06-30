<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarTabelaCampanhasConsultaMetaVendedoresProdutoAdicionarCampoMetros extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campanhas_consulta_meta_vendedores_produtos', function ($table) {
            $table->float('kilo')->nullable();
            $table->float('metros')->nullable();
            $table->float('unidade')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campanhas_consulta_meta_vendedores_produtos', function ($table) {
            $table->dropColumn('kilo');
            $table->dropColumn('metros');
            $table->dropColumn('unidade');
        });
    }
}
