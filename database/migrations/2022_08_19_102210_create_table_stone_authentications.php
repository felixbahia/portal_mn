<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableStoneAuthentications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stone_authentications', function (Blueprint $table) {
            $table->increments('id');
            $table->string('estabelecimento_codigo',2);
            $table->string('stone_code');
            $table->string('client_id');
            $table->string('chave_secreta');
            $table->string('serial')->nullable();
            $table->boolean('producao')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stone_authentications');
    }
}
