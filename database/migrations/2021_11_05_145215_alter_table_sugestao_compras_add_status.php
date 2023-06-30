<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableSugestaoComprasAddStatus extends Migration
{

    public function up()
    {
        Schema::table('sugestao_compras', function (Blueprint $table) {
            $table->integer('status')->nullable();
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
            $table->dropColumn('status');

        });
    }
}
