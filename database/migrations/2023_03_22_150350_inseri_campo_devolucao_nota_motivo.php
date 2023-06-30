<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InseriCampoDevolucaoNotaMotivo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('devolucao_nota_motivos', function (Blueprint $table) {
            $table->string('assinatura_pedido', 3)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('devolucao_nota_motivos', function (Blueprint $table) {
            $table->dropColumn('assinatura_pedido');
        });
    }
}
