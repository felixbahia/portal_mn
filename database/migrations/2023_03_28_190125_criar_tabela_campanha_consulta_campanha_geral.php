<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaCampanhaConsultaCampanhaGeral extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campanhas_consulta_meta_vendedores', function (Blueprint $table) {
            $table->increments('id');
            $table->string('metros')->nullable();
            $table->string('valor')->nullable();
            $table->string('devolucao')->nullable();
            $table->string('kilo')->nullable();
            $table->string('logo')->nullable();
            $table->string('periodo')->nullable();
            $table->string('unidade')->nullable();
            $table->string('metros_medida')->nullable();
            $table->integer('campanhas_consulta_meta_vendedores_periodo_id')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('campanhas_consulta_meta_vendedores_periodo_id')
                ->references('id')
                ->on('campanhas_consulta_meta_vendedores_periodos')
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
        Schema::dropIfExists('campanhas_consulta_meta_vendedores');
    }
}
