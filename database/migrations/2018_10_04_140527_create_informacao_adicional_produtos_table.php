<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInformacaoAdicionalProdutosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('informacao_adicional_produtos', function (Blueprint $table) {
            $table->increments('id');
            $table->string("cod_produto");
            $table->string("volume")->nullable();
            $table->string("largura")->nullable();
            $table->string("gramatura")->nullable();
            $table->boolean("exibir_nacional")->nullable();
            $table->integer("created_by");
            $table->integer("modified_by")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('informacao_adicional_produtos');
    }
}
