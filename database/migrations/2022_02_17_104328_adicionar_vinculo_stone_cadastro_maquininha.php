<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdicionarVinculoStoneCadastroMaquininha extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stone_cadastro_maquininhas', function (Blueprint $table) {
            $table->string('serial_number')->nullable();
        });

        Schema::table('stone_configuracao_maquininhas', function (Blueprint $table) {
            $table->string('vinculo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stone_cadastro_maquininhas', function (Blueprint $table) {
            $table->dropColumn('serial_number');
        });

        Schema::table('stone_configuracao_maquininhas', function (Blueprint $table) {
            $table->dropColumn('vinculo');
        });
    }
}
