<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDevolucaoNotaStatusFlagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devolucao_nota_status_flags', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('devolucao_nota_id');
            $table->integer('devolucao_nota_status_id');
            $table->integer('aprovador');
            $table->integer('created_by');
            $table->integer('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('devolucao_nota_id')
                ->references('id')
                ->on('devolucoes_notas')
                ->onDelete('NO ACTION');

            $table->foreign('devolucao_nota_status_id')
                ->references('id')
                ->on('devolucao_nota_status')
                ->onDelete('NO ACTION');
                
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');

            $table->foreign('deleted_by')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('devolucao_nota_status_flags');
    }
}
