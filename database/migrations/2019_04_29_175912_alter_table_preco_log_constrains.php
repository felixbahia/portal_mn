<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePrecoLogConstrains extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('precos_logs', function ($table) {
            $table->float('preco_real_novo')->nullable()->change();
            $table->float('preco_dolar_novo')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('precos_logs', function ($table) {
            $table->float('preco_real_novo')->nullable(false)->change();
            $table->float('preco_dolar_novo')->nullable(false)->change();
        });
    }
}
