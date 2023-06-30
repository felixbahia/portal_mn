<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InserirCamposTabelaProdutoGrupos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('produto_grupos', function (Blueprint $table) {
            $table->boolean('status')->default(false)->nullable();
            $table->float('encolhimento')->nullable();
            $table->string('ligamento')->nullable();
            $table->string('titulo_trama',30)->nullable();
            $table->string('titulo_urdume',30)->nullable();
            $table->string('informacao_adicional')->nullable();
            $table->string('nome_arquivo')->nullable();
            $table->string('caminho')->nullable();
            $table->dropColumn('gramatura_gml');
            $table->string('construcao')->nullable();
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
            $table->dropColumn('encolhimento');
            $table->dropColumn('titulo_trama');
            $table->dropColumn('titulo_urdume');
            $table->dropColumn('informacao_adicional');
            $table->dropColumn('ligamento');
            $table->dropColumn('nome_arquivo');
            $table->dropColumn('caminho');
            $table->dropColumn('status');
            $table->string('gramatura_gml');
            $table->dropColumn('construcao');
        });
    }
}
