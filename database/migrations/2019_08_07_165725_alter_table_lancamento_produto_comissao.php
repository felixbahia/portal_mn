<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableLancamentoProdutoComissao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lancamento_projeto_produtos', function ($table) {
            $table->string('codigo_produto')->nullable()->change();
            $table->string('detalhe_producao')->nullable();
            $table->dropColumn('comissao');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lancamento_projeto_produtos', function ($table) {
            $table->float('comissao');
        });
    }
}
