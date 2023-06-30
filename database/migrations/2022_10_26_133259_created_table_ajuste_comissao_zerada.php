<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableAjusteComissaoZerada extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comissao_ajuste_zeradas', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('nota_id');
            $table->uuid('vendedor_id');
            $table->float('comissao_anterior');
            $table->float('comissao_atual');
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
        Schema::dropIfExists('comissao_ajuste_zeradas');
    }
}
