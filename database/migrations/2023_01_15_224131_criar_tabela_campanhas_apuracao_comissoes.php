<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaCampanhasApuracaoComissoes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campanhas_apuracao_comissoes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('campanha_id');  
            $table->integer('campanhas_apuracao_comissoe_tipo_id');  
            $table->dateTime('inicio_periodo');
            $table->dateTime('fim_periodo');
            $table->float('meta_reais')->nullable();
            $table->float('meta_metros')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('campanhas_apuracao_comissoe_tipo_id')
                ->references('id')
                ->on('campanhas_apuracao_tipos')
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
        Schema::dropIfExists('campanhas_apuracao_comissoes');
    }
}
