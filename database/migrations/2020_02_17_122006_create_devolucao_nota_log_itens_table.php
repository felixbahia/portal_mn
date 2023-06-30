<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDevolucaoNotaLogItensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devolucao_nota_log_itens', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('devolucao_nota_log_id');
            $table->string('codigo_produto');
            $table->float('quantidade');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('devolucao_nota_log_id')
                ->references('id')
                ->on('devolucao_nota_logs')
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
        Schema::dropIfExists('devolucao_nota_log_itens');
    }
}
