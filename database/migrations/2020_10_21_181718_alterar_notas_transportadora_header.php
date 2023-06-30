<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarNotasTransportadoraHeader extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notas_transportadora_headers', function (Blueprint $table) {
            $table->renameColumn('cnpj_transportadora','transportadora_cnpj');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notas_transportadora_headers', function (Blueprint $table) {
            $table->renameColumn('transportadora_cnpj', 'cnpj_transportadora');
        });
    }
}
