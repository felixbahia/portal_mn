<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeingKeysToClientesVencimentosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clientes_vencimentos', function (Blueprint $table) {
            $table->foreign('condicao_id', 'fk_clientes_vencimentos_condicao_id')->references('id')->on('condicoes_pagamento_web')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('created_by', 'fk_clientes_vencimentos_created_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('modified_by', 'fk_clientes_vencimentos_modified_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clientes_vencimentos', function (Blueprint $table) {
            $table->dropForeign('fk_clientes_vencimentos_condicao_id');
            $table->dropForeign('fk_clientes_vencimentos_created_by');
            $table->dropForeign('fk_clientes_vencimentos_modified_by');
        });
    }
}
