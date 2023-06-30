<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableTituloAPagarAddNota extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('titulo_a_pagar', function (Blueprint $table) {
            $table->string('nota_numero')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('titulo_a_pagar', function (Blueprint $table) {
            $table->dropColumn('nota_numero');
        });
    }
}
