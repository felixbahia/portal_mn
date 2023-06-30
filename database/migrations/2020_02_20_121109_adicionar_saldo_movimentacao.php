<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdicionarSaldoMovimentacao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movimentacao', function ($table) {
            $table->float('custo_sem_imposto')->nullable();
            $table->float('saldo_movimentos')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('movimentacao', function ($table) {
            $table->dropColumn('custo_sem_imposto');
            $table->dropColumn('saldo_movimentos');
        });
    }
}
