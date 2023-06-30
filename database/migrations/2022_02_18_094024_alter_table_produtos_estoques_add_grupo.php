<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableProdutosEstoquesAddGrupo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('produtos_estoques', function (Blueprint $table) {
            $table->string('grupo')->nullable();
            $table->integer('produto_grupos_id')->nullable();

            $table->foreign('produto_grupos_id')
                ->references('id')
                ->on('produto_grupos')
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
        Schema::table('produtos_estoques', function (Blueprint $table) {
            $table->dropColumn('grupo');
            $table->dropColumn('produto_grupos_id');
        });
    }
}
