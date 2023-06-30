<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableRenameMetasXRepresentantes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('unidade_negocio_metas_x_representantes', 'unidade_negocio_metas_x_users');
        Schema::table('unidade_negocio_metas_x_users', function ($table) {
            $table->float('metas')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('unidade_negocio_metas_x_users', function ($table) {
            $table->dropColumn('metas');
        });
        Schema::rename('unidade_negocio_metas_x_users', 'unidade_negocio_metas_x_representantes');
    }
}
