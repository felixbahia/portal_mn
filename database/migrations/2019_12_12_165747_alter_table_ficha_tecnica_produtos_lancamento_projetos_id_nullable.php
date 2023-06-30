<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableFichaTecnicaProdutosLancamentoProjetosIdNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ficha_tecnica_produtos', function ($table) {
            $table->integer('lancamento_projetos_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ficha_tecnica_produtos', function ($table) {
            $table->integer('lancamento_projetos_id')->nullable(false)->change();
        });
    }
}
