<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableSugestaoComprasFotos extends Migration
{
    public function up()
    {
        Schema::create('sugestao_compra_fotos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('sugestao_compra_id');
            $table->string('nome_arquivo');
            $table->string('caminho');
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();


            $table->foreign('sugestao_compra_id')
            ->references('id')
            ->on('sugestao_compras')
            ->onDelete('NO ACTION');

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

        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sugestao_compra_fotos');
    }
}
