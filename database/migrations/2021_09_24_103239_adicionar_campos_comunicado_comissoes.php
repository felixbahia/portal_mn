<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdicionarCamposComunicadoComissoes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('comunicado_comissoes', function (Blueprint $table) {
            $table->string('vendedor_codigo')->nullable();
            $table->float('valor_meta')->nullable();
            $table->float('valor_comissao')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('comunicado_comissoes', function (Blueprint $table) {
            $table->dropColumn('vendedor_codigo');
            $table->dropColumn('valor_meta');
            $table->dropColumn('valor_comissao');
        });
    }
}
