<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableBookVirtual extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('books_virtuals', function ($table) {
            $table->string('origem')->nullable();
            $table->string('artigo')->nullable();
            $table->string('nome')->nullable();
            $table->string('pecas')->nullable();
            $table->string('img_insumo_lavagem')->nullable();
            $table->string('caracteristicas')->nullable();
            $table->dropColumn('cod_produto');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('books_virtuals', function ($table) {
            $table->string('cod_produto')->nullable();
            $table->dropColumn('origem');
            $table->dropColumn('artigo');
            $table->dropColumn('nome');
            $table->dropColumn('pecas');
            $table->dropColumn('img_insumo_lavagem');
            $table->dropColumn('caracteristicas');
        });
    }
}