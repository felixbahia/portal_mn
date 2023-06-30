<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDevolucaoNotaStatusUsuariosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devolucao_nota_status_usuarios', function (Blueprint $table) {
            $table->integer('devolucao_nota_status_id');
            $table->integer('user_id');

            $table->foreign('devolucao_nota_status_id')
                ->references('id')
                ->on('devolucao_nota_status')
                ->onDelete('NO ACTION');
                
            $table->foreign('user_id')
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
        Schema::dropIfExists('devolucao_nota_status_usuarios');
    }
}
