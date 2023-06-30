<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFichaTecnicaSequenciaOperacionalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ficha_tecnica_sequencia_operacionals', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('ficha_tecnica_produtos_id');
            $table->integer('ordem');
            $table->string('operacao');
            $table->string('tipo_ponto');
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
        Schema::dropIfExists('ficha_tecnica_sequencia_operacionals');
    }
}
