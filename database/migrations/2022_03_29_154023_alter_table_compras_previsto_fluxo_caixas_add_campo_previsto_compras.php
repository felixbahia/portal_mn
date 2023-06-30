<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableComprasPrevistoFluxoCaixasAddCampoPrevistoCompras extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('compras_previsto_fluxo_caixas', function (Blueprint $table) {
            $table->float('compras_previsto')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('compras_previsto_fluxo_caixas', function (Blueprint $table) {
            $table->dropColumn('compras_previsto')->nullable();
        });
    }
}
