<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableProdutoTecidoEstampado extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('produto_tecidos_estampados', function (Blueprint $table) {
            $table->increments('id');
            $table->string('produto_codigo_final', 60);
            $table->string('produto_codigo_desenho', 60);
            $table->integer('produto_tecidos_bases_id');

            $table->softDeletes();
            $table->timestamps();

            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();

            $table->foreign('produto_tecidos_bases_id')
                ->references('id')
                ->on('produto_tecidos_bases')
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

            $table->unique(['produto_codigo_final', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('produto_tecidos_estampados');
    }
}
