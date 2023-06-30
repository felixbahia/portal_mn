<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaCampanhaConsultaCampanhaGeralPeriodos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campanhas_consulta_meta_vendedores_periodos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('campanha_id');  
            $table->integer('campanhas_apuracao_comissoe_id')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('campanhas_apuracao_comissoe_id')
                ->references('id')
                ->on('campanhas_apuracao_comissoes')
                ->onDelete('NO ACTION');
            $table->foreign('campanha_id')
                ->references('id')
                ->on('campanhas')
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
        Schema::dropIfExists('campanhas_consulta_meta_vendedores_periodos');
    }
}
