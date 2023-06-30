<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FichaTecnicaProdutoInsumo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ficha_tecnica_produto_insumo', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('ficha');
            $table->string('insumo');
            $table->decimal('quantidade');
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('ficha', 'fk_ficha_tecnica_produto_insumo_ficha')->references('id')->on('ficha_tecnica_produto')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('created_by', 'fk_ficha_tecnica_produto_insumo_created_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('updated_by', 'fk_ficha_tecnica_produto_insumo_updated_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ficha_tecnica_produto_insumo');
    }
}
