<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableBookVirtualAddGeneroGramaturaComposicaoConstrucaoSazonal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('books_virtuals', function (Blueprint $table) {
            $table->string('genero', 20)->nullable();
            $table->string('gramatura_top', 20)->nullable();
            $table->string('gramatura_bottom', 20)->nullable();
            $table->string('gramatura_all_over', 20)->nullable();
            $table->integer('composicaos_id')->nullable();
            $table->integer('construcaos_id')->nullable();
            $table->integer('sazonalidades_id')->nullable();

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
        Schema::table('books_virtuals', function (Blueprint $table) {
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
