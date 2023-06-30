<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAliquotaPrecoClienteIsento extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aliquota_precos', function ($table) {
            $table->float('icms_venda_cliente_isento')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('aliquota_precos', function ($table) {
            $table->dropColumn('icms_venda_cliente_isento');
        });
    }
}
