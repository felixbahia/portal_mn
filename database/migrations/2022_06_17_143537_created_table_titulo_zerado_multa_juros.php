<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableTituloZeradoMultaJuros extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('titulo_zerado_multa_juros', function (Blueprint $table) {
            $table->increments('id');
            $table->string('titulo_numero')->nullable();
            $table->uuid('titulo_uuid')->nullable();
            $table->date('titulo_vencimento')->nullable();
            $table->float('percentualjurosdiario')->nullable();
            $table->float('juros')->nullable();
            $table->float('multa')->nullable();
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
        Schema::dropIfExists('titulo_zerado_multa_juros');
    }
}
