<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableDevolucaoNotaTituloCancelado extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devolucao_nota_titulo_cancelados', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('devolucao_notas_id');

            $table->uuid('titulo_uuid_nasajon');
            $table->string('titulo_numero');
            $table->float('titulo_valor');
            $table->integer('parcela');
            $table->date('data_emissao');
            $table->date('data_vencimento');

            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('devolucao_notas_id')
                ->references('id')
                ->on('devolucao_notas')
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
        Schema::dropIfExists('devolucao_nota_titulo_cancelados');
    }
}