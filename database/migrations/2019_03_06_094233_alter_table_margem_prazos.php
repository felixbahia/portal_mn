<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableMargemPrazos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('margem_prazos', function ($table) {
            $table->float('prazo_90')->nullable();
            $table->dropColumn('comissao_a', 'comissao_b', 'comissao_c');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('margem_prazos', function ($table) {
            $table->float('comissao_a')->nullable();
            $table->float('comissao_b')->nullable();
            $table->float('comissao_c')->nullable();
            $table->dropColumn('prazo_90');
        });
    }
}
