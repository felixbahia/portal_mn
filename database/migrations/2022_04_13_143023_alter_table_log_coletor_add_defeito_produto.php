<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableLogColetorAddDefeitoProduto extends Migration
{
    public function up()
    {
        Schema::table('log_coletors', function (Blueprint $table) {
            $table->integer('produto_defeito_id')->nullable();


            $table->foreign('produto_defeito_id')
            ->references('id')
            ->on('produto_defeitos')
            ->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_coletors', function (Blueprint $table) {
            $table->dropColumn('produto_defeito_id');

        });
    }
}
