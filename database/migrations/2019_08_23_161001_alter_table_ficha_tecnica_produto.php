<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableFichaTecnicaProduto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ficha_tecnica_produtos', function (Blueprint $table) {
            $table->foreign('lancamento_projetos_id')
                ->references('id')
                ->on('lancamento_projetos')
                ->onDelete('NO ACTION');
        });

        Schema::dropIfExists('ficha_tecnica_produto');

        Schema::dropIfExists('ficha_tecnica_produto_insumo');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
