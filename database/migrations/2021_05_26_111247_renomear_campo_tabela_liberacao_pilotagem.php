<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenomearCampoTabelaLiberacaoPilotagem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('liberacao_pilotagems', function (Blueprint $table) {
            $table->renameColumn('titulo_data_emissaoo', 'titulo_data_emissao');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('liberacao_pilotagems', function (Blueprint $table) {
            $table->renameColumn('titulo_data_emissao','titulo_data_emissaoo');
        });
    }
}
