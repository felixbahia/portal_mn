<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaClicksignSignatarios extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clicksign_signatarios', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('signatario_clicksign_id')->nullable();
            $table->string('nome');
            $table->string('email');
            $table->string('cpf')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->json('json_retorno')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clicksign_signatarios');
    }
}
