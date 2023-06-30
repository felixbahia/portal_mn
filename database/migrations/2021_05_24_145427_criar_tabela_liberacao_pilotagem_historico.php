<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaLiberacaoPilotagemHistorico extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('liberacao_pilotagem_historicos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('liberacao_pilotagems_id');
            $table->string('estado');
            $table->integer('users_id');
            $table->timestamps();

            $table->foreign('liberacao_pilotagems_id')
                ->references('id')
                ->on('liberacao_pilotagems')
                ->onDelete('NO ACTION');
            $table->foreign('users_id')
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
        Schema::dropIfExists('liberacao_pilotagem_historicos');
    }
}
