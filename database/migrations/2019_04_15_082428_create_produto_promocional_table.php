<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProdutoPromocionalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('produtos_promocionais', function (Blueprint $table) {
            $table->increments('id');
            $table->string('codigo_produto');
            $table->string('codigo_estabelecimento', 2)->nullable();
            $table->string('tipo_frete', 3)->nullable();
            $table->string('codigo_cliente')->nullable();
            $table->integer('codigo_vendedor')->nullable();
            $table->double('preco_real')->nullable();
            $table->double('preco_dolar')->nullable();
            $table->date('data_expiracao');
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('deleted_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');

            $table->foreign('codigo_vendedor')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('produtos_promocionais');
    }
}
