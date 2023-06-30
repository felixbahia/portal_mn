<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarTabelaStonePagamentosParciaisRenomearColunaSerial extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stone_pagamentos_parciais', function (Blueprint $table) {
            $table->renameColumn('serial','id_pagamento_nasajon')->nullable();
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
            $table->renameColumn('id_pagamento_nasajon','serial')->nullable();
        });
    }
}
