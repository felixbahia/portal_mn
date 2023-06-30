<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePrecosLogCompraReal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('precos_logs', function (Blueprint $table) {
            $table->decimal('compra_real_antigo')->nullable();
            $table->decimal('compra_real_novo')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('precos_logs', function (Blueprint $table) {
            $table->dropColumn('compra_real_antigo');
            $table->dropColumn('compra_real_novo');
        });
    }
}
