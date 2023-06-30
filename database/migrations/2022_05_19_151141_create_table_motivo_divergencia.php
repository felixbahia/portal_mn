<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableMotivoDivergencia extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('motivo_divergencias', function (Blueprint $table) {

            $table->increments('id');
            $table->string('descricao',30);
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')
            ->references('id')
            ->on('users')
            ->onDelete('NO ACTION');
        $table->foreign('updated_by')
            ->references('id')
            ->on('users')
            ->onDelete('NO ACTION');
        $table->foreign('deleted_by')
            ->references('id')
            ->on('users')
            ->onDelete('NO ACTION');

        });


  

        Schema::table('log_coletor_romaneios', function (Blueprint $table) {
 
            $table->integer('motivo_divergencia_id')->nullable();


        $table->foreign('motivo_divergencia_id')
            ->references('id')
            ->on('motivo_divergencias')
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
        Schema::dropIfExists('motivo_divergencias');
    }
}