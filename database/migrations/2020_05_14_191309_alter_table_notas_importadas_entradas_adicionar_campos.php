<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableNotasImportadasEntradasAdicionarCampos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notas_importadas_entradas', function (Blueprint $table) {
            $table->string('pagamento_tipo')->nullable();
            $table->float('pagamento_valor')->nullable();
            $table->float('valor_imposto_importacao')->nullable();
            $table->float('valor_pis')->nullable();
            $table->float('valor_cofins')->nullable();
            $table->float('valor_total_tributos')->nullable();
            $table->float('valor_etc')->nullable();
            $table->float('unidade_base_calculo',5)->nullable();
            $table->float('unidade_etc',5)->nullable();
            $table->string('expedidor_nome')->nullable();
            $table->string('expedidor_cnpj')->nullable();
            $table->string('destinatario_nome')->nullable();
            $table->string('destinatario_cnpj')->nullable();
            $table->string('natureza_operacao')->nullable();
            $table->string('produto_predominante')->nullable();
            $table->string('caracteristica_carga')->nullable();
            $table->float('peso_declarado')->nullable();
            $table->string('unidade_peso_declarado',5)->nullable();
            $table->float('peso_real')->nullable();
            $table->string('unidade_peso_real',5)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notas_importadas_entradas', function (Blueprint $table) {
            $table->dropColumn('pagamento_tipo');
            $table->dropColumn('pagamento_valor');
            $table->dropColumn('valor_imposto_importacao');
            $table->dropColumn('valor_pis');
            $table->dropColumn('valor_cofins');
            $table->dropColumn('valor_total_tributos');
            $table->dropColumn('valor_etc');
            $table->dropColumn('unidade_base_calculo');
            $table->dropColumn('unidade_etc');
            $table->dropColumn('expedidor_nome');
            $table->dropColumn('expedidor_cnpj');
            $table->dropColumn('destinatario_nome');
            $table->dropColumn('destinatario_cnpj');
            $table->dropColumn('natureza_operacao');
            $table->dropColumn('produto_predominante');
            $table->dropColumn('caracteristica_carga');
            $table->dropColumn('peso_declarado');
            $table->dropColumn('unidade_peso_declarado');
            $table->dropColumn('peso_real');
            $table->dropColumn('unidade_peso_real');
        });
    }
}
