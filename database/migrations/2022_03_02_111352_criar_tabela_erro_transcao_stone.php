<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaErroTranscaoStone extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stone_erro_transacoes_pedidos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pedido_id')->nullable();
            $table->integer('stone_cadastro_maquininha_id');
            $table->text('erro_msg');
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('stone_cadastro_maquininha_id')
                ->references('id')
                ->on('stone_cadastro_maquininhas')
                ->onDelete('NO ACTION');
            $table->foreign('pedido_id')
                ->references('id')
                ->on('pedido')
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

        Schema::table('stone_transacoes_pedidos', function (Blueprint $table) {
            $table->text('token')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stone_erro_transacoes_pedidos');
        Schema::table('stone_transacoes_pedidos', function (Blueprint $table) {
            $table->dropColumn('token');
        });
    }
}
