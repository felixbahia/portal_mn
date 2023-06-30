<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUserAddCollumComissao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function ($table) {
            $table->double('comissao_a', 5, 2)->nullable(true);
            $table->double('comissao_b', 5, 2)->nullable(true);
            $table->double('comissao_c', 5, 2)->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function ($table) {
            $table->dropColumn('comissao_a');
            $table->dropColumn('comissao_b');
            $table->dropColumn('comissao_c');
        });
    }
}
