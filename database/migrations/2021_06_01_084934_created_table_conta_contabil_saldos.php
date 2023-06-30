<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableContaContabilSaldos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conta_contabil_saldos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('conta_classificacao')->nullable();
            $table->string('conta');
            $table->string('conta_nome')->nullable();
            $table->float('movimentacao_antes_do_encerramento')->nullable();
            $table->float('movimentacao')->nullable();
            $table->float('saldo_antes_do_encerramento')->nullable();
            $table->float('saldo')->nullable();
            $table->string('ano_mes');
            $table->integer('ano');
            $table->integer('mes');
            $table->date('data');
            $table->string('empresa');
            $table->string('empresa_razao_social');
            $table->string('empresa_cnpj');
            $table->string('estabelecimento_codigo');
            $table->string('estabelecimento_nome');
            $table->string('estabelecimento_cnpj');
            $table->integer('nivel');
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
        Schema::dropIfExists('conta_contabil_saldos');
    }
}
