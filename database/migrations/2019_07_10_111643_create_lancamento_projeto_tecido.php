<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLancamentoProjetoTecido extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //relação nome_da_tabela_campo
        Schema::create('lancamento_projeto_tecidos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('lancamento_projetos_id');
            $table->integer('codigo_estabelecimento');
            $table->string('codigo_produto');
            $table->float('consumo_unitario');
            $table->float('quantidade');
            $table->float('consumo_total');
            $table->float('custo_unitario');
            $table->float('valor_total');
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lancamento_projetos_id')
                ->references('id')
                ->on('lancamento_projetos')
                ->onDelete('NO ACTION');
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
        Schema::dropIfExists('lancamento_projeto_tecidos');
    }
}
