<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableConfirmacaoNotasDeSaidaAdicionarCampos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('confirmacao_notas_de_saida', function ($table) {
            $table->string('foto_canhoto')->nullable()->after('data_saida');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('confirmacao_notas_de_saida', function ($table) {
            $table->dropColumn('foto_canhoto');
        });
    }
}
