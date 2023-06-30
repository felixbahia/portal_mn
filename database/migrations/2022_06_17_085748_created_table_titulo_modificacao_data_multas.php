<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableTituloModificacaoDataMultas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('titulo_modificacao_data_multas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('titulo_numero')->nullable();
            $table->uuid('titulo_uuid')->nullable();
            $table->date('titulo_vencimento')->nullable();
            $table->date('titulo_multa')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('titulo_modificacao_data_multas');
    }
}
