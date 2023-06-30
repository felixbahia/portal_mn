<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePrecosAddUnidae extends Migration
{
    public function up()
    {
        Schema::table('precos', function ($table) {
            $table->string('unidade')->nullable();
        });
    }

    public function down()
    {
        Schema::table('precos', function ($table) {
            $table->$table->dropColumn('unidade');
        });
    }
}
