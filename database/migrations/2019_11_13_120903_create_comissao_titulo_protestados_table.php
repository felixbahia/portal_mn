<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateComissaoTituloProtestadosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comissao_titulo_protestados', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('nota_id')->nullable();
            $table->float('saldo');
            $table->date('titulo_emissao');
            $table->date('vencimento');
            $table->integer('parcela');
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
        Schema::dropIfExists('comissao_titulo_protestados');
    }
}
