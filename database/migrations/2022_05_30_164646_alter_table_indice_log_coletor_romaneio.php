<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableIndiceLogColetorRomaneio extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('log_coletors', function (Blueprint $table){
            $table->index(['peca_codigo']);
            $table->index(['numero_nota']);
 

            

        });
        
            Schema::table('log_coletor_romaneios', function (Blueprint $table){
                $table->index(['peca_codigo']);
                $table->index(['numero_nota']);
            
    
                
    
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()


    {

        Schema::table('log_coletors', function (Blueprint $table){
            $table->dropIndex(['peca_codigo']);
            $table->dropIndex(['numero_nota']);
           

        });
        Schema::table('log_coletor_romaneios', function (Blueprint $table){
            $table->dropIndex(['peca_codigo']);
            $table->dropIndex(['numero_nota']);
     

        });
  
    }
}