<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableSugestaoComprasCodigoNulo extends Migration
{
    public function up()
    {
        Schema::table('sugestao_compras', function (Blueprint $table) {
            $table->string('produto_codigo',60)->nullable()->change();
            $table->string('cliente_codigo',30)->nullable()->change();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sugestao_compras', function (Blueprint $table) {
            $table->string('produto_codigo',60)->change();
            $table->string('cliente_codigo',30)->change();

        });
    }
}
