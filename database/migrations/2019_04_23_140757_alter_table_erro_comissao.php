<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableErroComissao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('erro_comissaos', function ($table) {
            $table->integer('batch')->default(1);
        });

        Schema::table('erro_comissaos', function ($table) {
            $table->dropUnique('erro_comissaos_id_unique');
            $table->integer('batch')->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('erro_comissaos', function ($table) {
            $table->dropColumn('batch');        
        });

    }
}
