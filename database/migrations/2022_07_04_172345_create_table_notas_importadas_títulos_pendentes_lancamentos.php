<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableNotasImportadasTítulosPendentesLancamentos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notas_importadas_entradas_titulos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('estabelecimento')->nullable();
            $table->string('fatura')->nullable();
            $table->string('nota')->nullable();
            $table->string('cfop')->nullable();
            $table->string('chave_nfe')->nullable();
            $table->string('fornecedor_documento')->nullable();
            $table->string('fornecedor_nome')->nullable();
            $table->float('valor')->nullable();
            $table->date('emissao')->nullable();
            $table->boolean('lancado')->nullable();
            $table->integer('notas_importadas_entrada_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('notas_importadas_entrada_id')
                ->references('id')
                ->on('notas_importadas_entradas')
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
        Schema::dropIfExists('notas_importadas_entradas_titulos');
    }
}
