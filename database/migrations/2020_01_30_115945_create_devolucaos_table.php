<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDevolucaosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devolucoes_notas', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('nota_id');
            $table->string('estabelecimento');
            $table->string('nota_fiscal');
            $table->string('serie');
            $table->string('cliente_cpf_cnpj');
            $table->string('cliente_razao_social');
            $table->float('valor');
            $table->boolean('valor_parcial')->default(false);
            $table->string('motivo');
            $table->string('status');
            $table->string('motivo_reprovacao')->nullable();
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

            $table->unique(['nota_id', 'deleted_at']);
            $table->index(['nota_fiscal']);


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('devolucoes_notas');
    }
}
