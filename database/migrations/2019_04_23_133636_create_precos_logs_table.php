<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePrecosLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('precos_logs', function (Blueprint $table) {
            $table->increments('id');

            $table->string('codigo_produto');
            $table->float('preco_real_antigo')->nullable();
            $table->float('preco_dolar_antigo')->nullable();
            $table->float('preco_real_novo');
            $table->float('preco_dolar_novo');
            
            $table->integer('created_by');
            $table->timestamps();

            $table->foreign('created_by')
            ->references('id')
            ->on('users')
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
        Schema::dropIfExists('precos_logs');
    }
}
