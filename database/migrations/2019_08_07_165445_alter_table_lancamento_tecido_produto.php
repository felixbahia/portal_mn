<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableLancamentoTecidoProduto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lancamento_projeto_tecidos', function ($table) {
            $table->integer('lancamento_projeto_produtos_id')->nullable();
            $table->foreign('lancamento_projeto_produtos_id')
                ->references('id')
                ->on('lancamento_projeto_produtos')
                ->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lancamento_projeto_tecidos', function ($table) {
            $table->dropColumn('lancamento_projeto_produtos_id');
        });
    }
}
