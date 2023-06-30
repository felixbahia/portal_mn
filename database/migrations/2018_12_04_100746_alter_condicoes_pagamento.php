<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCondicoesPagamento extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('condicoes_pagamento_web', function ($table) {
            $table->boolean('liberado_representante')->default(true);
        });

        Schema::table('condicoes_pagamento_web', function ($table) {
            $table->boolean('liberado_representante')->default(false)->change();
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('aprovacao_de_pedidos', function ($table) {
            $table->drop('liberado_representante');
        });        //
    }
}
