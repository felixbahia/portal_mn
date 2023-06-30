<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableImportacaoFinanceiroLancamento extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('importacao_financeiro_lancamentos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('importacao_financeiros_id');
            $table->date('cambio_data')->nullable();
            $table->float('cambio_valor')->nullable();
            $table->float('real_taxa')->nullable();
            $table->float('real_valor')->nullable();
            $table->string('banco')->nullable();
            $table->string('fechamento_tipo')->nullable();
            $table->string('cambio_numero_contrato')->nullable();
            $table->string('banco_numero_contrato')->nullable();
            $table->string('modalidade')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('importacao_financeiros_id')
                ->references('id')
                ->on('importacao_financeiros')
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
        Schema::dropIfExists('importacao_financeiro_lancamentos');
    }
}
