<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CieloAutenticacoes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cielo_autenticaos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('estabelecimento', 2);
            $table->string('client_id')->nullable();
            $table->string('merchant_id')->nullable();
            $table->string('access_token')->nullable();
            $table->boolean('lio')->default(false);
            $table->boolean('ecommerce')->default(false);
            $table->boolean('sandbox')->default(false);
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
        Schema::dropIfExists('cielo_autenticaos');
    }
}
