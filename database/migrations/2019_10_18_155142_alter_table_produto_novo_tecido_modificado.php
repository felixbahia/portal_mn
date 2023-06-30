<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableProdutoNovoTecidoModificado extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('produto_novos', function ($table) {
            $table->integer('lancamento_projetos_id')->nullable();
            $table->foreign('lancamento_projetos_id')
                ->references('id')
                ->on('lancamento_projetos')
                ->onDelete('NO ACTION');

            $table->integer('lancamento_projeto_tecidos_id')->nullable();
            $table->foreign('lancamento_projeto_tecidos_id')
                ->references('id')
                ->on('lancamento_projeto_tecidos')
                ->onDelete('NO ACTION');
            $table->integer('lancamento_projeto_produtos_id')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('produto_novos', function ($table) {
            $table->dropColumn('lancamento_projeto_tecidos_id');
        });
    }
}
