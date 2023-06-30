<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableFichaTecnicaProdutoInfoAdicionalNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ficha_tecnica_info_adicionals', function ($table) {
            $table->string('lavagem')->nullable(true)->change();
            $table->string('encolhimento')->nullable(true)->change();
            $table->string('imagem_produto')->nullable(true)->change();   
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ficha_tecnica_info_adicionals', function ($table) {
            $table->string('lavagem')->nullable(false)->change();
            $table->string('encolhimento')->nullable(false)->change();
            $table->string('imagem_produto')->nullable(false)->change();   
        });
    }
}
