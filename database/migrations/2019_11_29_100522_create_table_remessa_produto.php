<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableRemessaProduto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('remessa_produtos', function (Blueprint $table) {
            $table->increments('id');

            $table->string('estabelecimento_codigo', 2);
            $table->string('produto_codigo');

            $table->string('tipo');
            $table->string('pedido_compra_numero');
            $table->string('pedido_compra_numero_uuid');
            $table->string('operacao');
            $table->string('cfop');
            $table->float('preco');
            $table->float('quantidade_enviada');
            $table->float('quantidade_total');
            $table->date('data_envio');

            $table->integer('faccaos_id');
            $table->integer('lancamento_projetos_id');
            $table->integer('lancamento_projeto_tecidos_id')->nullable();
            $table->integer('lancamento_projeto_insumos_id')->nullable();
            $table->integer('lancamento_projeto_faccoes_id')->nullable();
            $table->integer('necessidades_compras_id')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('faccaos_id')
                ->references('id')
                ->on('faccaos')
                ->onDelete('NO ACTION');
            $table->foreign('lancamento_projetos_id')
                ->references('id')
                ->on('lancamento_projetos')
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
            $table->foreign('necessidades_compras_id')
                ->references('id')
                ->on('necessidades_compras')
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
        Schema::dropIfExists('remessa_produtos');
    }
}
