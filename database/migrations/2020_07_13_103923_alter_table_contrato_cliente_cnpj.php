<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableContratoClienteCnpj extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contrato_clientes', function (Blueprint $table) {
            $table->dropColumn('cpf_cnpj_cliente');
        });
        Schema::table('contrato_clientes', function (Blueprint $table) {
            $table->string('cliente_cpf_cnpj')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contrato_clientes', function (Blueprint $table) {
            $table->dropColumn('cliente_cpf_cnpj');
        });
        Schema::table('contrato_clientes', function (Blueprint $table) {
            $table->string('cpf_cnpj_cliente')->nullable();
        });
    }
}
