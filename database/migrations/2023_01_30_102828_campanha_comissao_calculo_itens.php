<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CampanhaComissaoCalculoItens extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campanhas_comissao_calculo_itens', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('campanha_comissao_calculo_id')->nullable();
            $table->integer('campanha_id')->nullable();
            $table->string('produto_codigo')->nullable();
            $table->float('comissao_padrao')->nullable();
            $table->float('comissao_incentivo_vendedor_interno')->nullable();
            $table->float('comissao_incentivo_gerente')->nullable();
            $table->float('comissao_incentivo_representante')->nullable();
            $table->float('comissao_incentivo_vendedor_interno_tipo')->nullable();
            $table->float('comissao_incentivo_gerente_tipo')->nullable();
            $table->float('comissao_incentivo_representante_tipo')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('campanha_id')
            ->references('id')
            ->on('campanhas')
            ->onDelete('NO ACTION');
            $table->foreign('campanha_comissao_calculo_id')
            ->references('id')
            ->on('campanhas_comissao_calculos')
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
        Schema::dropIfExists('campanhas_comissao_calculo_itens');
    }
}
