<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdicionarCampoAjusteFracaoAjusteEstoque extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ajuste_estoques', function (Blueprint $table) {
            $table->boolean('ajuste_fracao')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ajuste_estoques', function (Blueprint $table) {
            $table->dropColumn('ajuste_fracao');
        });
    }
}
