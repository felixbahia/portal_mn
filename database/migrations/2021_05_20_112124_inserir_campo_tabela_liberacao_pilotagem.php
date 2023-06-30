<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InserirCampoTabelaLiberacaoPilotagem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('liberacao_pilotagems', function (Blueprint $table) {
            $table->integer('aprovador_id')->nullable();
           
            $table->foreign('aprovador_id')
                ->references('id')
                ->on('users')
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
        Schema::table('liberacao_pilotagems', function (Blueprint $table) {
            $table->dropColumn('aprovador_id');
        });
    }
}
