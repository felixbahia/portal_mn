<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdicionarCfopParaContaEOrdem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('naturezas_de_operacao', function ($table) {
            $table->string('nat_op_venda_conta_ordem')->nullable();
            $table->string('nat_op_remessa_conta_ordem')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('naturezas_de_operacao', function ($table) {
            $table->dropColumn('nat_op_venda_conta_ordem');
            $table->dropColumn('nat_op_remessa_conta_ordem');
        });
    }
}
