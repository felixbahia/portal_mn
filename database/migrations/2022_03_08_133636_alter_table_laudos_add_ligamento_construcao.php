<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableLaudosAddLigamentoConstrucao extends Migration
{  /**
    * Run the migrations.
    *
    * @return void
    */
   public function up()
   {
       Schema::table('laudos', function (Blueprint $table) {
           $table->string('ligamento',10)->nullable();
           $table->string('construcao',20)->nullable();
          
       });
   }

   /**
    * Reverse the migrations.
    *
    * @return void
    */
   public function down()
   {
       Schema::table('laudos', function (Blueprint $table) {
           $table->dropColumn('ligamento');
           $table->dropColumn('construcao');
       });
   }
}
