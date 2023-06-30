<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateComissaoGerentesVendedoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comissao_gerentes_vendedores', function (Blueprint $table) {
            $table->integer('mes');
            $table->integer('ano');
            $table->string('codigo_representante');
            $table->float('porcentagem');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('comissao_gerentes_vendedores');
    }
}
