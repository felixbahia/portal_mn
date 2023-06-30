<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InserirCampoTabelaBookVirtuals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('books_virtuals', function (Blueprint $table) {
            $table->integer('familias_id')->nullable();

            $table->foreign('familias_id')
                ->references('id')
                ->on('familias')
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
        Schema::table('books_virtuals', function (Blueprint $table) {
            $table->dropColumn('familias_id');
        });
    }
}
