<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableImportacaoEmbarque extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('importacao_embarques', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('importacaos_id');
            $table->string('porto_origem')->nullable();
            $table->string('porto_destino')->nullable();
            $table->string('agente_compra')->nullable();
            $table->string('armador')->nullable();
            $table->boolean('etd_booking')->nullable();
            $table->boolean('eta_booking')->nullable();
            $table->string('numero_bl')->nullable();
            $table->date('transit_time_chegada')->nullable();
            $table->date('transit_time_saida')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('importacaos_id')
                ->references('id')
                ->on('importacaos')
                ->onDelete('NO ACTION');
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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('importacao_embarques');
    }
}
