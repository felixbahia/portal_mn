<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class VendasSantistaLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vendas_santista_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ip')->nullable();
            $table->string('nota');
            $table->string('cnpj');
            $table->string('resultado');
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vendas_santista_logs');
    }
}
