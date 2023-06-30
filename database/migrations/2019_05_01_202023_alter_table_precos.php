<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePrecos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('precos', function ($table) {
            $table->string('marca');
            $table->string('linha');
            $table->string('grupo');
            $table->string('subgrupo');
            $table->string('descricao');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('precos', function ($table) {
            $table->dropColumn('marca');
            $table->dropColumn('linha');
            $table->dropColumn('grupo');
            $table->dropColumn('subgrupo');
            $table->dropColumn('descricao');
        });
    }
}
