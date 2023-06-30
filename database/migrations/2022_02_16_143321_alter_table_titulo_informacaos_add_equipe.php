<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableTituloInformacaosAddEquipe extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('titulo_informacaos', function (Blueprint $table) {
            $table->string('equipe')->nullable();
            $table->integer('unidades_negocios_id')->nullable();

            $table->foreign('unidades_negocios_id')
                ->references('id')
                ->on('unidades_negocios')
                ->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('titulo_informacaos', function (Blueprint $table) {
            $table->dropColumn('equipe');
            $table->dropColumn('unidades_negocios_id');
        });
    }
}
