<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MensagemTipoUsuario extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('mensagem_perfils');
        Schema::create('mensagem_tipo_usuarios', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('mensagem_id');
            $table->integer('tipo_usuario_id');
            
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('mensagem_id')
                ->references('id')
                ->on('mensagems')
                ->onDelete('NO ACTION');

            $table->foreign('tipo_usuario_id')
                ->references('id')
                ->on('tipo_usuarios')
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
        Schema::create('mensagem_perfils', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('mensagems_id');
            $table->integer('roles_id');
            
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('mensagems_id')
                ->references('id')
                ->on('mensagems')
                ->onDelete('NO ACTION');

            $table->foreign('roles_id')
                ->references('id')
                ->on('roles')
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
        Schema::dropIfExists('mensagem_tipo_usuarios');
    }
}
