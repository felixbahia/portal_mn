<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableCenprotTitulosUltimoStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cenprot_titulos', function (Blueprint $table) {
            $table->string('cenprot_status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cenprot_titulos', function (Blueprint $table) {
            $table->dropColumn('cenprot_status');
        });
    }
}
