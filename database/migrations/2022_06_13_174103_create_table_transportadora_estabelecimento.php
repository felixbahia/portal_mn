<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableTransportadoraEstabelecimento extends Migration
{
        /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transportadora_estabelecimentos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('transportadora_cnpj',18);
            $table->string('transportadora_nome',255);
            $table->string('estabelecimento',2)->nullable();;
            $table->string('tipo_frete',10);
            $table->string('uf_origem',2);
            $table->string('uf_destino',2);
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

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transportadora_estabelecimentos');
    }
}