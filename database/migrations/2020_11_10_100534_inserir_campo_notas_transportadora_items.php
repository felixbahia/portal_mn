<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InserirCampoNotasTransportadoraItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notas_transportadora_items', function (Blueprint $table) {
            $table->string('emissor_cnpj')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notas_transportadora_items', function (Blueprint $table) {
            $table->dropColumn('emissor_cnpj');
        });
    }
}