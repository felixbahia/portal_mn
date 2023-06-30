<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFichaTecnicaInfoAdicionalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ficha_tecnica_info_adicionals', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('ficha_tecnica_produtos_id');
            $table->string('lavagem');
            $table->string('encolhimento');
            $table->string('etiqueta_tamanho');
            $table->string('etiqueta_composicao');
            $table->string('imagem_produto');
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
        Schema::dropIfExists('ficha_tecnica_info_adicionals');
    }
}
