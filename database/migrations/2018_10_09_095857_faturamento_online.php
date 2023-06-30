<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FaturamentoOnline extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('faturamento_online', function (Blueprint $table) {
            $table->increments('id');
            $table->string('estabelecimento', 2);
            $table->string('numero_documento', 6);
            $table->string('codigo_cadastro', 13);
            $table->string('tipo_operacao', 3);
            $table->string('codigo_vendedor', 6);
            $table->dateTime('data');
            $table->decimal('valor_compra', 17, 4);
            $table->decimal('valor_frete', 17, 4);
            $table->decimal('valor_ipi', 17, 4);
            $table->decimal('valor_troco', 17, 4);
            $table->string('chave_validacao', 100);
            $table->string('tabela_data', 100);
            $table->string('numero_nota', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('faturamento_online');
    }
}
