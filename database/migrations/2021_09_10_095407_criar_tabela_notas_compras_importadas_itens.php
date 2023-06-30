<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaNotasComprasImportadasItens extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notas_importadas_compras_itens', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('nota_id')->nullable();
            $table->integer('notas_importadas_compras_id')->nullable();
            $table->string('produto_codigo')->nullable();
            $table->string('produto_descricao')->nullable();
            $table->string('ncm',30)->nullable();
            $table->string('cfop',15)->nullable();
            $table->string('unidade',10)->nullable();
            $table->float('quantidade')->nullable();
            $table->float('valor_unitario')->nullable();
            $table->float('valor_total')->nullable();
            $table->float('icms_aliquota')->nullable();
            $table->float('icms_base')->nullable();
            $table->float('icms_valor')->nullable();
            $table->float('icms_aliquota_st')->nullable();
            $table->float('icms_base_st')->nullable();
            $table->float('icms_valor_st')->nullable();
            $table->float('ipi_aliquota')->nullable();
            $table->float('ipi_base')->nullable();
            $table->float('ipi_valor')->nullable();
            $table->timestamps();

            $table->foreign('notas_importadas_compras_id')
                ->references('id')
                ->on('notas_importadas_compras')
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
        Schema::dropIfExists('notas_importadas_compras_itens');
    }
}
