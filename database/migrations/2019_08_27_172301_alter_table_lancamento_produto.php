<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableLancamentoProduto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lancamento_projetos', function ($table) {
            $table->date('data_entrada')->nullable();
            $table->date('data_previsao_entrega')->nullable();
            $table->integer('estabelecimento')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lancamento_projetos', function ($table) {
            $table->dropColumn('data_entrada');
            $table->dropColumn('data_previsao_entrega');
            $table->dropColumn('estabelecimento');
        });
    }
}
