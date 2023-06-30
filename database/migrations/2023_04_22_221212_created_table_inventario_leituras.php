<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableInventarioLeituras extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventario_codigo_leituras', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('inventario_codigos_id');
            $table->string('estabelecimento_posse', 2)->nullable();
            $table->string('produto_codigo')->nullable();
            $table->string('fracao_codigo');
            $table->float('contagem_1');
            $table->float('contagem_2')->nullable();
            $table->float('contagem_3')->nullable();
            $table->float('saldo')->nullable();
            $table->string('endereco')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('inventario_codigos_id')
                ->references('id')
                ->on('inventario_codigos')
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
        Schema::dropIfExists('inventario_codigo_leituras');
    }
}
