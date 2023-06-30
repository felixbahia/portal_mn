<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableProdutoEspecificacaos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('produto_especificacaos', function (Blueprint $table) {
            $table->string('codigo_produto');
            $table->string('marca')->nullable();
            $table->string('linha')->nullable();
            $table->string('grupo')->nullable();
            $table->string('subgrupo')->nullable();
            $table->string('descricao')->nullable();
            $table->string('unidade')->nullable();
            $table->string('procedencia')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('produto_especificacaos');
    }
}
