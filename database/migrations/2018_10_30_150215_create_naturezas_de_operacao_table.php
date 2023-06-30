<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNaturezasDeOperacaoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('naturezas_de_operacao', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('estabelecimento');
            $table->string('estado_destino');
            $table->string('nat_op_pj');
            $table->string('nat_op_pf');
            $table->integer('created_by');
            $table->integer('modified_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by', 'fk_natureza_de_operacaos_created_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('modified_by', 'fk_natureza_de_operacaos_modified_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('naturezas_de_operacao');
    }
}
