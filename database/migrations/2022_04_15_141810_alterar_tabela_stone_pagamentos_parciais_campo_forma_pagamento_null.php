<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarTabelaStonePagamentosParciaisCampoFormaPagamentoNull extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stone_pagamentos_parciais', function (Blueprint $table) {
            $table->string('forma_pagamento')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stone_pagamentos_parciais', function (Blueprint $table) {
            $table->string('forma_pagamento')->change();
        });
    }
}
