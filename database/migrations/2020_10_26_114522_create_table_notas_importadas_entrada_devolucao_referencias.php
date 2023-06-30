<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableNotasImportadasEntradaDevolucaoReferencias extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notas_importadas_entrada_devolucao_referencias', function (Blueprint $table) {
            $table->increments('id');
            $table->string('chave_numero_nota')->nullable();
            $table->integer('notas_importadas_entradas_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('notas_importadas_entradas_id')
                ->references('id')
                ->on('notas_importadas_entradas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notas_importadas_entrada_devolucao_referencias');
    }
}
