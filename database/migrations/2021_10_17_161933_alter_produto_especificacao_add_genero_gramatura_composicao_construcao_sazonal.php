<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterProdutoEspecificacaoAddGeneroGramaturaComposicaoConstrucaoSazonal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('produto_especificacaos', function (Blueprint $table) {
            $table->string('origem')->nullable();
            $table->string('pecas')->nullable();
            $table->string('caracteristicas')->nullable();
            $table->integer('segmentos_id')->nullable();
            $table->string('tipo_material')->nullable();
            $table->string('padrao_codigo')->nullable();
            $table->integer('familias_id')->nullable();
            $table->string('genero', 20)->nullable();
            $table->string('gramatura_top', 20)->nullable();
            $table->string('gramatura_bottom', 20)->nullable();
            $table->string('gramatura_all_over', 20)->nullable();
            $table->integer('composicaos_id')->nullable();
            $table->integer('construcaos_id')->nullable();
            $table->integer('sazonalidades_id')->nullable();

            $table->foreign('segmentos_id')
                ->references('id')
                ->on('segmentos')
                ->onDelete('NO ACTION');
            $table->foreign('familias_id')
                ->references('id')
                ->on('familias')
                ->onDelete('NO ACTION');
            $table->foreign('composicaos_id')
                ->references('id')
                ->on('composicaos')
                ->onDelete('NO ACTION');
            $table->foreign('construcaos_id')
                ->references('id')
                ->on('construcaos')
                ->onDelete('NO ACTION');
            $table->foreign('sazonalidades_id')
                ->references('id')
                ->on('sazonalidades')
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
        Schema::table('produto_especificacaos', function (Blueprint $table) {
            $table->dropColumn('origem');
            $table->dropColumn('pecas');
            $table->dropColumn('caracteristicas');
            $table->dropColumn('segmentos_id');
            $table->dropColumn('tipo_material');
            $table->dropColumn('padrao_codigo');
            $table->dropColumn('familias_id');
            $table->dropColumn('genero');
            $table->dropColumn('gramatura_top');
            $table->dropColumn('gramatura_bottom');
            $table->dropColumn('gramatura_all_over');
            $table->dropColumn('composicaos_id');
            $table->dropColumn('construcaos_id');
            $table->dropColumn('sazonalidades_id');
        });
    }
}
