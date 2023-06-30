<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableRenegociacaoAvalistaEstabelecimentoCpfCnpj extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renegociacao_titulo_avalistas', function (Blueprint $table) {
            $table->string('estabelecimento_codigo', 2)->nullable();
            $table->string('cpf_cnpj')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('renegociacao_titulo_avalistas', function (Blueprint $table) {
            $table->dropColumn('estabelecimento_codigo');
            $table->dropColumn('cpf_cnpj');
        });
    }
}
