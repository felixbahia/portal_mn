<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableImportacaoMudancaCampoFornecedorCpf extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('importacaos', function (Blueprint $table) {
            $table->renameColumn('fornecedor_cpf_cnpj','fornecedor_codigo');
            $table->renameColumn('respresentante_cpf_cnpj','respresentante_codigo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('importacaos', function (Blueprint $table) {
            $table->renameColumn('fornecedor_codigo','fornecedor_cpf_cnpj');
            $table->renameColumn('respresentante_codigo','respresentante_cpf_cnpj');
        });
    }
}
