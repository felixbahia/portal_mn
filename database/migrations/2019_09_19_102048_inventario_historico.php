<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InventarioHistorico extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventario_historiocos', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('inventario_nasajaon');
            $table->string('estabelecimento', 5);
            $table->smallInteger('contagem');
            $table->timestamp('data');

            $table->integer('created_by');
            $table->timestamps();
            $table->foreign('created_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });

        Schema::create('inventario_historioco_produtos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('inventario_historiocos_id');
            $table->string('codigo_produto');
            $table->float('quantidade');
            $table->boolean('codigo_barras');
            
            $table->integer('created_by');
            $table->timestamps();
            $table->foreign('inventario_historiocos_id')->references('id')->on('inventario_historiocos')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('created_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
        
        Schema::create('inventario_historioco_produto_pecas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('inventario_historioco_produtos_id');
            $table->string('codigo_peca')->nullable();
            $table->string('endereco');
            $table->float('quantidade');
            
            $table->integer('created_by');
            $table->timestamps();
            $table->foreign('inventario_historioco_produtos_id')->references('id')->on('inventario_historioco_produtos')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('created_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventario_historioco_produto_pecas');
        Schema::dropIfExists('inventario_historioco_produtos');
        Schema::dropIfExists('inventario_historiocos');
    }
}
