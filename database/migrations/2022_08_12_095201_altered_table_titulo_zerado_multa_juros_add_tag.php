<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlteredTableTituloZeradoMultaJurosAddTag extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('titulo_zerado_multa_juros', function (Blueprint $table) {
            $table->boolean('zera_juros');
            $table->boolean('zera_multa');     
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('titulo_zerado_multa_juros', function (Blueprint $table) {
            $table->dropColumn('zera_juros');
            $table->dropColumn('zera_multa');
        });
    }
}
