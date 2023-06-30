<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableImportacaoValorPadrao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('importacao_valor_padraos', function (Blueprint $table) {
            $table->increments('id');
            $table->float('dolar_referencia');
            $table->float('pis');
            $table->float('cofins');
            $table->float('capatazia');
            $table->float('taxa_siscomex');
            $table->float('sda');
            $table->float('honorarios');
            $table->float('expediente');
            $table->float('armazenagem');
            $table->float('laudo');
            $table->float('frete_rodoviario');
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
        Schema::dropIfExists('importacao_valor_padraos');
    }
}
