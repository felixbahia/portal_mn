<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompraProdutoImportacaosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('compra_produto_importacaos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('estabelecimento');
            $table->string('numero_documento');
            $table->string('codigo_produto');
            $table->date('data_faturamento')->nullable();
            $table->double('preco_total');
            $table->double('quantidade');
            $table->double('preco_unitario');
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
        Schema::dropIfExists('compra_produto_importacaos');
    }
}
