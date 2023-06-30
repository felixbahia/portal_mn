<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CustoGerencialImportacaoNotas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('valor_custo_nota_produtos', function ($table) {
            $table->float('custo_gerencial')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('valor_custo_nota_produtos', function ($table) {
            $table->dropColumn('custo_gerencial');
        });
    }
}
