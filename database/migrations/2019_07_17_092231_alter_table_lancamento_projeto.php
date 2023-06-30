<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableLancamentoProjeto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lancamento_projetos', function ($table) {
            $table->string('cliente_codigo', 30)->nullable();
            $table->string('cliente_cpf_cnpj', 18)->nullable();
            $table->string('tipo_frete', 3)->nullable();
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
            $table->dropColumn('cliente_codigo');
            $table->dropColumn('cliente_cpf_cnpj');
            $table->dropColumn('tipo_frete');
        });
    }
}
