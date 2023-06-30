<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarNotasTransportadoraItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notas_transportadora_itens', function (Blueprint $table) {
            $table->renameColumn('cnpj_destinatario','destinatario_cnpj');
            $table->renameColumn('cnpj_filial_transportadora','filial_transportadora_cnpj');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notas_transportadora_itens', function (Blueprint $table) {
            $table->renameColumn('destinatario_cnpj','cnpj_destinatario');
            $table->renameColumn('filial_transportadora_cnpj','cnpj_filial_transportadora');
        });
    }
}
