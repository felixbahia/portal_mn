<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterProdutoNovoCampoNovos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('produto_novos', function ($table) {
            $table->string('ncm')->nullable();
            $table->float('peso')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('produto_novos', function ($table) {
            $table->dropColumn('ncm');
            $table->dropColumn('peso');
        });
    }
}
