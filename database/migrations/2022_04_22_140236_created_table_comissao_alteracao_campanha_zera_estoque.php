<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableComissaoAlteracaoCampanhaZeraEstoque extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comissao_alterada_campanha_zera_estoque', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nota_uuid');
            $table->string('nota_numero');
            $table->float('comissao_anterior')->nullable();
            $table->float('comissao_atual')->nullable();
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
        Schema::dropIfExists('comissao_alterada_campanha_zera_estoque');
    }
}
