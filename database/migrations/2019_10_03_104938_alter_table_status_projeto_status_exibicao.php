<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableStatusProjetoStatusExibicao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('status_projeto', function ($table) {
            $table->integer('status_projeto_exibicao_id')->nullable();

            $table->foreign('status_projeto_exibicao_id')
                ->references('id')
                ->on('status_projeto_exibicao')
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
        Schema::table('status_projeto', function ($table) {
            $table->dropColumn('status_projeto_exibicao_id');
        });
    }
}
