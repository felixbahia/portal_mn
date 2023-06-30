<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFichaTecnicaTabelaMedidasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ficha_tecnica_tabela_medidas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('ficha_tecnica_produtos_id');
            $table->integer('ordem');
            $table->string('medida_descricao');
            $table->float('medida_p');
            $table->float('medida_m');
            $table->float('medida_g');
            $table->float('medida_gg');
            $table->float('medida_xg');
            $table->float('medida_xgg');
            $table->float('tolerancia_xgg');
            $table->timestamps();

            $table->foreign('ficha_tecnica_produtos_id')->references('id')->on('ficha_tecnica_produtos');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ficha_tecnica_tabela_medidas');
    }
}
