<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarTabelaCampanhaComissaoAdicionarDevolucao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campanhas_comissao_calculos', function ($table) {
            $table->uuid('id_nota_devolucao')->nullable();
            $table->float('valor_nota')->nullable();
            $table->float('quantidade_nota')->nullable();
            $table->float('valor_nota_devolucao')->nullable();
            $table->float('quantidade_nota_devolucao')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campanhas_comissao_calculos', function ($table) {
            $table->dropColumn('id_nota_devolucao');
            $table->dropColumn('valor_nota');
            $table->dropColumn('valor_nota_devolucao');
            $table->dropColumn('quantidade_nota');
            $table->dropColumn('quantidade_nota_devolucao');
        });
    }
}
