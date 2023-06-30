<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableRedImportacaoFinanceiroLancamentos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reds_importacao_financeiro_lancamentos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('reds_id')->nullable();
            $table->integer('importacao_financeiro_lancamentos_id')->nullable();
            $table->float('utilizado_valor')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('reds_id')
                ->references('id')
                ->on('reds')
                ->onDelete('NO ACTION');
            $table->foreign('importacao_financeiro_lancamentos_id')
                ->references('id')
                ->on('importacao_financeiro_lancamentos')
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
        Schema::dropIfExists('reds_importacao_financeiro_lancamentos');
    }
}
