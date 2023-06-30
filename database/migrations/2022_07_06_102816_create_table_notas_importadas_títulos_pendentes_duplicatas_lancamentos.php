<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableNotasImportadasTítulosPendentesDuplicatasLancamentos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notas_importadas_entradas_titulos_duplicatas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('duplicata')->nullable();
            $table->date('vencimento')->nullable();
            $table->float('valor')->nullable();
            $table->integer('notas_importadas_entradas_titulo_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('notas_importadas_entradas_titulo_id')
                ->references('id')
                ->on('notas_importadas_entradas_titulos')
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
        Schema::dropIfExists('notas_importadas_entradas_titulos_duplicatas');
    }
}
