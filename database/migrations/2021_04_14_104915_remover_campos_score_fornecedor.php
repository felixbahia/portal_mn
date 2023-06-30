<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoverCamposScoreFornecedor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('score_fornecedores', function (Blueprint $table) {
            $table->dropColumn('fornecedor');
            $table->dropColumn('cnpj');
            $table->dropColumn('estado');
            $table->dropColumn('regime_tributacao');
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
            $table->string('fornecedor');
            $table->string('cnpj');
            $table->string('estado')->nullable();
            $table->string('regime_tributacao')->nullable();
        });
    }
}
