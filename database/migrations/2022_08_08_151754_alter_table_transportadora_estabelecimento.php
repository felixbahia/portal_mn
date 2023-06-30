<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableTransportadoraEstabelecimento extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transportadora_estabelecimentos', function (Blueprint $table) {
      
            $table->renameColumn('transportadora_cnpj', 'transportadora_codigo');
       
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transportadora_estabelecimentos', function (Blueprint $table) {
      
            $table->renameColumn('transportadora_codigo', 'transportadora_cnpj');
       
        });
    }
}
