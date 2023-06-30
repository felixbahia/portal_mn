<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaCampanhaConsultaCampanhaGeralProdutos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campanhas_consulta_meta_vendedores_produtos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('vendedor_codigo')->nullable();
            $table->string('vendedor_descricao')->nullable();
            $table->string('bruto')->nullable();
            $table->string('devolucao')->nullable();
            $table->string('liquido')->nullable();
            $table->string('POS')->nullable();
            $table->integer('campanhas_consulta_meta_vendedor_id')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('campanhas_consulta_meta_vendedor_id')
                ->references('id')
                ->on('campanhas_consulta_meta_vendedores')
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
        Schema::dropIfExists('campanhas_consulta_meta_vendedores_produtos');
    }
}
