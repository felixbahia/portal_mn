<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableVideos extends Migration
{
     /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      

        Schema::table('videos', function (Blueprint $table) {
            $table->integer('modulos_id')->nullable();
            $table->foreign('modulos_id')
                ->references('id')
                ->on('modulos')
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


        Schema::table('videos', function (Blueprint $table) {
  
            $table->dropColumn('modulos_id');
 
       
        });
    }
}
