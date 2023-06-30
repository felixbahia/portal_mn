<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableVendasSantistas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('vendas_santistas', function (Blueprint $table) {
            $table->string('cliente_cnpj')->nullable()->change();
            $table->string('representante_codigo')->nullable()->change();
            $table->boolean('confirmado');
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
        Schema::table('vendas_santistas', function (Blueprint $table) {
            $table->string('cliente_cnpj')->nullable(false)->change();
            $table->string('representante_codigo')->nullable(false)->change();
            $table->dropColumn('confirmado');
        });        
    }
}
