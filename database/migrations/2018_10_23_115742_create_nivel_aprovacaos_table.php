<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNivelAprovacaosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nivel_aprovacaos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('descricao');
            $table->unsignedInteger('tipo_usuario_id');
            $table->timestamps();
        });
        Schema::table('nivel_aprovacaos', function (Blueprint $table) {
            $table->foreign('tipo_usuario_id')
                ->references('id')
                ->on('tipo_usuarios')
                ->onDelete('NO ACTION');
        });
        Schema::table('aprovacao_de_pedidos', function (Blueprint $table) {
            $table->foreign('nivel_aprovacao')
                ->references('id')
                ->on('nivel_aprovacaos')
                ->onDelete('NO ACTION');
        });
        Schema::table('aprovacao_de_pedidos', function (Blueprint $table) {
            $table->foreign('pedido_id')
                ->references('id')
                ->on('pedido')
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
        Schema::table('nivel_aprovacaos', function (Blueprint $table) {
            $table->dropForeign(['tipo_usuario_id']);
        });
        Schema::table('aprovacao_de_pedidos', function (Blueprint $table) {
            $table->dropForeign(['nivel_aprovacao', 'pedido_id']);
        });
        Schema::dropIfExists('nivel_aprovacaos');
    }
}
