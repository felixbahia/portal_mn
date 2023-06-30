<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableBookVirtualInsumoParaInstrucoes extends Migration
{
     /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('books_virtuals', function ($table) {
            $table->string('img_instrucoes_lavagem')->nullable();
            $table->dropColumn('img_insumo_lavagem');
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
            $table->string('img_insumo_lavagem')->nullable();
            $table->dropColumn('img_instrucoes_lavagem');
        });
    }
}
