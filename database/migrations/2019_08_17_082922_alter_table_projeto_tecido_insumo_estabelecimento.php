<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableProjetoTecidoInsumoEstabelecimento extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lancamento_projeto_tecidos', function ($table) {
            $table->integer('codigo_estabelecimento')->nullable()->change();
        });
        Schema::table('lancamento_projeto_insumos', function ($table) {
            $table->integer('codigo_estabelecimento')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
