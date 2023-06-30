<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableNecessidadeComprasXProjeto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('necessidades_compras_x_projetos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('necessidades_compras_id');
            $table->integer('lancamento_projetos_id');
            $table->integer('lancamento_projeto_produtos_id')->nullable();
            $table->integer('lancamento_projeto_tecidos_id')->nullable();
            $table->integer('lancamento_projeto_insumos_id')->nullable();
            $table->integer('lancamento_projeto_faccoes_id')->nullable();
            $table->string('tipo');

            $table->softDeletes();
            $table->timestamps();

            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();

            $table->foreign('necessidades_compras_id')
                ->references('id')
                ->on('necessidades_compras')
                ->onDelete('NO ACTION');
            $table->foreign('lancamento_projetos_id')
                ->references('id')
                ->on('lancamento_projetos')
                ->onDelete('NO ACTION');
            $table->foreign('lancamento_projeto_produtos_id')
                ->references('id')
                ->on('lancamento_projeto_produtos')
                ->onDelete('NO ACTION');
            $table->foreign('lancamento_projeto_tecidos_id')
                ->references('id')
                ->on('lancamento_projeto_tecidos')
                ->onDelete('NO ACTION');
            $table->foreign('lancamento_projeto_insumos_id')
                ->references('id')
                ->on('lancamento_projeto_insumos')
                ->onDelete('NO ACTION');
            $table->foreign('lancamento_projeto_faccoes_id')
                ->references('id')
                ->on('lancamento_projeto_faccoes')
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
        Schema::dropIfExists('necessidades_compras_x_projetos');
    }
}
