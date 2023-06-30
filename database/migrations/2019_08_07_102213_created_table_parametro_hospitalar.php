<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableParametroHospitalar extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parametro_hospitalar', function (Blueprint $table) {
            $table->increments('id');
            $table->string('margens');
            $table->float('mark_up');
            $table->float('frete');
            $table->float('desconto_pagamento_antecipado_0');
            $table->float('desconto_pagamento_antecipado_30');
            $table->float('desconto_pagamento_antecipado_60');
            $table->float('desconto_pagamento_antecipado_61');
            $table->float('desconto_inscricao_estadual');
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('updated_by')
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
        Schema::dropIfExists('parametro_hospitalar');
    }
}
