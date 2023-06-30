<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableDevolucoesNotasCamposContato extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('devolucoes_notas', function ($table) {
            $table->string('nome_contato')->nullable();
            $table->string('telefone_contato')->nullable();
            $table->string('email_contato')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('devolucoes_notas', function ($table) {
            $table->dropColumn('nome_contato');
            $table->dropColumn('telefone_contato');
            $table->dropColumn('email_contato');
        });
    }
}
