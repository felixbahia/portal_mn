<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableFichaTecnicaTabelaMedidasSoftDelete extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ficha_tecnica_tabela_medidas', function ($table) {
            $table->softDeletes();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->renameColumn('tolerancia_xgg', 'tolerancia');
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ficha_tecnica_tabela_medidas', function ($table) {
            $table->dropSoftDeletes();
            $table->dropColumn('created_by');
            $table->dropColumn('deleted_by');
            $table->renameColumn('tolerancia', 'tolerancia_xgg');

        });
    }
}
