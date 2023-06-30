<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AtualizacaoCron extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('atualizacao_cron', function (Blueprint $table) {
            $table->increments('id');
            $table->string('token');
            $table->string('descricao');
            $table->dateTime('atualizacao');
            $table->longText('erro')->nullable();
            $table->boolean('alerta_erro')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('atualizacao_cron');
    }
}
