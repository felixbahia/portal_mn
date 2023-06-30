<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableBooksVirtualsSegmentosId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('books_virtuals', function ($table) {
            $table->integer('segmentos_id')->nullable();

            $table->foreign('segmentos_id')
                ->references('id')
                ->on('segmentos')
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
        Schema::table('books_virtuals', function ($table) {
            $table->dropForeign(['segmentos_id']);
            $table->dropColumn('segmentos_id');    
        });
    }
}
