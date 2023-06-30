<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFaccaoXTipoDeServico extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('faccao_x_tipo_de_servicos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('codigo_faccao');
            $table->integer('codigo_tipo_de_servico');
            $table->double('preco');
            $table->string('codigo_unidade', 6);
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('codigo_faccao')
            ->references('id')
            ->on('faccaos')
            ->onDelete('NO ACTION');

            $table->foreign('codigo_tipo_de_servico')
                ->references('id')
                ->on('tipo_de_servicos')
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
        Schema::dropIfExists('faccao_x_tipo_de_servicos');
    }
}
