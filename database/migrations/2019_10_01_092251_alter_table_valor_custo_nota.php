<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableValorCustoNota extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('valor_custo_notas', function ($table) {
            $table->string('fornecedor_cnpj')->nullable()->change();
            $table->string('fornecedor_codigo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('valor_custo_notas', function ($table) {
            $table->string('fornecedor_cnpj')->nullable('false')->change();
            $table->dropColumn('fornecedor_codigo');
        });
    }
}