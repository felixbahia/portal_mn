<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdicionarCamposClientesNovos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cliente_novos', function ($table) {
            $table->string('codigo_gerado')->nullable(true);
            $table->string('atividade', 4)->nullable(true)->default('25');
            $table->string('indicador_juros', 1)->nullable(true)->default("S");
            $table->unsignedInteger('conceitos_clientes_id')->nullable(true);
            $table->foreign('conceitos_clientes_id')
                ->references('id')
                ->on('conceitos_clientes')
                ->onDelete('NO ACTION');
                
            $table->unsignedInteger('status')->change();
            $table->foreign('status')
                ->references('id')
                ->on('status_cliente_novos')
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
        Schema::table('cliente_novos', function ($table) {
            $table->dropColumn('codigo_gerado');
            $table->dropColumn('atividade');
            $table->dropColumn('indicador_juros');
            $table->dropForeign(['conceitos_clientes_id']);
            $table->dropColumn('conceitos_clientes_id');
        });
    }
}
