<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableLaudosAddFichaTecnica extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('laudos', function (Blueprint $table) {
            $table->boolean('status')->nullable();
            $table->float('gramatura_linear')->nullable();
            $table->float('rendimento')->nullable();
            $table->float('encolhimento')->nullable();
            $table->string('titulo_trama',30)->nullable();
            $table->string('titulo_urdume',30)->nullable();
            $table->string('informacao_adicional',255)->nullable();
            $table->string('nome_arquivo')->nullable();
            $table->string('caminho')->nullable();
           $table->string('caracteristicas')->nullable()->change();
            $table->string('tamanho_pecas')->nullable()->change();
            $table->string('origem')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('laudos', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('gramatura_linear');
            $table->dropColumn('rendimento');
            $table->dropColumn('encolhimento');
            $table->dropColumn('titulo_trama');
            $table->dropColumn('titulo_urdume');
            $table->dropColumn('informacao_adicional');
            $table->dropColumn('nome_arquivo');
            $table->dropColumn('caminho');
            $table->string('caracteristicas')->change();
            $table->string('tamanho_pecas')->change();
            $table->string('origem')->change();

        });
    }
}
