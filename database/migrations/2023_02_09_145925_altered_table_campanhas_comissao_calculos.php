<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlteredTableCampanhasComissaoCalculos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campanhas_comissao_calculos', function (Blueprint $table) {
            $table->float('porcetagem_comissao_gerente')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campanhas_comissao_calculos', function (Blueprint $table) {
            $table->dropColumn('porcetagem_comissao_gerente')->nullable();
        });
    }
}
