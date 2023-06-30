<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePrecoTirarEspecificacoes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('precos', function ($table) {
            $table->dropColumn('marca');
            $table->dropColumn('linha');
            $table->dropColumn('grupo');
            $table->dropColumn('subgrupo');
            $table->dropColumn('descricao');
            $table->dropColumn('unidade');
            $table->dropColumn('procedencia');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('precos', function ($table) {
            $table->string('marca')->nullable();
            $table->string('linha')->nullable();
            $table->string('grupo')->nullable();
            $table->string('subgrupo')->nullable();
            $table->string('descricao')->nullable();
            $table->string('unidade')->nullable();
            $table->string('procedencia')->nullable();
        });
    }
}
