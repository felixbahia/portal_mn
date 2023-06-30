<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlteredTablesSaldoRed extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reds', function (Blueprint $table) {
            $table->float('saldo')->nullable();
        });

        Schema::table('importacao_financeiro_lancamentos', function (Blueprint $table) {
            $table->integer('reds_id')->nullable();
            $table->float('red_valor')->nullable();

            $table->foreign('reds_id')
                ->references('id')
                ->on('reds')
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
        Schema::table('reds', function (Blueprint $table) {
            $table->dropColumn('saldo');
        });

        Schema::table('importacao_financeiro_lancamentos', function (Blueprint $table) {
            $table->dropColumn('reds_id');
            $table->dropColumn('red_valor');
        });
    }
}
