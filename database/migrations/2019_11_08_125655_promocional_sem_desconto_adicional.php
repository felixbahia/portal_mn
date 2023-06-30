<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PromocionalSemDescontoAdicional extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('produtos_promocionais', function (Blueprint $table) {
            $table->boolean('sem_desconto_adicional')->default(false)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('produtos_promocionais', function (Blueprint $table) {
            $table->dropColumn('sem_desconto_adicional');
        });
    }
}
