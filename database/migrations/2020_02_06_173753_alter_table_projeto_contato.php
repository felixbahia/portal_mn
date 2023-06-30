<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableProjetoContato extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lancamento_projetos', function ($table) {
            $table->string('nome_contato')->nullable();
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
        Schema::table('lancamento_projetos', function ($table) {
            $table->dropColumn('nome_contato');
            $table->dropColumn('email_contato');
        });
    }
}
