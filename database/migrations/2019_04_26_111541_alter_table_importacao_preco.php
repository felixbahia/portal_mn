<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableImportacaoPreco extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('importacao_precos', 'precos');

        Schema::table('precos', function ($table) {
            $table->dropColumn('grupo');
            $table->dropColumn('marca');
            $table->dropColumn('linha');
            $table->dropColumn('subgrupo');
            $table->dropColumn('descricao');
            $table->datetime('ultima_compra')->nullable();

            // $table->dropUnique('codigo_produto');
            $table->dropUnique('deleted_by');

            $table->unique(['codigo_produto', 'deleted_by']);

        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('precos', 'importacao_precos');

        Schema::table('importacao_precos', function ($table) {
            $table->string('grupo')->nullable();
            $table->string('marca')->nullable();
            $table->string('linha')->nullable();
            $table->string('subgrupo')->nullable();
            $table->string('descricao')->nullable();
            $table->dropColumn('ultima_compra');
        });
    }
}
