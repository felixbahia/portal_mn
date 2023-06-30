<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdicionarCamposTabelaProdutoGrupos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('produto_grupos', function (Blueprint $table) {
            $table->string('largura')->nullable();
            $table->string('gramatura_tipo')->nullable();
            $table->string('gramatura_gm2')->nullable();
            $table->string('gramatura_gml')->nullable();
            $table->string('rendimento')->nullable();
            $table->string('tipo_genero')->nullable();
            $table->integer('segmentos_id')->nullable();
            $table->integer('familias_id')->nullable();
            $table->string('padrao_codigo')->nullable();
            $table->string('caracteristicas')->nullable();
            $table->string('pecas')->nullable();
            $table->string('origem')->nullable();
            $table->integer('composicaos_id')->nullable();
            $table->integer('construcaos_id')->nullable();
            $table->integer('sazonalidades_id')->nullable();
            $table->string('imagem')->nullable();

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
        Schema::table('produto_grupos', function (Blueprint $table) {
            $table->dropColumn('unidade');
            $table->dropColumn('procedencia');
            $table->dropColumn('gramatura_tipo');
            $table->dropColumn('gramatura_gm2');
            $table->dropColumn('gramatura_gml');
            $table->dropColumn('rendimento');
            $table->dropColumn('genero');
            $table->dropColumn('segmento_id');
            $table->dropColumn('familia_id');
            $table->dropColumn('padrao_codigo');
            $table->dropColumn('caracteristicas');
            $table->dropColumn('pecas');
            $table->dropColumn('origem');
            $table->dropColumn('composicao_id');
            $table->dropColumn('construcao_id');
            $table->dropColumn('sazonalidades_id');
        });
    }
}
