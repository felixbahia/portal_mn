<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoverCamposNotaScoreFornecedor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('score_fornecedores', function (Blueprint $table) {
            $table->dropColumn('nota_entrada_numero');
            $table->dropColumn('nota_entrada_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('score_fornecedores', function (Blueprint $table) {
            $table->string('nota_entrada_numero')->nullable();
            $table->string('nota_entrada_id')->nullable();
        });
    }
}
