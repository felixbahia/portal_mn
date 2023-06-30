<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableInvatirosEstoqueAtual extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventario_codigo_estoque_atuals', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('inventario_codigos_id');
            $table->string('estabelecimento_posse', 2);
            $table->string('produto_codigo');
            $table->uuid('produto_id');
            $table->string('fracao_codigo');
            $table->uuid('fracao_id');
            $table->string('endereco')->nullable();
            $table->float('saldo');
            $table->boolean('empenhado');
            $table->string('fracao_pai')->nullable();
            $table->boolean('peca_veio_de_fracionamento')->nullable();
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
        Schema::dropIfExists('inventario_codigo_estoque_atuals');
    }
}
