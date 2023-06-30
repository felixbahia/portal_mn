<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePreco extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('precos', function ($table) {
            $table->float('compra_real')->nullable();
            $table->float('compra_dolar')->nullable();
            $table->date('ultima_compra_real')->nullable();
            $table->date('ultima_compra_dolar')->nullable();
            $table->dropColumn('ultima_compra');
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('precos', function ($table) {
            $table->dropColumn('compra_real');
            $table->dropColumn('compra_dolar');
            $table->date('ultima_compra')->nullable();
            $table->boolean('status')->nullable();
        });
    }
}
